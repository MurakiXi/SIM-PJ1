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

    /** @test */
    public function mypage_sell_shows_user_name_and_profile_image_and_selling_items(): void
    {
        $user = User::factory()->create(['name' => '旦那テスト']);
        $user->profile_image = 'profiles/test.png';
        $user->save();

        // 自分が出品した商品
        $item1 = $this->createItem(['seller_id' => $user->id, 'name' => '出品A']);
        $item2 = $this->createItem(['seller_id' => $user->id, 'name' => '出品B', 'status' => 'sold']);

        $res = $this->actingAs($user)->get(route('mypage'));

        $res->assertOk();

        // ユーザー情報
        $res->assertSee('旦那テスト');
        $res->assertSee('storage/profiles/test.png', false);

        // 出品一覧（タブはデフォルトsell）
        $res->assertSee('出品した商品一覧');
        $res->assertSee('出品A');
        $res->assertSee('出品B');
    }

    /** @test */
    public function mypage_buy_shows_only_paid_orders_items(): void
    {
        $user = User::factory()->create(['name' => '購入者']);
        $user->profile_image = 'profiles/buyer.png';
        $user->save();

        $seller = User::factory()->create();

        $paidItem = $this->createItem(['seller_id' => $seller->id, 'name' => '購入済み商品', 'price' => 777]);
        $pendingItem = $this->createItem(['seller_id' => $seller->id, 'name' => '未決済商品', 'price' => 888]);

        // paid（表示される）
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

        // pending（表示されない）
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

        $res = $this->actingAs($user)->get(route('mypage', ['page' => 'buy']));

        $res->assertOk();

        // ユーザー情報（buyタブでも出るべき）
        $res->assertSee('購入者');
        $res->assertSee('storage/profiles/buyer.png', false);

        // 購入一覧（paidのみ）
        $res->assertSee('購入した商品一覧');
        $res->assertSee('購入済み商品');
        $res->assertDontSee('未決済商品');
    }

    /** @test */
    public function profile_edit_page_prefills_user_and_address_values(): void
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

        $res = $this->actingAs($user)->get(route('mypage.profile'));
        $res->assertOk();

        // name の初期値（input value に入っている想定）
        $res->assertSee('value="旧ユーザー名"', false);

        // 郵便番号・住所・建物（こちらも value に入っている想定）
        $res->assertSee('value="100-0001"', false);
        $res->assertSee('value="東京都千代田区テスト1-1-1"', false);
        $res->assertSee('value="旧ビル101"', false);

        // 画像は file input の value ではなく、「現在の画像が分かる表示」を見るのが筋
        // 例：<img src="...storage/profiles/old.png">
        $res->assertSee('storage/profiles/old.png', false);
    }

    /** @test */
    public function profile_update_persists_user_and_address_and_reflects_as_prefill(): void
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
            'profile_image' => $file, // ※フォームの name 属性に合わせる
        ]);

        // どこへ戻す設計かは実装次第：profile編集に戻る or mypageに戻る
        $patch->assertStatus(302);

        // DB反映（users）
        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'name' => '新ユーザー名',
        ]);

        // DB反映（addresses）
        $this->assertDatabaseHas('addresses', [
            'user_id'     => $user->id,
            'postal_code' => '150-0001',
            'address'     => '東京都渋谷区テスト2-2-2',
            'building'    => '新ビル202',
        ]);

        // 画像保存の検証：保存パスがDBに入る実装ならそれも確認
        $freshUser = $user->fresh();
        if (!empty($freshUser->profile_image)) {
            Storage::disk('public')->assertExists($freshUser->profile_image);
        }

        // 再GETでプリフィル反映（キャッシュ罠回避で fresh）
        $res = $this->actingAs($freshUser)->get(route('mypage.profile'));
        $res->assertOk();
        $res->assertSee('value="新ユーザー名"', false);
        $res->assertSee('value="150-0001"', false);
        $res->assertSee('value="東京都渋谷区テスト2-2-2"', false);
        $res->assertSee('value="新ビル202"', false);
    }
}
