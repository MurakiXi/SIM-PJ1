<?php

namespace Tests\Feature\Items;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Item;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    // ID7-1:
    public function test_show_displays_basic_information_for_guest(): void
    {
        //testcategory
        $category = Category::create(['name' => '家電']);

        //testitem
        $item = Item::factory()->create([
            'name'        => 'テスト商品',
            'brand'       => 'Rolax',
            'price'       => 15000,
            'description' => '説明文です',
            'condition'   => 1,
            'status'      => 'on_sale',
            'image_path'  => 'items/dummy.jpg',
        ]);

        //make pivot
        $item->categories()->attach($category->id);

        //get
        $response = $this->get("/item/{$item->id}");

        //200
        $response->assertOk();

        //can see name and brand
        $response->assertSeeText('テスト商品');
        $response->assertSeeText('Rolax');

        //can see price
        $response->assertSeeText('¥15,000');

        //can see image
        $response->assertSee('storage/items/dummy.jpg', false);

        //can see description
        $response->assertSeeText('説明文です');

        //can see category
        $response->assertSeeText('家電');

        //can see status
        $response->assertSeeText('良好');

        //can see 'purchase'
        $response->assertSeeText('購入手続きへ');

        //number of like and comment are '0'
        $response->assertSeeText('0');
    }

    // ID7-2:
    public function test_show_displays_sold_label_and_purchase_disabled(): void
    {
        //testitem
        $item = Item::factory()->create([
            'name'   => '売り切れ品',
            'status' => 'sold',
        ]);

        //get
        $response = $this->get("/item/{$item->id}");

        //200
        $response->assertOk();

        //sold
        $response->assertSeeText('売り切れ品');
        $response->assertSeeText('Sold');

        //can see sold
        $response->assertSeeText('売約済み');

        //can't see purchase nor processing
        $response->assertDontSeeText('購入手続きへ');
        $response->assertDontSeeText('購入手続き中');
    }

    // ID7:
    public function test_show_displays_like_and_comment_counts_and_comment_list(): void
    {
        $item = Item::factory()->create([
            'name' => 'カウント確認品',
            'status' => 'on_sale',
        ]);

        //make 2 likes
        $u1 = User::factory()->create(['name' => '太郎']);
        $u2 = User::factory()->create(['name' => '花子']);
        Like::create(['user_id' => $u1->id, 'item_id' => $item->id]);
        Like::create(['user_id' => $u2->id, 'item_id' => $item->id]);

        //make comment
        Comment::create([
            'user_id' => $u1->id,
            'item_id' => $item->id,
            'body'    => 'コメント本文です',
        ]);

        //get
        $response = $this->get("/item/{$item->id}");

        //200
        $response->assertOk();

        //can see likes_count/comments_count
        $response->assertSeeText('2');
        $response->assertSeeText('1');

        //can see name and body on the comment section
        $response->assertSeeText('太郎');
        $response->assertSeeText('コメント本文です');

        //can see comment and the number
        $response->assertSeeText('コメント(1)');
    }

    // ID7-3
    public function test_show_displays_multiple_categories(): void
    {
        //testcategories
        $category1 = \App\Models\Category::create(['name' => '家電']);
        $category2 = \App\Models\Category::create(['name' => 'メンズ']);

        //testitem
        $item = \App\Models\Item::factory()->create([
            'name'   => 'カテゴリ確認品',
            'status' => 'on_sale',
        ]);

        //add 2 categories
        $item->categories()->attach([$category1->id, $category2->id]);

        //get
        $response = $this->get("/item/{$item->id}");

        //200
        $response->assertOk();

        //can see both categories
        $response->assertSeeText('家電');
        $response->assertSeeText('メンズ');
    }
}
