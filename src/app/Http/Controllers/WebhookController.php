<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\StripeEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Webhook as StripeWebhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = StripeWebhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Throwable $e) {
            return response('Invalid signature', 400);
        }

        $session = $event->data->object;

        $order = Order::where('stripe_session_id', $session->id)->first();
        if (! $order) {
            return response('Order not found yet', 500);
        }

        DB::transaction(function () use ($event, $session, $order) {

            try {
                StripeEvent::create([
                    'event_id'    => $event->id,
                    'type'        => $event->type,
                    'livemode'    => (bool)($event->livemode ?? false),
                    'received_at' => now(),
                ]);
            } catch (QueryException $e) {
                return;
            }

            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $item  = Item::whereKey($order->item_id)->lockForUpdate()->firstOrFail();

            $markPaid = function () use ($order, $item) {
                if ($order->payment_status === 'paid') {
                    return;
                }

                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);

                $item->update([
                    'status' => 'sold',
                    'processing_expires_at' => null,
                ]);
            };

            $release = function (string $finalStatus) use ($order, $item) {
                if ($order->payment_status !== 'pending') {
                    return;
                }

                $update = ['payment_status' => $finalStatus];

                if ($finalStatus === 'canceled') {
                    $update['canceled_at'] = now();
                } elseif ($finalStatus === 'expired') {
                    $update['expired_at'] = now();
                }

                $order->update($update);

                $item->update([
                    'status' => 'on_sale',
                    'processing_expires_at' => null,
                ]);
            };

            switch ($event->type) {
                case 'checkout.session.completed':
                    if (($session->payment_status ?? null) === 'paid') {
                        $markPaid();
                    }
                    break;

                case 'checkout.session.async_payment_succeeded':
                    $markPaid();
                    break;

                case 'checkout.session.async_payment_failed':
                    $release('canceled');
                    break;

                case 'checkout.session.expired':
                    $release('expired');
                    break;

                default:
                    break;
            }
        });

        return response()->json(['ok' => true]);
    }
}
