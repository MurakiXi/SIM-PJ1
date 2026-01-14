<?php

namespace Tests\Feature\Item;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExhibitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sell_create_page_can_be_opened(): void
    {
        $user = User::factory()->create();

        Category::create(['name' => '本']);

        $res = $this->actingAs($user)->get(route('sell.create'));

        $res->assertOk();
        $res->assertSee('商品の出品');
        $res->assertSee('name="image"', false);
        $res->assertSee('name="name"', false);
        $res->assertSee('name="category_ids[]"', false);
        $res->assertSee('本');
    }

    /** @test */
    public function seller_can_store_item_and_categories_and_image(): void
    {
        Storage::fake('public');

        $seller = User::factory()->create();

        // カテゴリはFactoryが無いので直に作成
        $cat1 = Category::create(['name' => '本']);
        $cat2 = Category::create(['name' => '家電']);

        // GD無し環境でも落ちないファイル偽装（以前の件の回避策でございます）
        $file = UploadedFile::fake()->create('item.png', 10, 'image/png');

        $payload = [
            'image'        => $file,
            'category_ids' => [$cat1->id, $cat2->id],
            'condition'    => 1, // 良好
            'name'         => 'テスト出品',
            'brand'        => 'テストブランド',
            'description'  => '説明文です',
            'price'        => 1234,
        ];

        $res = $this->actingAs($seller)->post(route('sell.store'), $payload);

        // ItemController@store は items.index へリダイレクトの実装でございますね
        $res->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('items', [
            'seller_id'   => $seller->id,
            'name'        => 'テスト出品',
            'brand'       => 'テストブランド',
            'description' => '説明文です',
            'price'       => 1234,
            'condition'   => 1,
            'status'      => 'on_sale',
        ]);

        $item = Item::where('seller_id', $seller->id)->where('name', 'テスト出品')->firstOrFail();

        // 画像が保存されている（store('items','public') の戻り値 = image_path）
        $this->assertNotEmpty($item->image_path);
        Storage::disk('public')->assertExists($item->image_path);

        // ピボット（category_item）が作られている
        $this->assertDatabaseHas('category_item', [
            'item_id'     => $item->id,
            'category_id' => $cat1->id,
        ]);
        $this->assertDatabaseHas('category_item', [
            'item_id'     => $item->id,
            'category_id' => $cat2->id,
        ]);
    }
}
