<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use App\Models\Order;
use App\Http\Requests\ProfileRequest;

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
                'user' => $user,
            ]);
        }

        $items = Item::query()
            ->where('seller_id', $user->id)
            ->latest()
            ->paginate(20, ['*'], 'p')
            ->appends(['page' => 'sell']);

        return view('mypage.index', [
            'user' => $user,
            'mode'   => 'sell',
            'orders' => null,
            'items'  => $items,
        ]);
    }

    public function edit(Request $request)
    {
        $user = $request->user();
        $address = $user->address;

        return view('mypage.profile', compact('user', 'address'));
    }

    public function update(ProfileRequest $request)
    {
        $user = $request->user();

        $validated = $request->validated();

        if ($request->hasFile('profile_image')) {
            if (!empty($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $path = $request->file('profile_image')->store('profiles', 'public');
            $user->profile_image = $path;
        }

        $user->name = $validated['name'];
        $user->save();

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
