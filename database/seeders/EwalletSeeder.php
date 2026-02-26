<?php

namespace Database\Seeders;

use App\Models\Ewallet;
use Illuminate\Database\Seeder;

class EwalletSeeder extends Seeder
{
    public function run(): void
    {
        // Create a single SYSTEM wallet if none exists yet
        if (! Ewallet::where('owner_type', 'SYSTEM')->exists()) {
            Ewallet::create([
                'owner_type' => 'SYSTEM',
                'owner_id' => null,
                'balance' => 0,
                'status' => true,
            ]);
        }
    }
}

