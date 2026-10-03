<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_account_menu_displays_the_users_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pemilik');
    }

    public function test_owner_dashboard_includes_sales_created_by_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $sale = Sale::create([
            'user_id' => $user->id,
            'store_name' => 'Toko Dashboard',
            'invoice_number' => 'INV-20260928-DASH01',
            'sale_date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);
        $sale->items()->create([
            'product_name' => 'Kertas A4',
            'quantity' => 2,
            'unit_price' => 10000,
            'discount_percent' => 10,
            'status' => 'loaded',
        ]);

        $otherSale = Sale::create([
            'user_id' => $otherUser->id,
            'store_name' => 'Toko Privat',
            'invoice_number' => 'INV-20260928-DASH02',
            'sale_date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);
        $otherSale->items()->create([
            'product_name' => 'Barang Rahasia',
            'quantity' => 100,
            'unit_price' => 100000,
            'status' => 'loaded',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rp 10.020.000')
            ->assertSee('Toko Dashboard')
            ->assertSee('Kertas A4')
            ->assertSee('Toko Privat')
            ->assertSee('Barang Rahasia');
    }

    public function test_dashboard_keeps_sales_scoped_without_view_all_permission(): void
    {
        $viewerRole = Role::create(['name' => 'sales-own-dashboard', 'guard_name' => 'web']);
        $viewerRole->syncPermissions(['dashboard.view', 'sales.view']);
        $viewer = User::factory()->create();
        $viewer->syncRoles($viewerRole);
        $otherUser = User::factory()->create();

        $ownSale = Sale::create([
            'user_id' => $viewer->id,
            'store_name' => 'Transaksi Milik Saya',
            'invoice_number' => 'INV-20260928-VIEW01',
            'sale_date' => now()->toDateString(),
            'status' => 'new',
        ]);
        $ownSale->items()->create([
            'product_name' => 'Produk Saya',
            'quantity' => 1,
            'unit_price' => 12000,
            'status' => 'new',
        ]);

        $otherSale = Sale::create([
            'user_id' => $otherUser->id,
            'store_name' => 'Transaksi Rahasia',
            'invoice_number' => 'INV-20260928-VIEW02',
            'sale_date' => now()->toDateString(),
            'status' => 'new',
        ]);
        $otherSale->items()->create([
            'product_name' => 'Produk Rahasia',
            'quantity' => 100,
            'unit_price' => 100000,
            'status' => 'new',
        ]);

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rp 12.000')
            ->assertSee('Transaksi Milik Saya')
            ->assertDontSee('Transaksi Rahasia')
            ->assertDontSee('Produk Rahasia');
    }
}
