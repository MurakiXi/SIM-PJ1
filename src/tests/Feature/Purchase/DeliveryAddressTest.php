<?php

namespace Tests\Feature\Purchase;

use App\Models\Item;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class DeliveryAddressTest extends TestCase
{
    use RefreshDatabase;

    private function createItemForSale(User $seller): Item
    {
        return Item::create([
            'seller_id'   => $seller->id,
            'name'        => 'テスト商品',
            'brand'       => null,
            'description' => '説明',
            'price'       => 1000,
            'image_path'  => 'items/test.jpg',
            'status'      => 'on_sale',
            'condition'   => 1,
        ]);
    }

    /** @test */
    public function updated_address_is_reflected_on_purchase_page(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = $this->createItemForSale($seller);

        // ※fillable事故を避けるため、必ずリレーション経由で作るのが堅いでございます
        $buyer->address()->create([
            'postal_code' => '100-0001',
            'address'     => '東京都千代田区テスト1-1-1',
            'building'    => '旧ビル101',
        ]);

        // 旧住所が購入画面に表示されている
        $before = $this->actingAs($buyer)->get(route('purchase.show', $item));
        $before->assertOk();
        $before->assertSee('〒100-0001');
        $before->assertSee('東京都千代田区テスト1-1-1');
        $before->assertSee('旧ビル101');

        // 住所変更
        $patch = $this->actingAs($buyer)->patch(route('purchase.address.update', $item), [
            'postal_code' => '150-0001',
            'address'     => '東京都渋谷区テスト2-2-2',
            'building'    => '新ビル202',
        ]);

        // updateAddressの仕様どおり、購入画面へ戻る
        $patch->assertRedirect(route('purchase.show', $item));

        $this->assertDatabaseHas('addresses', [
            'user_id'     => $buyer->id,
            'postal_code' => '150-0001',
            'address'     => '東京都渋谷区テスト2-2-2',
            'building'    => '新ビル202',
        ]);


        // 購入画面に新住所が反映されている
        $after = $this->actingAs($buyer->fresh())->get(route('purchase.show', $item));
        $after->assertOk();
        $after->assertSee('〒150-0001');
        $after->assertSee('東京都渋谷区テスト2-2-2');
        $after->assertSee('新ビル202');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function checkout_saves_shipping_address_snapshot_to_orders(): void
    {
        // Stripe secret が null だと setApiKey が嫌がる環境もあるので、保険で入れる
        config(['services.stripe.secret' => 'sk_test_dummy']);

        // Stripeの静的呼び出しをモック（外部通信を完全に断つ）
        $stripe = Mockery::mock('alias:Stripe\Checkout\Session');
        $stripe->shouldReceive('create')
            ->once()
            ->andReturn((object)[
                'id'  => 'cs_test_123',
                'url' => 'https://example.test/stripe',
            ]);

        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = $this->createItemForSale($seller);

        // 住所を登録（= checkoutで参照する address_id を得る）
        $address = $buyer->address()->create([
            'postal_code' => '150-0001',
            'address'     => '東京都渋谷区テスト2-2-2',
            'building'    => '新ビル202',
        ]);

        // 購入（checkout）を叩く
        $response = $this->actingAs($buyer)->post(route('purchase.checkout', $item), [
            'payment_method' => 'card',
            'address_id'     => $address->id,
        ]);

        // StripeのURLへ飛ばす想定（redirect()->away）
        $response->assertRedirect('https://example.test/stripe');

        // ordersに「配送先住所のスナップショット」が保存されていること
        $this->assertDatabaseHas('orders', [
            'item_id'          => $item->id,
            'buyer_id'         => $buyer->id,
            'payment_method'   => 'card',
            'ship_postal_code' => '150-0001',
            'ship_address'     => '東京都渋谷区テスト2-2-2',
            'ship_building'    => '新ビル202',
            'payment_status'   => 'pending',
        ]);
    }
}
