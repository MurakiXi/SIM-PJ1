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

    //ID4-1　全商品を取得できる
    public function test_index_shows_items_and_status_labels(): void
    {
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

        $response = $this->get('/');

        $response->assertOk();

        $response->assertSeeText($onSale->name);
        $response->assertSeeText($sold->name);
        $response->assertSeeText($processing->name);

        //ID4-2　購入済み商品は「Sold」と表示される
        $response->assertSeeTextInOrder([
            $sold->name,
            'Sold',
        ]);

        $response->assertSeeTextInOrder([
            $processing->name,
            'Processing',
        ]);
    }

    //ID4-3　自分が出品した商品は表示されない
    public function test_index_hides_own_items_when_authenticated(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $myItem = Item::factory()->create([
            'seller_id' => $me->id,
            'name' => '自分の商品',
        ]);

        $othersItem = Item::factory()->create([
            'seller_id' => $other->id,
            'name' => '他人の商品',
        ]);

        //1.ユーザーにログインをする
        //2.商品ページを開く
        $response = $this->actingAs($me)->get('/');

        $response->assertOk();

        $response->assertSeeText($othersItem->name);

        $response->assertDontSeeText($myItem->name);
    }

    //ID5-1　いいねした商品だけが表示される
    public function test_mylist_shows_only_liked_items_when_authenticated(): void
    {
        $user = User::factory()->create();

        $liked1 = Item::factory()->create(['name' => 'いいね1']);
        $liked2 = Item::factory()->create(['name' => 'いいね2']);
        $notLiked = Item::factory()->create(['name' => 'いいねしてない']);

        Like::create(['user_id' => $user->id, 'item_id' => $liked1->id]);
        Like::create(['user_id' => $user->id, 'item_id' => $liked2->id]);

        //1.ユーザーにログインをする
        //2.マイリストページを開く
        $response = $this->actingAs($user)->get('/?tab=mylist');

        $response->assertOk();

        $response->assertSeeText($liked1->name);
        $response->assertSeeText($liked2->name);

        $response->assertDontSeeText($notLiked->name);
    }

    // ID5-2:購入済み商品は「Sold」と表示される
    public function test_mylist_shows_sold_label_for_sold_item(): void
    {
        $user = User::factory()->create();

        $soldItem = Item::factory()->create([
            'name' => '売り切れ商品',
            'status' => 'sold',
        ]);

        Like::create([
            'user_id' => $user->id,
            'item_id' => $soldItem->id,
        ]);
        //1.ユーザーにログインをする
        //2.マイリストページを開く
        $response = $this->actingAs($user)->get('/?tab=mylist');

        $response->assertOk();
        $response->assertSeeText($soldItem->name);

        //3.購入済み商品を確認する
        $response->assertSeeTextInOrder([
            $soldItem->name,
            'Sold',
        ]);
    }

    //ID5-3　未認証の場合は何も表示されない
    public function test_mylist_for_guest_redirects_to_login(): void
    {
        //1.マイリストページを開く
        $response = $this->get('/?tab=mylist');

        $response->assertStatus(302);
        //ログインページへ遷移する(商品は表示されない)
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    //ID6-1 search
    public function test_index_filters_items_by_keyword(): void
    {
        $hit = Item::factory()->create([
            'name' => '黒い腕時計',
            'status' => 'on_sale',
        ]);

        $miss = Item::factory()->create([
            'name' => '白いシャツ',
            'status' => 'on_sale',
        ]);

        //検索欄にキーワードを入力
        //検索ボタンを押す
        $response = $this->get('/?keyword=腕');

        $response->assertOk();

        $response->assertSeeText($hit->name);

        $response->assertDontSeeText($miss->name);
    }

    // ID6-2
    public function test_keyword_is_kept_and_filters_mylist(): void
    {
        $user = User::factory()->create();

        $likedHit = Item::factory()->create([
            'name' => '腕時計A',
            'status' => 'on_sale',
        ]);

        $likedMiss = Item::factory()->create([
            'name' => '指輪',
            'status' => 'on_sale',
        ]);

        $unlikedHit = Item::factory()->create([
            'name' => '腕時計B',
            'status' => 'on_sale',
        ]);

        Like::create(['user_id' => $user->id, 'item_id' => $likedHit->id]);
        Like::create(['user_id' => $user->id, 'item_id' => $likedMiss->id]);

        //1.ホームページで商品を検索
        $home = $this->actingAs($user)->get('/?keyword=時');
        $home->assertOk();
        //2.検索結果が表示される
        $home->assertSeeText($likedHit->name);
        $home->assertSeeText($unlikedHit->name);
        $home->assertDontSeeText($likedMiss->name);

        //3.マイリストページに遷移
        $mylist = $this->actingAs($user)->get('/?tab=mylist&keyword=時');
        $mylist->assertOk();
        $mylist->assertSeeText($likedHit->name);
        $mylist->assertDontSeeText($likedMiss->name);
        $mylist->assertDontSeeText($unlikedHit->name);

        $mylist->assertSee('name="keyword"', false);
        $mylist->assertSee('value="時"', false);
    }
}
