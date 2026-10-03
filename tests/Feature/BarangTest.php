<?php

namespace Tests\Feature;

use App\Livewire\Barang\Create;
use App\Livewire\Barang\Edit;
use App\Models\Barang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BarangTest extends TestCase
{
    use RefreshDatabase;

    public function test_barang_name_must_be_unique_but_current_name_is_allowed_when_editing(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles('admin');

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('namaBarang', 'Buku Tulis')
            ->set('hargaBarang', '12000')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('namaBarang', 'Buku Tulis')
            ->set('hargaBarang', '15000')
            ->call('save')
            ->assertHasErrors(['namaBarang' => 'unique'])
            ->assertSee('Nama barang sudah digunakan.');

        $barang = Barang::query()->firstOrFail();

        Livewire::actingAs($admin)
            ->test(Edit::class, ['barang' => $barang])
            ->call('update')
            ->assertHasNoErrors();
    }

    public function test_barang_forms_generate_codes_and_show_price_history_on_edit(): void
    {
        $admin = User::factory()->create(['timezone' => 'Asia/Makassar']);
        $admin->syncRoles('admin');

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->assertSet('kodeBarang', 'BRG0001')
            ->set('namaBarang', 'Buku Tulis')
            ->set('hargaBarang', '12000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('barang.index'));

        $barang = Barang::query()->where('kode_barang', 'BRG0001')->firstOrFail();

        $this->assertDatabaseHas('harga_barang', [
            'kode_barang' => 'BRG0001',
            'harga_barang' => '12000.00',
        ]);

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->assertSet('kodeBarang', 'BRG0002')
            ->set('namaBarang', 'Pensil')
            ->set('hargaBarang', '3000')
            ->call('save')
            ->assertHasNoErrors();

        $historyEntry = $barang->hargaHistory()->latest('id')->firstOrFail();
        $expectedHistoryTime = $historyEntry->created_at->copy()->timezone($admin->timezone)->format('d M Y, H:i');
        $unconvertedHistoryTime = $historyEntry->created_at->copy()->timezone('UTC')->format('d M Y, H:i');

        Livewire::actingAs($admin)
            ->test(Edit::class, ['barang' => $barang])
            ->assertSee('Riwayat harga')
            ->assertSee('12.000')
            ->assertSee($expectedHistoryTime)
            ->assertDontSee($unconvertedHistoryTime)
            ->set('hargaBarang', '15000')
            ->call('update')
            ->assertHasNoErrors()
            ->assertRedirect(route('barang.index'));

        $this->assertDatabaseCount('harga_barang', 3);
        $this->assertNotNull($barang->hargaHistory()->latest('id')->firstOrFail()->created_at);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['barang' => $barang])
            ->set('hargaBarang', '15000.00')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('harga_barang', 3);
    }
}
