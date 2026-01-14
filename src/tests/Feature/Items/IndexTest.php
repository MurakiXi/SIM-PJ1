<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    // ID4-1
    public function test_index_shows_items_and_status_labels(): void
    {
        //testitems
        $onSale = Item::factory()->create([
            'name' => 'りんご',
            'status' => 'on_sale',
        ]);

        $sold = Item::factory()->create([
            'name' => 'みかん',
            'status' => 'sold',
        ]);

        $processing = Item::factory()->create([
            'name' => 'ばなな',
            'status' => 'processing',
        ]);

        //get
        $response = $this->get('/');

        //200
        $response->assertOk();

        //can see each name
        $response->assertSeeText($onSale->name);
        $response->assertSeeText($sold->name);
        $response->assertSeeText($processing->name);

        //ID4-2 can see labels
        $response->assertSeeText('Sold');
        $response->assertSeeText('Processing');
    }

    // ID4-3:can't see own items
    public function test_index_hides_own_items_when_authenticated(): void
    {
        //testusers
        $me = User::factory()->create();
        $other = User::factory()->create();

        //own and others items
        $myItem = Item::factory()->create([
            'seller_id' => $me->id,
            'name' => '自分の商品',
        ]);

        $othersItem = Item::factory()->create([
            'seller_id' => $other->id,
            'name' => '他人の商品',
        ]);

        //login as me and get
        $response = $this->actingAs($me)->get('/');

        //200
        $response->assertOk();

        //can see others items
        $response->assertSeeText($othersItem->name);

        //can't see own items
        $response->assertDontSeeText($myItem->name);
    }

    // ID5-1
    public function test_mylist_shows_only_liked_items_when_authenticated(): void
    {
        //testuser
        $user = User::factory()->create();

        //testitems
        $liked1 = Item::factory()->create(['name' => 'いいね1']);
        $liked2 = Item::factory()->create(['name' => 'いいね2']);
        $notLiked = Item::factory()->create(['name' => 'いいねしてない']);

        //make 2 likes
        Like::create(['user_id' => $user->id, 'item_id' => $liked1->id]);
        Like::create(['user_id' => $user->id, 'item_id' => $liked2->id]);

        //get with tab=mylist
        $response = $this->actingAs($user)->get('/?tab=mylist');

        //200
        $response->assertOk();

        //can see be liked
        $response->assertSeeText($liked1->name);
        $response->assertSeeText($liked2->name);

        //can't see not be liked
        $response->assertDontSeeText($notLiked->name);
    }

    // ID5-2:
    public function test_mylist_shows_sold_label_for_sold_item(): void
    {
        //testuser
        $user = User::factory()->create();

        //testitem
        $soldItem = Item::factory()->create([
            'name' => '売り切れ商品',
            'status' => 'sold',
        ]);

        //like testitem
        Like::create([
            'user_id' => $user->id,
            'item_id' => $soldItem->id,
        ]);

        //get mylist
        $response = $this->actingAs($user)->get('/?tab=mylist');

        //can see testitem
        $response->assertOk();
        $response->assertSeeText($soldItem->name);

        //can see 'Sold'
        $response->assertSeeText('Sold');
    }

    // ID5-3
    public function test_mylist_for_guest_shows_notice(): void
    {
        //testitem
        $item = Item::factory()->create(['name' => 'ゲストでも見えたら困る商品']);

        //get with tab=mylist
        $response = $this->get('/?tab=mylist');

        //200
        $response->assertOk();

        //can see notice in index.blade.php
        $response->assertSeeText('（未認証のため表示できません）');

        //can't see testitem
        $response->assertDontSeeText($item->name);
    }

    //ID6-1 search
    public function test_index_filters_items_by_keyword(): void
    {
        //testitems
        $hit = Item::factory()->create([
            'name' => '黒い腕時計',
            'status' => 'on_sale',
        ]);

        $miss = Item::factory()->create([
            'name' => '白いシャツ',
            'status' => 'on_sale',
        ]);

        //get with keyword
        $response = $this->get('/?keyword=腕');

        //200
        $response->assertOk();

        //can see hit
        $response->assertSeeText($hit->name);

        //can't see missed
        $response->assertDontSeeText($miss->name);
    }

    // ID6-2
    public function test_keyword_is_kept_and_filters_mylist(): void
    {
        //testuser
        $user = User::factory()->create();

        //①testitem to hit and be like
        $likedHit = Item::factory()->create([
            'name' => '腕時計A',
            'status' => 'on_sale',
        ]);

        //②testitem to missed
        $likedMiss = Item::factory()->create([
            'name' => '指輪',
            'status' => 'on_sale',
        ]);

        //③testitem to hit and not be liked
        $unlikedHit = Item::factory()->create([
            'name' => '腕時計B',
            'status' => 'on_sale',
        ]);

        //make 2 likes
        Like::create(['user_id' => $user->id, 'item_id' => $likedHit->id]);
        Like::create(['user_id' => $user->id, 'item_id' => $likedMiss->id]);

        //get mylist with keyword
        $response = $this->actingAs($user)->get('/?tab=mylist&keyword=時');

        //200
        $response->assertOk();

        //can see ①
        $response->assertSeeText($likedHit->name);

        //can't see ②
        $response->assertDontSeeText($likedMiss->name);

        //can't see ③
        $response->assertDontSeeText($unlikedHit->name);

        // ※HTML上のinputが value="腕時" になっている想定
        $response->assertSee('name="keyword"', false);
        $response->assertSee('value="時"', false);
    }
}
