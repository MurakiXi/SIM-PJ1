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

    //ID13
    public function test_get_user_information(): void
    {
        $user = User::factory()->create(['name' => 'ユーザーテスト']);
        $user->profile_image = 'profiles/test.png';
        $user->save();

        //own items
        $item1 = $this->createItem(['seller_id' => $user->id, 'name' => '出品A']);
        $item2 = $this->createItem(['seller_id' => $user->id, 'name' => '出品B', 'status' => 'sold']);

        $res = $this->actingAs($user)->get(route('mypage'));

        $res->assertOk();

        //can see username and image
        $res->assertSee('ユーザーテスト');
        $res->assertSee('storage/profiles/test.png', false);

        //exhibit list
        $res->assertSee('出品した商品一覧');
        $res->assertSee('出品A');
        $res->assertSee('出品B');
    }

    //ID13
    public function test_bought_list_includes_only_paid(): void
    {
        $user = User::factory()->create(['name' => '購入者']);
        $user->profile_image = 'profiles/buyer.png';
        $user->save();

        $seller = User::factory()->create();

        $paidItem = $this->createItem(['seller_id' => $seller->id, 'name' => '購入済み商品', 'price' => 777]);
        $pendingItem = $this->createItem(['seller_id' => $seller->id, 'name' => '未決済商品', 'price' => 888]);

        //paid
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

        //pending
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

        //user information
        $res->assertSee('購入者');
        $res->assertSee('storage/profiles/buyer.png', false);

        //bought list
        $res->assertSee('購入した商品一覧');
        $res->assertSee('購入済み商品');
        $res->assertDontSee('未決済商品');
    }

    //ID14
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

        $res = $this->actingAs($user)->get(route('mypage.profile'));
        $res->assertOk();

        //old name
        $res->assertSee('value="旧ユーザー名"', false);

        //address
        $res->assertSee('value="100-0001"', false);
        $res->assertSee('value="東京都千代田区テスト1-1-1"', false);
        $res->assertSee('value="旧ビル101"', false);

        //image
        $res->assertSee('storage/profiles/old.png', false);
    }


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

        //302
        $patch->assertStatus(302);

        //exists on table
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

        //image exists on storage
        $freshUser = $user->fresh();
        if (!empty($freshUser->profile_image)) {
            Storage::disk('public')->assertExists($freshUser->profile_image);
        }

        //get again and can see new information
        $res = $this->actingAs($freshUser)->get(route('mypage.profile'));
        $res->assertOk();
        $res->assertSee('value="新ユーザー名"', false);
        $res->assertSee('value="150-0001"', false);
        $res->assertSee('value="東京都渋谷区テスト2-2-2"', false);
        $res->assertSee('value="新ビル202"', false);
    }
}
