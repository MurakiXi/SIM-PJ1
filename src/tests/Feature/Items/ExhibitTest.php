<?php

namespace Tests\Feature\Items;

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

    //ID15
    public function test_sell_create_page_can_be_opened(): void
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

    //ID15
    public function test_seller_can_store_item_and_categories_and_image(): void
    {
        Storage::fake('public');

        $seller = User::factory()->create();

        //make category
        $cat1 = Category::create(['name' => '本']);
        $cat2 = Category::create(['name' => '家電']);

        //create png
        $file = UploadedFile::fake()->create('item.png', 10, 'image/png');

        $payload = [
            'image'        => $file,
            'category_ids' => [$cat1->id, $cat2->id],
            'condition'    => 1,
            'name'         => 'テスト出品',
            'brand'        => 'テストブランド',
            'description'  => '説明文です',
            'price'        => 1234,
        ];

        $res = $this->actingAs($seller)->post(route('sell.store'), $payload);

        //redirect to index
        $res->assertRedirect(route('items.index'));

        //item exists on table
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

        //image exists on storage
        $this->assertNotEmpty($item->image_path);
        Storage::disk('public')->assertExists($item->image_path);

        //category_item
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
