<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
        });

        DB::table('users')->orderBy('id')->get(['id', 'email'])->each(function (object $user): void {
            $base = strtolower((string) preg_replace('/[^a-z0-9]/', '', explode('@', $user->email)[0]));
            $base = substr($base !== '' ? $base : 'user'.$user->id, 0, 50);
            $username = $base;
            $suffix = 1;

            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $suffixText = (string) $suffix++;
                $username = substr($base, 0, 50 - strlen($suffixText)).$suffixText;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });

        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('alamat');
            $table->timestamps();
        });

        Role::findOrCreate('customer', 'web');
    }

    public function down(): void
    {
        Schema::dropIfExists('customer');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });
    }
};
