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

    //ID10-1
    public function test_purchase_flow(): void
    {
        $this->withoutExceptionHandling();

        Carbon::setTestNow(Carbon::parse('2026-01-13 12:00:00'));

        [$buyer, $address] = $this->makeBuyerWithAddress();
        [, $item] = $this->makeSellerAndItem();

        // mock Stripe::Session::create (stop external communication)
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

        $response->assertSessionHasNoErrors();
        $response->assertRedirect($checkoutUrl);

        //order exists on table
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

        //status turns into 'processing'
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'status' => 'processing',
        ]);

        //payment status turns into 'paid'
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

        //ID10-2
        //purchase status turns into 'Sold' 
        $index = $this->get(route('items.index'));
        $index->assertOk();
        $index->assertSee($item->name);
        $index->assertSee('Sold');

        //ID10-3
        //item can be seen on bought list
        $mypage = $this->actingAs($buyer)->get(route('mypage', ['page' => 'buy']));
        $mypage->assertOk();
        $mypage->assertSee($item->name);
    }

    //ID11
    public function test_id11_payment_preview_elements_exist_on_purchase_page(): void
    {
        [$buyer, $address] = $this->makeBuyerWithAddress();
        [, $item] = $this->makeSellerAndItem();

        $res = $this->actingAs($buyer)->get(route('purchase.show', $item));
        $res->assertOk();

        //select/preview/script exists
        $res->assertSee('id="payment_method"', false);
        $res->assertSee('id="payment_method_preview"', false);
        $res->assertSee('payment-select.js', false);

        //choices exists in pulldown
        $res->assertSee('コンビニ払い');
        $res->assertSee('カード支払い');

        //address_id exists
        $res->assertSee('name="address_id"', false);
        $res->assertSee('value="' . $address->id . '"', false);
    }

    //ID12
    public function test_updated_address_is_reflected_and_used_in_order_shipping_fields(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-13 12:00:00'));

        [$buyer, $address] = $this->makeBuyerWithAddress([
            'postal_code' => '111-1111',
            'address' => '東京都港区1-1-1',
            'building' => '旧ビル',
        ]);
        [, $item] = $this->makeSellerAndItem();

        //change address
        $update = $this->actingAs($buyer)->patch(route('purchase.address.update', $item), [
            'postal_code' => '222-2222',
            'address' => '東京都新宿区2-2-2',
            'building' => '新ビル202',
        ]);

        $update->assertStatus(302);
        $update->assertRedirect(route('purchase.show', $item));

        $buyer = $buyer->fresh();

        //ID12-1
        //new address reflected on puchase view
        $show = $this->actingAs($buyer)->get(route('purchase.show', $item));
        $show->assertOk();
        $show->assertSee('〒222-2222');
        $show->assertSee('東京都新宿区2-2-2');
        $show->assertSee('新ビル202');

        $newAddress = $buyer->fresh()->address;

        //ID12-2
        //new address reflected on orders.ship
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
