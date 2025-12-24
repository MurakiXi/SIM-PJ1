<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seller = User::factory()->create([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'password' => Hash::make('testpass1')
        ]);

        $buyer = User::factory()->create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => Hash::make('testpass2')
        ]);
    }
}
