<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $methods = ['mBOK', 'PayPal', 'VISA', 'Syber Pay', 'Manual'];
        foreach ($methods as $method) {
            \App\Models\PaymentMethod::create([
                'name' => $method
            ]);
        }
        $status = ['pending', 'approved'];
        foreach ($status as $value) {
            \App\Models\PaymentStatus::create([
                'name' => $value
            ]);
        }
        \App\Models\Language::create([
            'name' => 'arabic',
            'short_name' => 'ar'
        ]);
        \App\Models\Language::create([
            'name' => 'english',
            'short_name' => 'en'
        ]);
    }
}
