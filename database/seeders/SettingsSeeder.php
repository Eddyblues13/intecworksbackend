<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'paystack_public_key',     'value' => '', 'type' => 'string'],
            ['key' => 'paystack_secret_key',     'value' => '', 'type' => 'string'],
            ['key' => 'korapay_public_key',      'value' => '', 'type' => 'string'],
            ['key' => 'korapay_secret_key',      'value' => '', 'type' => 'string'],
            ['key' => 'korapay_encryption_key',  'value' => '', 'type' => 'string'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(
                ['key' => $s['key']],
                ['value' => $s['value'], 'type' => $s['type']]
            );
        }
    }
}
