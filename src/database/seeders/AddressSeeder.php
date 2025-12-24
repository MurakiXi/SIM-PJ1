<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Address;
use App\Models\User;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        //
        $seller = User::where('email', 'seller@example.com')->first();
        $buyer  = User::where('email', 'buyer@example.com')->first();

        if (! $seller || ! $buyer) {
            $this->command?->warn('AddressSeeder: seller/buyer user not found. Run UserSeeder first.');
            return;
        }

        Address::updateOrCreate(
            ['user_id' => $seller->id],
            [
                'postal_code' => '100-0001',
                'address'     => '東京都千代田区千代田1-1',
                'building'    => 'testbuilding101',
            ]
        );

        Address::updateOrCreate(
            ['user_id' => $buyer->id],
            [
                'postal_code' => '150-0001',
                'address'     => '東京都渋谷区神宮前1-1-1',
                'building'    => null,
            ]
        );
    }
}
