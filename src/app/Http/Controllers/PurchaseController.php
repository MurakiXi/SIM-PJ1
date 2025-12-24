<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;

class PurchaseController extends Controller
{
    //

    public function purchase(Item $item, Request $request)
    {
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

    public function checkout(PurchaseRequest $request) {}

    public function editAddress() {}

    public function updateAddress(PurchaseRequest $request) {}
}
