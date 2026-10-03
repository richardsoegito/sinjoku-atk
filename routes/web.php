<?php

use App\Livewire\Barang\Create as BarangCreate;
use App\Livewire\Barang\Edit as BarangEdit;
use App\Livewire\Barang\Index as BarangIndex;
use App\Livewire\Customer\TransactionDetails as CustomerTransactionDetails;
use App\Livewire\Customer\Transactions as CustomerTransactions;
use App\Livewire\Dashboard;
use App\Livewire\Sales\Create;
use App\Livewire\Sales\Edit;
use App\Livewire\Sales\Index;
use App\Livewire\Sales\Tracking;
use App\Livewire\Settings\Permissions;
use App\Livewire\Settings\Roles;
use App\Livewire\Settings\Users;
use App\Livewire\Settings\Users\Create as CreateUser;
use App\Livewire\Settings\Users\Edit as EditUser;
use App\Models\Sale;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::redirect('/', '/login')->name('home');

Route::livewire('track/{token}', Tracking::class)->name('sales.track');
Route::get('track/{token}/delivery-proof', function (string $token) {
    $sale = Sale::query()
        ->where('tracking_token_hash', hash('sha256', $token))
        ->firstOrFail();

    abort_unless($sale->delivery_proof_path, 404);

    $filename = 'bukti-pengiriman-'.$sale->invoice_number.'.'.pathinfo($sale->delivery_proof_path, PATHINFO_EXTENSION);

    return Storage::disk('public')->response($sale->delivery_proof_path, $filename, [], 'inline');
})->name('sales.track.delivery-proof');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', Dashboard::class)->name('dashboard');
    Route::livewire('customer/transactions/{type}', CustomerTransactions::class)->name('customer.transactions');
    Route::livewire('customer/transaction/{sale}', CustomerTransactionDetails::class)->name('customer.transactions.show');
    Route::livewire('barang', BarangIndex::class)->name('barang.index');
    Route::livewire('barang/create', BarangCreate::class)->name('barang.create');
    Route::livewire('barang/{barang}/edit', BarangEdit::class)->name('barang.edit');
    Route::livewire('settings/roles', Roles::class)->name('settings.roles');
    Route::livewire('settings/permissions', Permissions::class)->name('settings.permissions');
    Route::livewire('settings/users', Users::class)->name('settings.users');
    Route::livewire('settings/users/create', CreateUser::class)->name('settings.users.create');
    Route::livewire('settings/users/{user}/edit', EditUser::class)->name('settings.users.edit');

    Route::livewire('sales', Index::class)->name('sales.index');
    Route::livewire('sales/create', Create::class)->name('sales.create');
    Route::livewire('sales/{sale}/edit', Edit::class)->name('sales.edit');
    Route::get('sales/{sale}/delivery-proof/view', function (Sale $sale) {
        abort_unless(($sale->user_id === auth()->id() || auth()->user()->can('sales.view-all') || auth()->user()->can('sales.manage-all')) && $sale->delivery_proof_path, 404);

        return Storage::disk('public')->response($sale->delivery_proof_path, 'bukti-pengiriman-'.$sale->invoice_number.'.'.pathinfo($sale->delivery_proof_path, PATHINFO_EXTENSION), [], 'inline');
    })->name('sales.delivery-proof.view');
    Route::get('sales/{sale}/delivery-proof', function (Sale $sale) {
        abort_unless(($sale->user_id === auth()->id() || auth()->user()->can('sales.view-all') || auth()->user()->can('sales.manage-all')) && $sale->delivery_proof_path, 404);

        return Storage::disk('public')->download($sale->delivery_proof_path, 'surat-jalan-'.$sale->invoice_number.'.'.pathinfo($sale->delivery_proof_path, PATHINFO_EXTENSION));
    })->name('sales.delivery-proof.download');
});

require __DIR__.'/settings.php';
