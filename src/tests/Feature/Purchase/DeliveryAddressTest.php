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

    public function test_updated_address_is_reflected_on_purchase(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = $this->createItemForSale($seller);

        $buyer->address()->create([
            'postal_code' => '100-0001',
            'address'     => '東京都千代田区テスト1-1-1',
            'building'    => '旧ビル101',
        ]);

        //purchse view shows old address
        $before = $this->actingAs($buyer)->get(route('purchase.show', $item));
        $before->assertOk();
        $before->assertSee('〒100-0001');
        $before->assertSee('東京都千代田区テスト1-1-1');
        $before->assertSee('旧ビル101');

        //change address
        $patch = $this->actingAs($buyer)->patch(route('purchase.address.update', $item), [
            'postal_code' => '150-0001',
            'address'     => '東京都渋谷区テスト2-2-2',
            'building'    => '新ビル202',
        ]);

        //redirect to purchase.show
        $patch->assertRedirect(route('purchase.show', $item));

        $this->assertDatabaseHas('addresses', [
            'user_id'     => $buyer->id,
            'postal_code' => '150-0001',
            'address'     => '東京都渋谷区テスト2-2-2',
            'building'    => '新ビル202',
        ]);


        //new address reflected
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

    //
    public function test_checkout_saves_shipping_address_snapshot_to_orders(): void
    {
        // Stripe secret が null だと setApiKey が嫌がる環境もあるので、保険で入れる
        config(['services.stripe.secret' => 'sk_test_dummy']);

        //stop external communication
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

        //memorize address
        $address = $buyer->address()->create([
            'postal_code' => '150-0001',
            'address'     => '東京都渋谷区テスト2-2-2',
            'building'    => '新ビル202',
        ]);

        //checkout
        $response = $this->actingAs($buyer)->post(route('purchase.checkout', $item), [
            'payment_method' => 'card',
            'address_id'     => $address->id,
        ]);

        //redirect to stripe
        $response->assertRedirect('https://example.test/stripe');

        //orders has address snapshot
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
