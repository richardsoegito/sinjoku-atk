<?php

namespace App\Livewire;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dasbor')]
class Dashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('customer') || auth()->user()->can('dashboard.view'), 403);
    }

    public function render()
    {
        if (auth()->user()->hasRole('customer')) {
            $customerSales = Sale::query()->where('customer_user_id', auth()->id());

            return view('livewire.customer.dashboard', [
                'activeCount' => (clone $customerSales)->whereIn('status', ['new', 'not_started', 'in_progress'])->count(),
                'completedCount' => (clone $customerSales)->where('status', 'completed')->count(),
                'recentSales' => (clone $customerSales)
                    ->with('items')
                    ->latest('sale_date')
                    ->latest('id')
                    ->limit(5)
                    ->get(),
            ]);
        }

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $previousMonthStart = $monthStart->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $monthStart->copy()->subDay();
        $salesQuery = $this->salesQuery();

        $monthRevenue = $this->revenueBetween($salesQuery, $monthStart, $monthEnd);
        $previousMonthRevenue = $this->revenueBetween($salesQuery, $previousMonthStart, $previousMonthEnd);
        $revenueChange = $previousMonthRevenue > 0
            ? round((($monthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100)
            : null;
        $monthOrders = (clone $salesQuery)
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->count();
        $statusCounts = (clone $salesQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $deliveryCounts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.id', (clone $salesQuery)->select('id'))
            ->selectRaw("COALESCE(sale_items.status, 'new') as status")
            ->selectRaw('SUM(sale_items.quantity) as units')
            ->groupByRaw("COALESCE(sale_items.status, 'new')")
            ->pluck('units', 'status');

        $monthlyTrend = collect(range(5, 0))->map(function (int $monthsAgo) use ($salesQuery): array {
            $start = Carbon::today()->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => $start->locale('id')->translatedFormat('M'),
                'revenue' => $this->revenueBetween($salesQuery, $start, $start->copy()->endOfMonth()),
            ];
        });

        $recentSales = (clone $salesQuery)
            ->with('items')
            ->latest('sale_date')
            ->latest('id')
            ->limit(6)
            ->get();
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.id', (clone $salesQuery)->select('id'))
            ->select('sale_items.product_name')
            ->selectRaw('SUM(sale_items.quantity) as units')
            ->groupBy('sale_items.product_name')
            ->orderByDesc('units')
            ->orderBy('sale_items.product_name')
            ->limit(5)
            ->get();

        return view('livewire.dashboard', [
            'monthRevenue' => $monthRevenue,
            'revenueChange' => $revenueChange,
            'monthOrders' => $monthOrders,
            'activeOrders' => (int) $statusCounts->get('new', 0)
                + (int) $statusCounts->get('not_started', 0)
                + (int) $statusCounts->get('in_progress', 0),
            'completedOrders' => (int) $statusCounts->get('completed', 0),
            'deliveryCounts' => [
                'new' => (int) $deliveryCounts->get('new', 0),
                'loaded' => (int) $deliveryCounts->get('loaded', 0),
                'delivered' => (int) $deliveryCounts->get('delivered', 0),
            ],
            'monthlyTrend' => $monthlyTrend,
            'recentSales' => $recentSales,
            'topProducts' => $topProducts,
            'maxMonthlyRevenue' => max(1, (float) $monthlyTrend->max('revenue')),
            'maxProductUnits' => max(1, (int) $topProducts->max('units')),
        ]);
    }

    private function revenueBetween(Builder $salesQuery, Carbon $start, Carbon $end): float
    {
        return (float) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.id', (clone $salesQuery)->select('id'))
            ->whereBetween('sales.sale_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(sale_items.quantity * sale_items.unit_price), 0) as total')
            ->value('total');
    }

    private function salesQuery(): Builder
    {
        $query = Sale::query()->whereNotIn('status', ['draft', 'cancelled']);

        if (! auth()->user()->can('sales.manage-all') && ! auth()->user()->can('sales.view-all')) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }
}
