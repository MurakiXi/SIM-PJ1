<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Item;
use App\Models\User;


class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $seedDir = database_path('seeders/images/items');

        $rows = [
            ['name' => '腕時計', 'price' => 15000, 'brand' => 'Rolax', 'description' => 'スタイリッシュなデザインのメンズ腕時計', 'condition' => 1, 'filename' => 'watch.jpg'],
            ['name' => 'HDD', 'price' => 5000, 'brand' => '西芝', 'description' => '高速で信頼性の高いハードディスク', 'condition' => 2, 'filename' => 'hdd.jpg'],
            ['name' => '玉ねぎ3束', 'price' => 300, 'brand' => null, 'description' => '新鮮な玉ねぎ3束のセット', 'condition' => 3, 'filename' => 'onion.jpg'],
            ['name' => '革靴', 'price' => 4000, 'brand' => null, 'description' => 'クラシックなデザインの革靴', 'condition' => 4, 'filename' => 'leather-shoes.jpg'],
            ['name' => 'ノートPC', 'price' => 45000, 'brand' => null, 'description' => '高性能なノートパソコン', 'condition' => 1, 'filename' => 'laptop.jpg'],
            ['name' => 'マイク', 'price' => 8000, 'brand' => null, 'description' => '高音質のレコーディング用マイク', 'condition' => 2, 'filename' => 'mic.jpg'],
            ['name' => 'ショルダーバッグ', 'price' => 3500, 'brand' => null, 'description' => 'おしゃれなショルダーバッグ', 'condition' => 3, 'filename' => 'shoulder-bag.jpg'],
            ['name' => 'タンブラー', 'price' => 500, 'brand' => null, 'description' => '使いやすいタンブラー', 'condition' => 4, 'filename' => 'tumbler.jpg'],
            ['name' => 'コーヒーミル', 'price' => 4000, 'brand' => 'Starbacks', 'description' => '手動のコーヒーミル', 'condition' => 1, 'filename' => 'coffee-mill.jpg'],
            ['name' => 'メイクセット', 'price' => 2500, 'brand' => null, 'description' => '便利なメイクアップセット', 'condition' => 2, 'filename' => 'makeup-set.jpg'],
        ];

        $sellerIds = User::query()->pluck('id')->values();

        if ($sellerIds->isEmpty()) {
            throw new \RuntimeException('users がありません。先に UserSeeder を実行してください。');
        }

        foreach ($rows as $i => $r) {
            $src = $seedDir . DIRECTORY_SEPARATOR . $r['filename'];

            if (! file_exists($src)) {
                throw new \RuntimeException("seed画像が見つかりません: {$src}");
            }

            Storage::disk('public')->putFileAs('items', new File($src), $r['filename']);

            Item::create([
                'seller_id'   => $sellerIds[$i % $sellerIds->count()],
                'name'        => $r['name'],
                'brand'       => $r['brand'] ?: null,
                'description' => $r['description'],
                'price'       => $r['price'],
                'image_path'   => 'items/' . $r['filename'],
                'status'      => 'on_sale',
                'condition'   => $r['condition'],
            ]);
        }
    }
}
