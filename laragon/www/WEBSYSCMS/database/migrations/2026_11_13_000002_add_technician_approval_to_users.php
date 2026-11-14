<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'technician_approved_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('technician_approved_at')->nullable();
            });
        }

        DB::table('users')
            ->whereNull('technician_approved_at')
            ->where('role', 'technician')
            ->update(['technician_approved_at' => now()]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'technician_approved_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('technician_approved_at');
            });
        }
    }
};
