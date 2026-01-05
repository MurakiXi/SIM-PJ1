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
        $type = $event->type ?? '';

        $allowed = [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
            'checkout.session.async_payment_failed',
            'checkout.session.expired',
        ];

        if (! in_array($type, $allowed, true)) {
            return response()->json(['ok' => true], 200);
        }

        $session = $event->data->object;

        $orderId = $session->metadata->order_id ?? null;
        $order = $orderId ? Order::find($orderId) : null;

        if (! $order) {
            $order = Order::where('stripe_session_id', $session->id)->first();
        }

        \Log::info('stripe webhook order lookup', [
            'session_id' => $session->id,
            'metadata_order_id' => $orderId,
            'order_found' => (bool) $order,
            'order_id' => $order->id ?? null,
        ]);

        if (! $order) {
            \Log::warning('stripe webhook: order not found', [
                'event_id'   => $event->id ?? null,
                'type'       => $event->type ?? null,
                'session_id' => $session->id ?? null,
                'metadata_order_id' => $orderId,
            ]);

            return response()->json(['ok' => true], 200);
        }

        DB::transaction(function () use ($event, $session, $order) {

            StripeEvent::firstOrCreate(
                ['event_id' => $event->id],
                [
                    'type'        => $event->type,
                    'livemode'    => (bool)($event->livemode ?? false),
                    'received_at' => now(),
                ]
            );

            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $item  = Item::whereKey($order->item_id)->lockForUpdate()->firstOrFail();

            \Log::info('stripe webhook tx state', [
                'type' => $event->type ?? null,
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
                'item_id' => $item->id,
                'item_status' => $item->status,
            ]);

            $markPaid = function () use ($order, $item) {
                if ($order->payment_status === 'paid' || $item->status === 'sold') {
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
                \Log::info('stripe webhook release called', [
                    'final' => $finalStatus,
                    'order_status' => $order->payment_status,
                    'item_status' => $item->status,
                ]);

                if ($order->payment_status !== 'pending') {
                    \Log::info('stripe webhook release skipped: not pending');
                    return;
                }
                if ($item->status === 'sold') {
                    return;
                }

                $update = ['payment_status' => $finalStatus];

                if ($finalStatus === 'canceled') {
                    $update['canceled_at'] = now();
                } elseif ($finalStatus === 'expired') {
                    $update['expired_at'] = now();
                }

                $order->update($update);

                if ($item->status === 'processing') {
                    $item->update([
                        'status' => 'on_sale',
                        'processing_expires_at' => null,
                    ]);
                }
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
                    \Log::info('stripe webhook case expired', ['order_id' => $order->id]);
                    $release('expired');
                    break;
            }
        });


        return response()->json(['ok' => true]);
    }
}
