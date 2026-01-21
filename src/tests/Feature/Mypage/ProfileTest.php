<?php

namespace Tests\Feature\Mypage;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function createItem(array $overrides = []): Item
    {
        return Item::create(array_merge([
            'seller_id'   => User::factory()->create()->id,
            'name'        => 'テスト商品',
            'brand'       => null,
            'description' => '説明',
            'price'       => 1000,
            'image_path'  => 'items/test.jpg',
            'status'      => 'on_sale',
            'condition'   => 1,
        ], $overrides));
    }

    private function createOrder(User $buyer, Item $item, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'item_id'           => $item->id,
            'buyer_id'          => $buyer->id,
            'payment_method'    => 'card',
            'stripe_session_id' => 'cs_test_' . uniqid(),
            'ship_postal_code'  => '150-0001',
            'ship_address'      => '東京都渋谷区',
            'ship_building'     => 'テストビル',
            'price_at_purchase' => $item->price,
            'payment_status'    => 'paid',
            'reserved_until'    => null,
            'paid_at'           => now(),
        ], $overrides));
    }


    //ID13　必要な情報が取得できる（プロフィール画像、ユーザー名、出品した商品一覧、購入した商品一覧）
    public function test_get_user_information(): void
    {
        $user = User::factory()->create(['name' => 'ユーザーテスト']);
        $user->profile_image = 'profiles/test.png';
        $user->save();

        $this->createItem(['seller_id' => $user->id, 'name' => '出品A']);
        $this->createItem(['seller_id' => $user->id, 'name' => '出品B', 'status' => 'sold']);

        $otherSeller = User::factory()->create();
        $paidItem = $this->createItem(['seller_id' => $otherSeller->id, 'name' => '購入済み商品']);
        Order::create([
            'item_id' => $paidItem->id,
            'buyer_id' => $user->id,
            'payment_method' => 'card',
            'stripe_session_id' => 'cs_test_paid_sell_1',
            'ship_postal_code' => '150-0001',
            'ship_address' => '東京都渋谷区',
            'ship_building' => 'ビル',
            'price_at_purchase' => $paidItem->price,
            'payment_status' => 'paid',
            'reserved_until' => now()->addMinutes(30),
            'paid_at' => now(),
        ]);

        //1.ユーザーにログインする
        //2.プロフィールページを開く(出品一覧)
        $res = $this->actingAs($user)->get(route('mypage', ['page' => 'sell']));

        $res->assertOk();

        $sellHref = 'href="' . route('mypage', ['page' => 'sell']) . '"';
        $buyHref  = 'href="' . route('mypage', ['page' => 'buy']) . '"';

        $res->assertSeeInOrder([
            $sellHref,
            'profile__tab-link is-active',
            'aria-current="page"',
            '出品した商品',
        ], false);

        $res->assertSeeInOrder([
            $buyHref,
            'aria-current="false"',
            '購入した商品',
        ], false);

        $res->assertDontSee($buyHref . ' class="profile__tab-link is-active"', false);

        $res->assertSee('ユーザーテスト');
        $res->assertSee('storage/profiles/test.png', false);

        $res->assertSee('出品した商品');
        $res->assertSee('購入した商品');

        $res->assertSee('出品A');
        $res->assertSee('出品B');
        $res->assertDontSee('購入済み商品');
    }


    //ID13　必要な情報が取得できる（プロフィール画像、ユーザー名、出品した商品一覧、購入した商品一覧）
    public function test_bought_list_includes_only_paid(): void
    {
        $user = User::factory()->create(['name' => '購入者']);
        $user->profile_image = 'profiles/buyer.png';
        $user->save();

        $seller = User::factory()->create();

        $paidItem = $this->createItem(['seller_id' => $seller->id, 'name' => '購入済み商品', 'price' => 777]);
        $pendingItem = $this->createItem(['seller_id' => $seller->id, 'name' => '未決済商品', 'price' => 888]);

        Order::create([
            'item_id'          => $paidItem->id,
            'buyer_id'         => $user->id,
            'payment_method'   => 'card',
            'stripe_session_id' => 'cs_test_paid_1',
            'ship_postal_code' => '150-0001',
            'ship_address'     => '東京都渋谷区',
            'ship_building'    => 'ビル',
            'price_at_purchase' => 777,
            'payment_status'   => 'paid',
            'reserved_until'   => now()->addMinutes(10),
            'paid_at'          => now(),
        ]);

        Order::create([
            'item_id'          => $pendingItem->id,
            'buyer_id'         => $user->id,
            'payment_method'   => 'card',
            'stripe_session_id' => 'cs_test_pending_1',
            'ship_postal_code' => '150-0001',
            'ship_address'     => '東京都渋谷区',
            'ship_building'    => 'ビル',
            'price_at_purchase' => 888,
            'payment_status'   => 'pending',
            'reserved_until'   => now()->addMinutes(10),
        ]);

        $this->createItem(['seller_id' => $user->id, 'name' => '自分の出品商品']);

        //1.ユーザーにログインする
        //2.プロフィールページを開く(購入一覧)
        $res = $this->actingAs($user)->get(route('mypage', ['page' => 'buy']));

        $res->assertOk();

        $sellHref = 'href="' . route('mypage', ['page' => 'sell']) . '"';
        $buyHref  = 'href="' . route('mypage', ['page' => 'buy']) . '"';

        $res->assertSeeInOrder([
            $buyHref,
            'profile__tab-link is-active',
            'aria-current="page"',
            '購入した商品',
        ], false);

        $res->assertSeeInOrder([
            $sellHref,
            'aria-current="false"',
            '出品した商品',
        ], false);

        $res->assertDontSee($sellHref . ' class="profile__tab-link is-active"', false);

        $res->assertSee('購入者');
        $res->assertSee('storage/profiles/buyer.png', false);

        $res->assertSee('出品した商品');
        $res->assertSee('購入した商品');
        $res->assertSee('page=buy', false);

        $res->assertSee('購入済み商品');
        $res->assertDontSee('未決済商品');
        $res->assertDontSee('自分の出品商品');
    }

    //ID14　変更項目が初期値として設定されていること（プロフィール画像、ユーザー名、郵便番号、住所）
    public function test_profile_edit_page_prefills_user_and_address_values(): void
    {
        $user = User::factory()->create([
            'name' => '旧ユーザー名',
            'profile_image' => 'profiles/old.png',
        ]);

        $user->address()->create([
            'postal_code' => '100-0001',
            'address'     => '東京都千代田区テスト1-1-1',
            'building'    => '旧ビル101',
        ]);

        //1.ユーザーにログインする
        //2.プロフィールページを開く
        $res = $this->actingAs($user)->get(route('mypage.profile'));
        $res->assertOk();

        //各項目の初期値が正しく表示されている
        $res->assertSee('value="旧ユーザー名"', false);
        $res->assertSee('value="100-0001"', false);
        $res->assertSee('value="東京都千代田区テスト1-1-1"', false);
        $res->assertSee('value="旧ビル101"', false);

        $res->assertSee('storage/profiles/old.png', false);
    }

    //ID14　変更項目が初期値として設定されていること（プロフィール画像、ユーザー名、郵便番号、住所）
    public function test_profile_update_persists_user_and_address_and_reflects_as_prefill(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => '旧ユーザー名',
            'profile_image' => 'profiles/old.png',
        ]);

        $user->address()->create([
            'postal_code' => '100-0001',
            'address'     => '東京都千代田区テスト1-1-1',
            'building'    => '旧ビル101',
        ]);

        $file = UploadedFile::fake()->create('new.png', 10, 'image/png');

        $patch = $this->actingAs($user)->patch(route('mypage.update'), [
            'name'        => '新ユーザー名',
            'postal_code' => '150-0001',
            'address'     => '東京都渋谷区テスト2-2-2',
            'building'    => '新ビル202',
            'profile_image' => $file,
        ]);

        $patch->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'name' => '新ユーザー名',
        ]);

        $this->assertDatabaseHas('addresses', [
            'user_id'     => $user->id,
            'postal_code' => '150-0001',
            'address'     => '東京都渋谷区テスト2-2-2',
            'building'    => '新ビル202',
        ]);

        $freshUser = $user->fresh();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'profile_image' => $freshUser->profile_image,
        ]);
        $this->assertNotEquals('profiles/old.png', $freshUser->profile_image);
        $this->assertNotEmpty($freshUser->profile_image);

        //(変更した)各項目の初期値が正しく表示されている
        $res = $this->actingAs($freshUser)->get(route('mypage.profile'));
        $res->assertOk();
        $res->assertSee('value="新ユーザー名"', false);
        $res->assertSee('value="150-0001"', false);
        $res->assertSee('value="東京都渋谷区テスト2-2-2"', false);
        $res->assertSee('value="新ビル202"', false);

        $this->assertDatabaseCount('addresses', 1);
    }
}
