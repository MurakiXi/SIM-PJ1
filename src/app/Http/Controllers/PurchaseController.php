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

        if ($item->order()->exists()) {
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

        if ($item->status !== 'on_sale' || $item->order()->exists()) {
            return back()->withErrors(['purchase' => 'この商品は購入できません。']);
        }

        $validated = $request->validated();

        $paymentMethod = $validated['payment_method'];
        $stripeMethod  = $paymentMethod === 'card' ? 'card' : 'konbini';

        $address = Address::where('id', $validated['address_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = StripeSession::create([
            'mode' => 'payment',
            'payment_method_types' => [$stripeMethod],
            'customer_email' => $user->email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'jpy',
                    'unit_amount' => $item->price,
                    'product_data' => [
                        'name' => $item->name,
                    ],
                ],
            ]],
            'success_url' => route('purchase.success', $item) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('purchase.cancel', $item),
        ]);

        DB::transaction(function () use ($user, $item, $paymentMethod, $address, $session) {
            Item::whereKey($item->id)->lockForUpdate()->first();
            $item->update([
                'status' => 'processing',
                'processing_expires_at' => now()->addMinutes(30),
            ]);

            Order::create([
                'item_id'           => $item->id,
                'buyer_id'          => $user->id,
                'payment_method'    => $paymentMethod,
                'stripe_session_id' => $session->id,
                'ship_postal_code'  => $address->postal_code,
                'ship_address'      => $address->address,
                'ship_building'     => $address->building,

                'price_at_purchase' => $item->price,
                'payment_status'    => 'pending',
                'reserved_until'    => now()->addMinutes(30),
            ]);
        });

        return redirect()->away($session->url);
    }

    public function cancel(Request $request, Item $item)
    {
        $user = $request->user();

        $order = Order::where('item_id', $item->id)
            ->where('buyer_id', $user->id)
            ->where('payment_status', 'pending')
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

        DB::transaction(function () use ($item, $order) {
            $lockedItem = Item::whereKey($item->id)->lockForUpdate()->first();

            if ($lockedItem->status === 'processing') {
                $lockedItem->update([
                    'status' => 'on_sale',
                    'processing_expires_at' => null,
                ]);
            }
            $order->update(['payment_status' => 'canceled']);
            $order->delete();
        });

        return redirect()->route('items.index')->with('message', '購入をキャンセルしました。');
    }

    public function success(Request $request, Item $item)
    {
        $user = $request->user();
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            abort(400, 'session_id is required');
        }

        $order = Order::where('item_id', $item->id)
            ->where('buyer_id', $user->id)
            ->firstOrFail();


        if ($order->stripe_session_id !== $sessionId) {
            abort(403);
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $session = StripeSession::retrieve($sessionId);

        if (($session->payment_status ?? null) === 'paid') {
            DB::transaction(function () use ($item) {
                $item->update([
                    'status' => 'sold',
                    'processing_expires_at' => null,
                ]);
            });

            return redirect()->route('items.index')->with('message', '決済が完了しました。');
        }

        return redirect()->route('items.index')->with('message', '決済を受け付けました。支払い完了の反映までお待ちください。');
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
