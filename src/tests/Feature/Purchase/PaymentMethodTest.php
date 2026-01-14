<?php

namespace Tests\Feature\Purchase;

use App\Models\User;
use App\Models\Item;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private function createBuyerWithAddress(): User
    {
        $buyer = User::factory()->create();

        $buyer->address()->create([
            'postal_code' => '100-0001',
            'address'     => '東京都千代田区テスト1-1-1',
            'building'    => 'テストビル101',
        ]);

        return $buyer;
    }

    private function createOnSaleItem(User $seller): Item
    {
        return Item::create([
            'seller_id'            => $seller->id,
            'name'                 => 'テスト商品',
            'brand'                => 'テストブランド',
            'description'          => '説明文です',
            'price'                => 1000,
            'image_path'           => 'items/test.jpg',
            'status'               => 'on_sale',
            'condition'            => 1,
            'processing_expires_at' => null,
        ]);
    }

    //ID11(支払い方法を選ぶUIが存在する＝支払い方法選択画面が開ける)
    public function test_purchase_page_has_payment_select_preview(): void
    {
        $seller = User::factory()->create();
        $buyer  = $this->createBuyerWithAddress();
        $item   = $this->createOnSaleItem($seller);

        $response = $this->actingAs($buyer)->get(route('purchase.show', $item));

        $response->assertOk();

        //html has select
        $response->assertSee('id="payment_method"', false);

        //html has options
        $response->assertSee('value="convenience_store"', false);
        $response->assertSee('コンビニ払い');
        $response->assertSee('value="card"', false);
        $response->assertSee('カード支払い');

        //html has method preview
        $response->assertSee('id="payment_method_preview"', false);
        $response->assertSee('選択してください');

        //js is read
        $response->assertSee('js/payment-select.js', false);
    }

    //ID11(addres_id無しでpaymant_method=cardのみを送り、バリデーションエラーで戻った購入画面を再get)
    //再get後の画面にold('payment_method')としてcardが表示される＝フォーム送信で選んだ値がサーバに到達している
    public function test_selected_method_is_kept_after_validation_error(): void
    {
        //post purchase without address
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = $this->createOnSaleItem($seller);

        $response = $this->actingAs($buyer)->from(route('purchase.show', $item))->post(
            route('purchase.checkout', $item),
            [
                'payment_method' => 'card',
            ]
        );

        $response->assertRedirect(route('purchase.show', $item));
        $response->assertSessionHasErrors(['address_id']);

        //get again with 'card' selected
        $page = $this->actingAs($buyer)->get(route('purchase.show', $item));

        $page->assertSee('value="card" selected', false);
        $page->assertSee('>カード支払い<', false);
    }

    //ID11(不正値が弾かれる＝選択した支払い方法が正しく反映される)
    public function test_invalid_payment_method_is_rejected(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = $this->createOnSaleItem($seller);

        $response = $this->actingAs($buyer)->from(route('purchase.show', $item))->post(
            route('purchase.checkout', $item),
            [
                'payment_method' => 'hacked',
            ]
        );

        $response->assertRedirect(route('purchase.show', $item));
        $response->assertSessionHasErrors(['payment_method']);
    }
}
