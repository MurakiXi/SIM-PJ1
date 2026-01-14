<?php

namespace Tests\Feature\Purchase;

use Tests\TestCase;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Address;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

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

    public function test_id10_purchase_flow_marks_item_sold_and_shows_in_mypage_buy(): void
    {
        $this->withoutExceptionHandling();

        Carbon::setTestNow(Carbon::parse('2026-01-13 12:00:00'));

        [$buyer, $address] = $this->makeBuyerWithAddress();
        [, $item] = $this->makeSellerAndItem();

        // Stripe::Session::create をモック（外部通信を潰す）
        $sessionId = 'cs_test_123';
        $checkoutUrl = 'https://stripe.test/checkout/cs_test_123';

        $stripeSession = Mockery::mock('alias:Stripe\\Checkout\\Session');
        $stripeSession
            ->shouldReceive('create')
            ->once()
            ->andReturn((object)[
                'id'  => $sessionId,
                'url' => $checkoutUrl,
            ]);

        $response = $this->actingAs($buyer)
            ->from(route('purchase.show', $item))
            ->post(route('purchase.checkout', $item), [
                'payment_method' => 'card',
                'address_id' => $address->id,
            ]);

        $response->assertSessionHasNoErrors(); // まずここで落ちるなら原因はバリデーション/購入不可
        $response->assertRedirect($checkoutUrl);


        // 注文が作られ、item が processing になっている（購入処理の中間成果）
        $this->assertDatabaseHas('orders', [
            'item_id' => $item->id,
            'buyer_id' => $buyer->id,
            'payment_method' => 'card',
            'payment_status' => 'pending',
            'stripe_session_id' => $sessionId,
            'ship_postal_code' => $address->postal_code,
            'ship_address' => $address->address,
            'ship_building' => $address->building,
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'status' => 'processing',
        ]);

        // 2) success（Stripeから戻った想定）で「paid」にする
        $stripeSession
            ->shouldReceive('retrieve')
            ->once()
            ->with($sessionId)
            ->andReturn((object)[
                'payment_status' => 'paid',
            ]);

        $success = $this->actingAs($buyer)->get(
            route('purchase.success', $item) . '?session_id=' . $sessionId
        );

        $success->assertStatus(302);
        $success->assertRedirect(route('items.index'));

        $order = Order::where('stripe_session_id', $sessionId)->firstOrFail();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'status' => 'sold',
        ]);

        // 3) 商品一覧で Sold が出る（要件の見える化）
        $index = $this->get(route('items.index'));
        $index->assertOk();
        $index->assertSee($item->name);
        $index->assertSee('Sold');

        // 4) マイページ購入一覧に載る
        $mypage = $this->actingAs($buyer)->get(route('mypage', ['page' => 'buy']));
        $mypage->assertOk();
        $mypage->assertSee($item->name);
    }

    public function test_id11_payment_preview_elements_exist_on_purchase_page(): void
    {
        [$buyer, $address] = $this->makeBuyerWithAddress();
        [, $item] = $this->makeSellerAndItem();

        $res = $this->actingAs($buyer)->get(route('purchase.show', $item));
        $res->assertOk();

        // PHPUnitではJSの「変更反映」自体は再現しづらいので、
        // 反映の仕掛け（select/preview/script）が存在することを担保する
        $res->assertSee('id="payment_method"', false);
        $res->assertSee('id="payment_method_preview"', false);
        $res->assertSee('payment-select.js', false);

        // プルダウンの選択肢があること
        $res->assertSee('コンビニ払い');
        $res->assertSee('カード支払い');

        // hidden の address_id が入っていること（住所初期値）
        $res->assertSee('name="address_id"', false);
        $res->assertSee('value="' . $address->id . '"', false);
    }

    public function test_id12_updated_address_is_reflected_and_used_in_order_shipping_fields(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-13 12:00:00'));

        [$buyer, $address] = $this->makeBuyerWithAddress([
            'postal_code' => '111-1111',
            'address' => '東京都港区1-1-1',
            'building' => '旧ビル',
        ]);
        [, $item] = $this->makeSellerAndItem();

        // 1) 住所変更
        $update = $this->actingAs($buyer)->patch(route('purchase.address.update', $item), [
            'postal_code' => '222-2222',
            'address' => '東京都新宿区2-2-2',
            'building' => '新ビル202',
        ]);

        $update->assertStatus(302);
        $update->assertRedirect(route('purchase.show', $item));

        $buyer = $buyer->fresh();

        // 2) 購入画面に反映されている
        $show = $this->actingAs($buyer)->get(route('purchase.show', $item));
        $show->assertOk();
        $show->assertSee('〒222-2222');
        $show->assertSee('東京都新宿区2-2-2');
        $show->assertSee('新ビル202');

        $newAddress = $buyer->fresh()->address;

        // 3) その住所で購入 → orders.ship_* に入る
        $sessionId = 'cs_test_456';
        $checkoutUrl = 'https://stripe.test/checkout/cs_test_456';

        $stripeSession = Mockery::mock('alias:Stripe\\Checkout\\Session');
        $stripeSession->shouldReceive('create')->once()->andReturn((object)[
            'id'  => $sessionId,
            'url' => $checkoutUrl,
        ]);

        $checkout = $this->actingAs($buyer)->post(route('purchase.checkout', $item), [
            'payment_method' => 'card',
            'address_id' => $newAddress->id,
        ]);

        $checkout->assertStatus(302);
        $checkout->assertRedirect($checkoutUrl);

        $this->assertDatabaseHas('orders', [
            'buyer_id' => $buyer->id,
            'item_id' => $item->id,
            'ship_postal_code' => '222-2222',
            'ship_address' => '東京都新宿区2-2-2',
            'ship_building' => '新ビル202',
        ]);
    }
}
