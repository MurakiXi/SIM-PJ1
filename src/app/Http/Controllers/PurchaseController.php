<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PurchaseRequest;
use App\Http\Requests\AddressRequest;
use App\Models\Item;
use App\Models\Order;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class PurchaseController extends Controller
{
    //
    public function purchase(Item $item, Request $request)
    {
        $item->releaseProcessingIfExpired();
        $item->refresh();

        $user = $request->user();

        if ($item->seller_id === $user->id) {
            abort(403);
        }

        if ($item->status === 'sold') {
            abort(404);
        }

        if ($item->activeOrder()->exists()) {
            abort(404);
        }
        $address = $user->address;

        $paymentMethods = [
            '' => '選択してください',
            'convenience_store' => 'コンビニ払い',
            'card' => 'カード支払い',
        ];

        return view('purchase.show', compact('item', 'address', 'paymentMethods'));
    }

    public function checkout(PurchaseRequest $request, Item $item)
    {
        $item->releaseProcessingIfExpired();
        $item->refresh();

        $user = $request->user();

        if ($item->seller_id === $user->id) {
            abort(403);
        }

        if ($item->status !== 'on_sale' || $item->activeOrder()->exists()) {
            return back()->withErrors(['purchase' => 'この商品は購入できません。']);
        }

        $validated = $request->validated();

        $paymentMethod = $validated['payment_method'];
        $stripeMethod  = $paymentMethod === 'card' ? 'card' : 'konbini';

        $address = Address::where('id', $validated['address_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        Stripe::setApiKey(config('services.stripe.secret'));

        $reservedUntil = now()->addMinutes(30);

        $order = DB::transaction(function () use ($user, $item, $paymentMethod, $address, $reservedUntil) {
            $lockedItem = Item::whereKey($item->id)->lockForUpdate()->firstOrFail();

            if ($lockedItem->status !== 'on_sale' || $lockedItem->activeOrder()->exists()) {
                throw new \RuntimeException('This item is not available.');
            }

            $lockedItem->update([
                'status' => 'processing',
                'processing_expires_at' => $reservedUntil,
            ]);

            return Order::create([
                'item_id'           => $lockedItem->id,
                'buyer_id'          => $user->id,
                'payment_method'    => $paymentMethod,
                'stripe_session_id' => null,
                'ship_postal_code'  => $address->postal_code,
                'ship_address'      => $address->address,
                'ship_building'     => $address->building,
                'price_at_purchase' => $lockedItem->price,
                'payment_status'    => 'pending',
                'reserved_until'    => $reservedUntil,
            ]);
        });

        try {
            $session = StripeSession::create([
                'mode' => 'payment',
                'payment_method_types' => [$stripeMethod],
                'expires_at' => $reservedUntil->timestamp,
                'customer_email' => $user->email,
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'item_id'  => (string) $item->id,
                    'buyer_id' => (string) $user->id,
                ],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'jpy',
                        'unit_amount' => $order->price_at_purchase,
                        'product_data' => [
                            'name' => $item->name,
                        ],
                    ],
                ]],
                'success_url' => route('purchase.success', $item) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('purchase.cancel',  $item),
            ]);

            $order->update([
                'stripe_session_id' => $session->id,
            ]);

            return redirect()->away($session->url);
        } catch (\Throwable $e) {

            DB::transaction(function () use ($order) {
                $lockedItem = Item::whereKey($order->item_id)->lockForUpdate()->first();

                if ($lockedItem && $lockedItem->status === 'processing') {
                    $lockedItem->update([
                        'status' => 'on_sale',
                        'processing_expires_at' => null,
                    ]);
                }

                $order->update(['payment_status' => 'canceled']);
            });

            throw $e;
        }
    }



    public function cancel(Request $request, Item $item)
    {
        $user = $request->user();

        $order = Order::where('item_id', $item->id)
            ->where('buyer_id', $user->id)
            ->where('payment_status', 'pending')
            ->latest('id')
            ->first();


        // no order → top
        if (! $order) {
            return redirect()->route('items.index');
        }

        // inquiry Stripe
        if ($order->stripe_session_id) {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($order->stripe_session_id);

            if (($session->payment_status ?? null) === 'paid') {
                return redirect()->route('items.index')
                    ->withErrors(['purchase' => '決済が完了しているためキャンセルできません。']);
            }
        }

        $wasCanceled = false;

        DB::transaction(function () use ($item, $order, &$wasCanceled) {
            $lockedItem  = Item::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->payment_status === 'paid') {
                return;
            }

            if ($lockedItem->status === 'processing') {
                $lockedItem->update([
                    'status' => 'on_sale',
                    'processing_expires_at' => null,
                ]);
            }

            $lockedOrder->update([
                'payment_status' => 'canceled',
                'canceled_at' => now(),
                'reserved_until' => null,
            ]);

            $wasCanceled = true;
        });

        return $wasCanceled
            ? redirect()->route('items.index')->with('message', '購入をキャンセルしました。')
            : redirect()->route('items.index')->withErrors(['purchase' => '決済が完了しているためキャンセルできません。']);
    }

    public function success(Request $request, Item $item)
    {
        $user = $request->user();
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('items.index')
                ->withErrors(['purchase' => '決済情報が取得できませんでした。']);
        }

        $order = Order::where('stripe_session_id', $sessionId)
            ->where('buyer_id', $user->id)
            ->where('item_id', $item->id)
            ->firstOrFail();

        if ($order->payment_status === 'paid') {
            return redirect()->route('items.index')->with('message', '購入が完了しました。');
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        if (($session->payment_status ?? null) === 'paid') {
            DB::transaction(function () use ($order, $item) {
                $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                $item  = Item::whereKey($item->id)->lockForUpdate()->firstOrFail();

                if ($order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                        'reserved_until' => null,
                    ]);
                }

                if ($item->status !== 'sold') {
                    $item->update([
                        'status' => 'sold',
                        'processing_expires_at' => null,
                    ]);
                }
            });

            return redirect()->route('items.index')->with('message', '購入が完了しました。');
        }

        return redirect()->route('items.index')->with(
            'message',
            '決済を受け付けました。お支払い完了後に購入が確定します。'
        );
    }


    public function editAddress(Item $item, Request $request)
    {
        $address = $request->user()->address;

        return view('purchase.address', compact('item', 'address'));
    }

    public function updateAddress(AddressRequest $request, Item $item)
    {
        $user = $request->user();

        Address::updateOrCreate(
            ['user_id' => $user->id],
            [
                'postal_code' => $request->validated()['postal_code'],
                'address'     => $request->validated()['address'],
                'building'    => $request->validated()['building'] ?? null,
            ]
        );

        return redirect()->route('purchase.show', $item);
    }
}
