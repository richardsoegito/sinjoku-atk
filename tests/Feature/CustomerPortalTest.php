<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_and_sidebar_only_show_customer_transactions(): void
    {
        $customer = $this->createCustomer('Customer Dashboard');
        $activeSale = $this->createSale($customer, 'Transaksi Aktif', 'new');
        $this->createSale($customer, 'Transaksi Selesai', 'completed');
        $this->createSale($customer, 'Transaksi Dibatalkan Dashboard', 'cancelled');
        $this->createSale(User::factory()->create(), 'Transaksi Customer Lain', 'in_progress');

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dasbor Customer')
            ->assertSee('Transaksi Aktif')
            ->assertSee('Transaksi Dibatalkan Dashboard')
            ->assertSee('Dibatalkan')
            ->assertDontSee('Transaksi Customer Lain')
            ->assertSee(route('customer.transactions', ['type' => 'active']))
            ->assertSee(route('customer.transactions', ['type' => 'history']))
            ->assertDontSee(route('sales.index'))
            ->assertDontSee(route('settings.users'))
            ->assertDontSee(route('barang.index'));

        $this->assertSame($customer->id, $activeSale->customer_user_id);
    }

    public function test_customer_transaction_lists_only_own_active_and_completed_sales(): void
    {
        $customer = $this->createCustomer('Customer List');
        $this->createSale($customer, 'Transaksi Baru', 'new');
        $this->createSale($customer, 'Transaksi Proses', 'in_progress');
        $this->createSale($customer, 'Transaksi Historis', 'completed');
        $cancelledSale = $this->createSale($customer, 'Transaksi Dibatalkan', 'cancelled');
        $this->createSale(User::factory()->create(), 'Transaksi Privat', 'new');

        $this->actingAs($customer)
            ->get(route('customer.transactions', ['type' => 'active']))
            ->assertOk()
            ->assertSee('Transaksi Baru')
            ->assertSee('Transaksi Proses')
            ->assertDontSee('Transaksi Historis')
            ->assertDontSee('Transaksi Dibatalkan')
            ->assertDontSee('Transaksi Privat');

        $this->get(route('customer.transactions', ['type' => 'history']))
            ->assertOk()
            ->assertSee('Transaksi Historis')
            ->assertSee('Transaksi Dibatalkan')
            ->assertSee('Dibatalkan')
            ->assertDontSee('Transaksi Baru')
            ->assertDontSee('Transaksi Proses')
            ->assertDontSee('Transaksi Privat');

        $this->get(route('customer.transactions.show', $cancelledSale))
            ->assertOk()
            ->assertSee('Dibatalkan')
            ->assertSee(route('customer.transactions', ['type' => 'history']));
    }

    public function test_customer_cannot_open_staff_transaction_or_settings_pages(): void
    {
        $customer = $this->createCustomer('Customer Terbatas');

        $this->actingAs($customer)
            ->get(route('sales.index'))
            ->assertForbidden();

        $this->get(route('settings.users'))->assertForbidden();
    }

    public function test_customer_can_open_own_transaction_details_from_active_and_history_lists(): void
    {
        $customer = $this->createCustomer('Customer Detail');
        $activeSale = $this->createSale($customer, 'Detail Transaksi Berjalan', 'in_progress');
        $activeSale->update([
            'delivery_address' => 'Jl. Detail No. 10',
            'description' => 'Kirim sebelum sore.',
        ]);
        $activeSale->items()->first()->update([
            'status' => 'loaded',
            'unit' => 'dus',
            'transport_name' => 'Truk Detail',
            'vehicle_number' => 'B 1234 CD',
        ]);
        $completedSale = $this->createSale($customer, 'Detail Transaksi Selesai', 'completed');

        $this->actingAs($customer)
            ->get(route('customer.transactions', ['type' => 'active']))
            ->assertSee(route('customer.transactions.show', $activeSale));

        $this->get(route('customer.transactions', ['type' => 'history']))
            ->assertSee(route('customer.transactions.show', $completedSale));

        $this->get(route('customer.transactions.show', $activeSale))
            ->assertOk()
            ->assertSee('Jl. Detail No. 10')
            ->assertSee('Kirim sebelum sore.')
            ->assertSee('Truk Detail')
            ->assertSee('B 1234 CD')
            ->assertSee('Rp 10.000');

        $otherCustomer = $this->createCustomer('Customer Lain Detail');
        $privateSale = $this->createSale($otherCustomer, 'Transaksi Privat Detail', 'completed');

        $this->get(route('customer.transactions.show', $privateSale))->assertNotFound();
    }

    private function createCustomer(string $name): User
    {
        $customer = User::factory()->create(['name' => $name]);
        $customer->syncRoles('customer');
        $customer->customer()->create(['alamat' => 'Jl. Customer No. 1']);

        return $customer;
    }

    private function createSale(User $customer, string $name, string $status): Sale
    {
        $creator = User::factory()->create();

        $sale = Sale::query()->create([
            'user_id' => $creator->id,
            'customer_user_id' => $customer->id,
            'store_name' => $customer->name,
            'invoice_number' => 'INV-'.fake()->unique()->numerify('########-######'),
            'manual_invoice_number' => $name,
            'sale_date' => now()->toDateString(),
            'status' => $status,
        ]);
        $sale->items()->create([
            'product_name' => $name,
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 10000,
        ]);

        return $sale;
    }
}
