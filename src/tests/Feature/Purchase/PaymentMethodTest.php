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

    /** @test */
    public function purchase_page_has_payment_select_preview_and_js(): void
    {
        $seller = User::factory()->create();
        $buyer  = $this->createBuyerWithAddress();
        $item   = $this->createOnSaleItem($seller);

        $response = $this->actingAs($buyer)->get(route('purchase.show', $item));

        $response->assertOk();

        // select がある（HTMLを文字列で確認）
        $response->assertSee('id="payment_method"', false);

        // option が揃っている
        $response->assertSee('value="convenience_store"', false);
        $response->assertSee('コンビニ払い');
        $response->assertSee('value="card"', false);
        $response->assertSee('カード支払い');

        // 小計側のプレビュー枠がある
        $response->assertSee('id="payment_method_preview"', false);
        $response->assertSee('選択してください');

        // JSが読み込まれている（assetのパスは環境で変わるので文字列でざっくり）
        $response->assertSee('js/payment-select.js', false);
    }

    /** @test */
    public function selected_payment_method_is_kept_after_validation_error(): void
    {
        // 住所を作らずに購入POST → address_id必須で落とす（“戻った時の反映”を検証）
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = $this->createOnSaleItem($seller);

        $response = $this->actingAs($buyer)->from(route('purchase.show', $item))->post(
            route('purchase.checkout', $item),
            [
                'payment_method' => 'card',
                // address_id を敢えて送らない
            ]
        );

        $response->assertRedirect(route('purchase.show', $item));
        $response->assertSessionHasErrors(['address_id']);

        // 戻り先の画面で、card が選択状態＆プレビューも card 表示になっていることを確認
        $page = $this->actingAs($buyer)->get(route('purchase.show', $item));

        // ↓この assert が通るように、blade側で old('payment_method') を使う必要がございます（後述）
        $page->assertSee('value="card" selected', false);
        $page->assertSee('>カード支払い<', false);
    }

    /** @test */
    public function invalid_payment_method_is_rejected(): void
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
