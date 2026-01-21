<?php

namespace Tests\Feature\Purchase;

use App\Models\Item;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Carbon;

class DeliveryAddressTest extends TestCase
{
    use RefreshDatabase;

    private function makeBuyerWithAddress(array $addressOverrides = []): array
    {
        $buyer = User::factory()->create([
            'email' => 'buyer@example.com',
        ]);

        $buyer->address()->create(array_merge([
            'postal_code' => '123-4567',
            'address'     => '東京都千代田区1-2-3',
            'building'    => 'テストビル101',
        ], $addressOverrides));

        $buyer->refresh();
        return [$buyer, $buyer->address];
    }


    private function makeSellerAndItem(): array
    {
        $seller = User::factory()->create([
            'email' => 'seller@example.com',
        ]);

        $item = Item::factory()->create([
            'seller_id' => $seller->id,
            'name' => '購入テスト商品',
            'status' => 'on_sale',
        ]);

        return [$seller, $item];
    }

    private function updateShippingAddress(User $buyer, Item $item, array $payload): void
    {
        //1.ユーザーにログインする
        $this->actingAs($buyer)
            ->patch(route('purchase.address.update', $item), $payload)
            ->assertRedirect(route('purchase.show', $item));
    }

    //ID12-1 送付先住所変更画面にて登録した住所が商品購入画面に反映されている
    public function test_updated_address_is_reflected_on_purchase_page(): void
    {
        [$buyer] = $this->makeBuyerWithAddress([
            'postal_code' => '111-1111',
            'address' => '東京都港区1-1-1',
            'building' => '旧ビル',
        ]);
        [, $item] = $this->makeSellerAndItem();

        //2.送付先住所変更画面で住所を登録する
        $this->updateShippingAddress($buyer, $item, [
            'postal_code' => '222-2222',
            'address' => '東京都新宿区2-2-2',
            'building' => '新ビル202',
        ]);

        //3.商品購入画面を再度開く
        $show = $this->actingAs($buyer)->get(route('purchase.show', $item));
        $show->assertOk();
        $show->assertSee('222-2222');
        $show->assertSee('東京都新宿区2-2-2');
        $show->assertSee('新ビル202');
    }

    //ID12-2 購入した商品に送付先住所が紐づいて登録される
    public function test_updated_address_is_saved_to_order_shipping_fields(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-13 12:00:00'));

        [$buyer] = $this->makeBuyerWithAddress([
            'postal_code' => '111-1111',
            'address' => '東京都港区1-1-1',
            'building' => '旧ビル',
        ]);
        [, $item] = $this->makeSellerAndItem();

        //2.送付先住所変更画面で住所を登録する
        $this->updateShippingAddress($buyer, $item, [
            'postal_code' => '222-2222',
            'address' => '東京都新宿区2-2-2',
            'building' => '新ビル202',
        ]);

        $newAddress = $buyer->fresh()->address;

        $sessionId = 'cs_test_456';
        $checkoutUrl = 'https://stripe.test/checkout/cs_test_456';

        $stripeSession = Mockery::mock('alias:Stripe\\Checkout\\Session');
        $stripeSession->shouldReceive('create')->once()->andReturn((object)[
            'id'  => $sessionId,
            'url' => $checkoutUrl,
        ]);

        //3.商品を購入する
        $this->actingAs($buyer)->post(route('purchase.checkout', $item), [
            'payment_method' => 'card',
            'address_id' => $newAddress->id,
        ])->assertRedirect($checkoutUrl);

        $this->assertDatabaseHas('orders', [
            'buyer_id' => $buyer->id,
            'item_id' => $item->id,
            'ship_postal_code' => '222-2222',
            'ship_address' => '東京都新宿区2-2-2',
            'ship_building' => '新ビル202',
        ]);
    }
}
