<?php

namespace Tests\Feature;

use App\Livewire\Sales\Create;
use App\Livewire\Sales\Edit;
use App\Livewire\Sales\Index;
use App\Livewire\Sales\Tracking;
use App\Models\Barang;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_pages_require_authentication(): void
    {
        $this->get('/sales')->assertRedirect('/login');
        $this->get('/sales/create')->assertRedirect('/login');
    }

    public function test_sales_index_is_displayed(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/sales')->assertOk();
    }

    public function test_delivery_proof_upload_is_only_shown_when_editing_a_sale(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->syncRoles('super-admin');

        Livewire::actingAs($superAdmin)
            ->test(Create::class)
            ->assertDontSee('Bukti pengiriman')
            ->assertDontSee('Unggah gambar atau PDF');

        $owner = User::factory()->create();
        $sale = Sale::create([
            'user_id' => $owner->id,
            'store_name' => 'Toko Dokumen',
            'invoice_number' => 'INV-20260928-CREATE1',
            'sale_date' => '2026-09-28',
            'status' => 'new',
        ]);

        Livewire::actingAs($owner)
            ->test(Edit::class, ['sale' => $sale])
            ->assertSee('Bukti pengiriman')
            ->assertSee('Unggah gambar atau PDF');
    }

    public function test_item_delivery_status_is_hidden_on_create_and_defaults_to_new(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('admin');

        Livewire::actingAs($user)
            ->test(Create::class)
            ->assertSee('Satuan')
            ->assertDontSee('Status pengiriman')
            ->assertSet('items.0.status', 'new');
    }

    public function test_sale_owner_can_generate_a_private_tracking_link(): void
    {
        $user = User::factory()->create();
        $sale = Sale::create([
            'user_id' => $user->id,
            'store_name' => 'Toko Sejahtera',
            'invoice_number' => 'INV-20260928-TRACK1',
            'sale_date' => '2026-09-28',
            'status' => 'new',
        ]);

        $component = Livewire::actingAs($user)
            ->test(Index::class)
            ->call('generateTrackingLink', $sale->id)
            ->assertHasNoErrors();

        $trackingUrl = $component->get('trackingUrl');
        $token = basename($trackingUrl);

        $this->assertSame(route('sales.track', $token), $trackingUrl);
        $this->assertSame(hash('sha256', $token), $sale->refresh()->tracking_token_hash);
        $this->assertNotSame($token, $sale->tracking_token_hash);
    }

    public function test_owner_can_generate_a_tracking_link_for_an_admins_sale(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles('admin');
        $owner = User::factory()->create();
        $sale = Sale::create([
            'user_id' => $admin->id,
            'store_name' => 'Toko Sejahtera',
            'invoice_number' => 'INV-20260928-TRACK3',
            'sale_date' => '2026-09-28',
            'status' => 'new',
        ]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('generateTrackingLink', $sale->id)
            ->assertHasNoErrors();

        $this->assertNotNull($sale->refresh()->tracking_token_hash);
    }

    public function test_tracking_page_shows_transaction_details_and_refreshes_sale_data(): void
    {
        $user = User::factory()->create();
        $sale = Sale::create([
            'user_id' => $user->id,
            'store_name' => 'Toko Sejahtera',
            'invoice_number' => 'INV-20260928-TRACK2',
            'sale_date' => '2026-09-28',
            'description' => 'Mohon dikirim sebelum Jumat.',
            'delivery_address' => 'Jl. Mawar No. 10, Surabaya',
            'status' => 'new',
        ]);
        $item = $sale->items()->create([
            'product_name' => 'Buku Tulis',
            'quantity' => 3,
            'unit_price' => 12500,
            'discount_percent' => 10,
            'status' => 'new',
        ]);
        $token = $sale->generateTrackingToken();
        $trackingUrl = route('sales.track', $token);

        $this->get($trackingUrl)
            ->assertOk()
            ->assertSee('Toko Sejahtera')
            ->assertSee('INV-20260928-TRACK2')
            ->assertSee('Jl. Mawar No. 10, Surabaya')
            ->assertSee('Buku Tulis')
            ->assertSee('Rp 37.500')
            ->assertSee('Mohon dikirim sebelum Jumat.')
            ->assertSee('wire:poll.60s', false);

        $trackingPage = Livewire::test(Tracking::class, ['token' => $token])
            ->assertSee('Menunggu');

        $item->update(['status' => 'loaded', 'transport_name' => 'Truk Jaya']);

        $trackingPage
            ->call('$refresh')
            ->assertSee('Perjalanan')
            ->assertSee('Truk Jaya');
    }

    public function test_tracking_page_rejects_unknown_tokens(): void
    {
        $this->get(route('sales.track', str_repeat('x', 64)))->assertNotFound();
    }

    public function test_owner_can_view_existing_delivery_proof_inline(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('delivery-proofs/proof.jpg', 'image contents');

        $owner = User::factory()->create();
        $sale = Sale::create([
            'user_id' => $owner->id,
            'store_name' => 'Toko Sejahtera',
            'invoice_number' => 'INV-20260928-PROOF1',
            'sale_date' => '2026-09-28',
            'status' => 'new',
            'delivery_proof_path' => 'delivery-proofs/proof.jpg',
        ]);

        $this->actingAs($owner);

        Livewire::test(Edit::class, ['sale' => $sale])
            ->assertSee(route('sales.delivery-proof.view', $sale), false)
            ->assertSee('Lihat bukti pengiriman')
            ->assertSee('Unduh bukti pengiriman');

        $response = $this->get(route('sales.delivery-proof.view', $sale));

        $response->assertOk();
        $this->assertStringStartsWith('inline', $response->headers->get('content-disposition'));
        $response->assertStreamedContent('image contents');

        $limitedViewer = User::factory()->create();
        $limitedRole = Role::create(['name' => 'sales-own-only', 'guard_name' => 'web']);
        $limitedRole->syncPermissions(['sales.view']);
        $limitedViewer->syncRoles($limitedRole);

        $this->actingAs($limitedViewer)
            ->get(route('sales.delivery-proof.view', $sale))
            ->assertNotFound();
    }

    public function test_owner_can_delete_existing_delivery_proof(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('delivery-proofs/proof.jpg', 'image contents');

        $owner = User::factory()->create();
        $sale = Sale::create([
            'user_id' => $owner->id,
            'store_name' => 'Toko Sejahtera',
            'invoice_number' => 'INV-20260928-PROOF2',
            'sale_date' => '2026-09-28',
            'status' => 'new',
            'delivery_proof_path' => 'delivery-proofs/proof.jpg',
        ]);

        Livewire::actingAs($owner)
            ->test(Edit::class, ['sale' => $sale])
            ->call('deleteDeliveryProof')
            ->assertHasNoErrors()
            ->assertSee('Unggah gambar atau PDF');

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'delivery_proof_path' => null,
        ]);
        Storage::disk('public')->assertMissing('delivery-proofs/proof.jpg');
    }

    public function test_sale_can_be_created_with_generated_invoice_and_items(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('admin');
        $customer = $this->createCustomer('Toko Sejahtera');
        $notebook = $this->createProduct('ATK-001', 'Buku Tulis', 12500);
        $pen = $this->createProduct('ATK-002', 'Pulpen', 5000);

        $this->actingAs($user);

        Livewire::test(Create::class)
            ->set('customerUserId', (string) $customer->id)
            ->set('manualInvoiceNumber', 'INV-MANUAL-001')
            ->set('saleDate', '2026-09-18')
            ->set('description', 'Penjualan tunai')
            ->set('deliveryAddress', 'Jl. Mawar No. 10, Surabaya')
            ->set('status', 'completed')
            ->set('items', [
                ['product_id' => $notebook->id, 'quantity' => 3, 'unit' => 'pcs', 'unit_price' => 12500],
                ['product_id' => $pen->id, 'quantity' => 2, 'unit' => 'pack', 'unit_price' => 5000],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('sales.index', absolute: false));

        $sale = Sale::query()->with('items')->firstOrFail();

        $this->assertSame('Toko Sejahtera', $sale->store_name);
        $this->assertSame($customer->id, $sale->customer_user_id);
        $this->assertSame('INV-MANUAL-001', $sale->manual_invoice_number);
        $this->assertSame('Jl. Mawar No. 10, Surabaya', $sale->delivery_address);
        $this->assertMatchesRegularExpression('/^INV-'.now()->format('Ymd').'\-[A-Z0-9]{6}$/', $sale->invoice_number);
        $this->assertSame('completed', $sale->status);
        $this->assertCount(2, $sale->items);
        $this->assertSame('Buku Tulis', $sale->items->first()->product_name);
        $this->assertSame($notebook->id, $sale->items->first()->product_id);
        $this->assertSame('pcs', $sale->items->first()->unit);
        $this->assertSame('new', $sale->items->first()->status);
        $this->assertSame('pack', $sale->items->last()->unit);
    }

    public function test_sale_requires_at_least_one_valid_item(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('admin');
        $this->actingAs($user);

        Livewire::test(Create::class)
            ->set('customerUserId', '')
            ->set('items', [])
            ->call('save')
            ->assertHasErrors(['customerUserId', 'items']);
    }

    public function test_sale_can_be_created_without_a_manual_invoice_number(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('admin');
        $customer = $this->createCustomer('Toko Tanpa Invoice');
        $product = $this->createProduct('ATK-003', 'Buku Tulis', 12000);

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('customerUserId', (string) $customer->id)
            ->set('saleDate', '2026-09-30')
            ->set('items', [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit' => 'pcs',
                'unit_price' => 12000,
            ]])
            ->call('save')
            ->assertHasNoErrors();

        $sale = Sale::query()->firstOrFail();

        $this->assertNull($sale->manual_invoice_number);
        $this->assertNotEmpty($sale->invoice_number);
    }

    public function test_sale_can_be_saved_as_an_incomplete_draft_without_customer_or_items(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('admin');

        Livewire::actingAs($user)
            ->test(Create::class)
            ->call('saveDraft')
            ->assertHasNoErrors()
            ->assertRedirect(route('sales.index', absolute: false));

        $sale = Sale::query()->firstOrFail();

        $this->assertSame('draft', $sale->status);
        $this->assertNull($sale->customer_user_id);
        $this->assertSame('', $sale->store_name);
        $this->assertCount(0, $sale->items);
    }

    public function test_transaction_can_be_cancelled_without_deleting_it(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('admin');
        $customer = $this->createCustomer('Customer Batal');
        $sale = Sale::query()->create([
            'user_id' => $user->id,
            'customer_user_id' => $customer->id,
            'store_name' => $customer->name,
            'invoice_number' => 'INV-20261001-CANCEL1',
            'sale_date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);

        Livewire::actingAs($user)
            ->test(Edit::class, ['sale' => $sale])
            ->call('cancelSale')
            ->assertHasNoErrors()
            ->assertRedirect(route('sales.index', absolute: false));

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_selecting_customer_and_product_fills_address_and_master_price(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('admin');
        $customer = $this->createCustomer('Customer Auto', 'Jl. Anggrek No. 5');
        $product = $this->createProduct('ATK-004', 'Kertas A4', 60000);

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('customerUserId', (string) $customer->id)
            ->assertSet('deliveryAddress', 'Jl. Anggrek No. 5')
            ->set('items.0.product_id', (string) $product->id)
            ->assertSet('items.0.product_name', 'Kertas A4')
            ->assertSet('items.0.unit_price', 60000.0)
            ->set('items.0.unit_price', '57000')
            ->set('items.0.unit', 'rim')
            ->set('saleDate', '2026-09-30')
            ->call('save')
            ->assertHasNoErrors();

        $sale = Sale::query()->with('items')->firstOrFail();
        $this->assertSame($customer->id, $sale->customer_user_id);
        $this->assertSame('Jl. Anggrek No. 5', $sale->delivery_address);
        $this->assertSame($product->id, $sale->items->first()->product_id);
        $this->assertSame('57000.00', $sale->items->first()->unit_price);
    }

    public function test_same_product_cannot_be_added_twice_to_one_sale(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('admin');
        $customer = $this->createCustomer('Customer Produk Unik');
        $product = $this->createProduct('ATK-006', 'Buku Tulis Unik', 12000);

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('customerUserId', (string) $customer->id)
            ->set('saleDate', '2026-10-01')
            ->set('items', [
                ['product_id' => $product->id, 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 12000],
                ['product_id' => $product->id, 'quantity' => 2, 'unit' => 'pcs', 'unit_price' => 12000],
            ])
            ->call('save')
            ->assertHasErrors(['items.1.product_id'])
            ->assertSee('Barang yang sama hanya dapat ditambahkan satu kali.');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_sale_can_be_updated_and_deleted_with_items(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('admin');
        $product = $this->createProduct('ATK-005', 'Produk Baru', 2500);
        $sale = Sale::create([
            'user_id' => $user->id,
            'store_name' => 'Toko Lama',
            'invoice_number' => 'INV-20260918-OLD001',
            'sale_date' => '2026-09-18',
            'status' => 'draft',
        ]);
        $sale->items()->create([
            'product_name' => 'Produk Lama',
            'quantity' => 1,
            'unit_price' => 1000,
        ]);

        $this->actingAs($user);

        Livewire::test(Edit::class, ['sale' => $sale])
            ->set('storeName', 'Toko Baru')
            ->set('manualInvoiceNumber', 'INV-MANUAL-UPDATED')
            ->set('deliveryAddress', 'Jl. Baru No. 20, Malang')
            ->set('items', [[
                'product_id' => $product->id,
                'product_name' => 'Produk Baru',
                'quantity' => 4,
                'unit' => 'dus',
                'unit_price' => 2500,
                'status' => 'loaded',
                'transport_name' => 'Truck Ujang',
                'vehicle_number' => 'L 4354 WP',
            ]])
            ->call('update')
            ->assertHasNoErrors();

        $sale->refresh();
        $this->assertSame('Toko Baru', $sale->store_name);
        $this->assertSame('INV-MANUAL-UPDATED', $sale->manual_invoice_number);
        $this->assertSame('Jl. Baru No. 20, Malang', $sale->delivery_address);
        $this->assertSame('Truck Ujang', $sale->items->first()->transport_name);
        $this->assertSame('L 4354 WP', $sale->items->first()->vehicle_number);
        $this->assertSame('Produk Baru', $sale->items->first()->product_name);
        $this->assertSame('dus', $sale->items->first()->unit);

        Livewire::test(Index::class)
            ->call('delete', $sale->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sale_items', ['sale_id' => $sale->id]);
    }

    private function createCustomer(string $name, string $address = 'Jl. Contoh No. 1'): User
    {
        $customer = User::factory()->create(['name' => $name]);
        $customer->syncRoles('customer');
        $customer->customer()->create(['alamat' => $address]);

        return $customer;
    }

    private function createProduct(string $code, string $name, float $price): Barang
    {
        return Barang::query()->create([
            'kode_barang' => $code,
            'nama_barang' => $name,
            'harga_barang' => $price,
        ]);
    }
}
