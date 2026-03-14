<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // ── Create admin in the separate `admins` table ──
        $admin = Admin::firstOrCreate(
            ['email' => 'admin@intecworks.com'],
            [
                'full_name'         => 'System Admin',
                'phone'             => '+2348000000000',
                'password'          => 'password',   // auto-hashed via cast
                'status'            => 'active',
                'location'          => 'Lagos, Nigeria',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("✅  Admin seeded in `admins` table: admin@intecworks.com / password");

        // ── Default platform settings ──
        PlatformSetting::setValue('commission_percent', 10);
        PlatformSetting::setValue('feature_toggles', [
            'chat_enabled'            => true,
            'supplier_marketplace'    => true,
            'wallet_withdrawals'      => true,
            'push_notifications'      => true,
        ]);
        PlatformSetting::setValue('job_categories', []);
        PlatformSetting::setValue('material_categories', []);

        $this->command->info("✅  Default platform settings seeded.");
    }
}
