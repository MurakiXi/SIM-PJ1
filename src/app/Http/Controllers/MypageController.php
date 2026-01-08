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
            'mode'   => 'sell',
            'orders' => null,
            'items'  => $items,
        ]);
    }

    public function edit(Request $request)
    { {
            $user = $request->user();
            $address = $user->address;

            return view('mypage.profile', compact('user', 'address'));
        }
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:8'],
            'address'     => ['nullable', 'string', 'max:255'],
            'building'    => ['nullable', 'string', 'max:255'],
            // 'image'     => ['nullable', 'image', 'max:2048'], // 後で
        ]);

        $user->update([
            'name' => $validated['name'],
        ]);

        $user->address()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'postal_code' => $validated['postal_code'] ?? null,
                'address'     => $validated['address'] ?? null,
                'building'    => $validated['building'] ?? null,
            ]
        );

        return redirect()->route('mypage.profile')->with('message', 'プロフィールを更新しました。');
    }
}
