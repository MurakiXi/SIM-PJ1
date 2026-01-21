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
    private function completePurchase(User $buyer, Item $item, Address $address): string
    {
        $sessionId = 'cs_test_123';
        $checkoutUrl = 'https://stripe.test/checkout/cs_test_123';

        $stripeSession = Mockery::mock('alias:Stripe\\Checkout\\Session');
        $stripeSession->shouldReceive('create')->once()->andReturn((object)[
            'id'  => $sessionId,
            'url' => $checkoutUrl,
        ]);

        //1.ユーザーにログインする
        $this->actingAs($buyer)
            //2.商品購入画面を開く
            //3.商品を選択して「購入する」ボタンを押下
            ->post(route('purchase.checkout', $item), [
                'payment_method' => 'card',
                'address_id' => $address->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect($checkoutUrl);

        $stripeSession->shouldReceive('retrieve')->once()->with($sessionId)->andReturn((object)[
            'payment_status' => 'paid',
        ]);

        $this->actingAs($buyer)
            ->get(route('purchase.success', $item) . '?session_id=' . $sessionId)
            ->assertRedirect(route('items.index'));

        return $sessionId;
    }

    //ID10-1
    public function test_user_can_purchase(): void
    {
        [$buyer, $address] = $this->makeBuyerWithAddress();
        [, $item] = $this->makeSellerAndItem();

        $sessionId = $this->completePurchase($buyer, $item, $address);

        $this->assertDatabaseHas('orders', [
            'item_id'          => $item->id,
            'buyer_id'         => $buyer->id,
            'payment_status'   => 'paid',
            'stripe_session_id' => $sessionId,
        ]);

        $this->assertDatabaseHas('items', [
            'id'     => $item->id,
            'status' => 'sold',
        ]);
    }


    //ID10-2
    public function test_purchased_item_is_shown_as_sold_on_index(): void
    {
        [$buyer, $address] = $this->makeBuyerWithAddress();
        [, $item] = $this->makeSellerAndItem();

        $this->completePurchase($buyer, $item, $address);

        $index = $this->get(route('items.index'));
        $index->assertOk();

        $index->assertSee($item->name);

        $index->assertSee('Sold');
    }

    //ID10-3
    public function test_purchased_item_appears_in_mypage_buy_list(): void
    {
        [$buyer, $address] = $this->makeBuyerWithAddress();
        [, $item] = $this->makeSellerAndItem();

        $this->completePurchase($buyer, $item, $address);

        $mypage = $this->actingAs($buyer)->get(route('mypage', ['page' => 'buy']));
        $mypage->assertOk();
        $mypage->assertSee($item->name);
    }

    //ID11 小計画面で変更が反映される(変更前要素の存在)
    public function test_payment_preview_elements_exist_on_purchase_page(): void
    {
        [$buyer, $address] = $this->makeBuyerWithAddress();
        [, $item] = $this->makeSellerAndItem();

        $res = $this->actingAs($buyer)->get(route('purchase.show', $item));
        $res->assertOk();

        $res->assertSee('id="payment_method"', false);
        $res->assertSee('name="payment_method"', false);
        $res->assertSee('id="payment_method_preview"', false);
        $res->assertSee('payment-select.js', false);

        $res->assertSee('value="convenience_store"', false);
        $res->assertSee('value="card"', false);

        $res->assertSee('name="address_id"', false);
        $res->assertSee('value="' . $address->id . '"', false);
    }

    //ID11 小計画面で変更が反映される(変更の反映)
    public function test_selected_payment_method_is_reflected_in_select(): void
    {
        [$buyer] = $this->makeBuyerWithAddress();
        [, $item] = $this->makeSellerAndItem();

        $res = $this->actingAs($buyer)
            ->withSession([
                'purchase' => [
                    'payment_method' => [
                        $item->id => 'card',
                    ],
                ],
            ])
            ->get(route('purchase.show', $item));

        $res->assertOk();

        $res->assertViewHas('selectedPayment', 'card');

        $res->assertSee('id="payment_method"', false);
        $res->assertSee('value="card"', false);
    }
}
