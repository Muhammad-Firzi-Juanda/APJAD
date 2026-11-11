<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'nuptk')) {
                    $table->string('nuptk', 50)->nullable()->index()->after('email');
                }
                if (! Schema::hasColumn('users', 'no_hp')) {
                    $table->string('no_hp', 30)->nullable()->after('nuptk');
                }
                if (! Schema::hasColumn('users', 'jabatan')) {
                    $table->string('jabatan', 100)->nullable()->index()->after('role');
                }
                if (! Schema::hasColumn('users', 'must_change_password')) {
                    $table->boolean('must_change_password')->default(false)->after('status');
                }
            });

            // Pastikan hanya ada maksimal 1 akun Super Admin dan 1 akun Admin (Fix.md Bagian 1)
            foreach (['Super Admin', 'Admin'] as $singletonRole) {
                $ids = DB::table('users')
                    ->where('role', $singletonRole)
                    ->orderBy('id', 'asc')
                    ->pluck('id');

                if ($ids->count() > 1) {
                    $keepId = $ids->first();
                    DB::table('users')
                        ->where('role', $singletonRole)
                        ->where('id', '!=', $keepId)
                        ->delete();
                }
            }
        }

        if (! Schema::hasTable('account_generation_histories')) {
            Schema::create('account_generation_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('actor_name', 150)->nullable();
                $table->string('actor_role', 50)->nullable();
                $table->string('file_name', 255)->nullable();
                $table->string('idempotency_key', 100)->nullable()->unique();
                $table->string('password_method', 30)->default('auto');
                $table->unsignedInteger('total_processed')->default(0);
                $table->unsignedInteger('total_created')->default(0);
                $table->unsignedInteger('total_failed')->default(0);
                $table->unsignedInteger('count_fakultas')->default(0);
                $table->unsignedInteger('count_jurusan')->default(0);
                $table->unsignedInteger('count_prodi')->default(0);
                $table->json('error_summary')->nullable();
                $table->json('created_accounts_summary')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_generation_histories');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $cols = [];
                foreach (['nuptk', 'no_hp', 'jabatan', 'must_change_password'] as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $cols[] = $col;
                    }
                }
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
