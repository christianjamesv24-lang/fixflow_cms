<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $this->removeDemoAccounts();
        $this->ensureStaffAccounts();
    }

    protected function removeDemoAccounts(): void
    {
        DB::table('contact_messages')->delete();
        DB::table('users')->whereIn('role', ['admin', 'technician'])->delete();
        DB::table('users')->where('role', 'customer')->delete();
    }

    protected function ensureStaffAccounts(): void
    {
        $staff = [
            [
                'email' => 'admin@fixflowservice.com',
                'name' => 'Operations Admin',
                'role' => 'admin',
                'password' => 'Admin@2026',
                'avatar_color' => '#4f46e5',
            ],
            [
                'email' => 'technician@fixflowservice.com',
                'name' => 'Field Technician',
                'role' => 'technician',
                'password' => 'Tech@2026',
                'avatar_color' => '#2563eb',
            ],
        ];

        foreach ($staff as $account) {
            DB::table('users')->insert([
                'name' => $account['name'],
                'email' => $account['email'],
                'password_hash' => password_hash($account['password'], PASSWORD_DEFAULT),
                'role' => $account['role'],
                'status' => 'active',
                'avatar_color' => $account['avatar_color'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
