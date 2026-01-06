<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Order;


class MypageController extends Controller
{
    //
    public function index(Request $request)
    {
        $user = $request->user();
        $mode = $request->query('page', 'sell');

        if ($mode === 'buy') {
            $orders = Order::with('item')
                ->where('buyer_id', $user->id)
                ->where('payment_status', 'paid')
                ->orderByDesc('paid_at')
                ->orderByDesc('id')
                ->paginate(20, ['*'], 'p')
                ->appends(['page' => 'buy']);

            return view('mypage.index', [
                'mode' => $mode,
                'orders' => $orders,
                'items' => null,
            ]);
        }

        $items = Item::query()
            ->where('seller_id', $user->id)
            ->latest()
            ->paginate(20, ['*'], 'p')
            ->appends(['page' => 'sell']);

        return view('mypage.index', [
            'mode' => 'sell',
            'orders' => null,
            'items' => $items,
        ]);
    }

    public function edit(Request $request)
    { {
            $user = $request->user();
            $address = $user->address;

            return view('mypage.profile', compact('user', 'address'));
        }
    }

    public function update(MypageRequest $request) {}
}
