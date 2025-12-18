<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use App\Models\Category;
use App\Http\Requests\ExhibitionRequest;

class ItemController extends Controller
{
    //
    public function index()
    {
        $query = Item::query()
            ->with(['seller'])->withCount(['likes', 'comments'])->latest();

        // FN014-4
        if (auth()->check()) {
            $query->where('seller_id', '!=', auth()->id());
        }

        $items = $query->paginate(20);

        return view('item.index', compact('items'));
    }

    public function show($item)
    {
        $item = Item::findOrFail($item);
        return view('item.show', compact('item'));
    }

    public function search(Request $request)
    {
        $keyword = trim((string) $request->input('keyword', ''));

        $query = Item::query()->latest();

        // FN014-4
        if (auth()->check()) {
            $query->where('seller_id', '!=', auth()->id());
        }

        if ($keyword !== '') {
            $query->where('name', 'like', "%{$keyword}%");
        }

        $items = $query
            ->paginate(20)
            ->appends($request->only('keyword'));

        return view('item.index', compact('items', 'keyword'));
    }


    public function create()
    {
        $categories = Category::all();

        $conditions = [
            1 => '良好',
            2 => '目立った傷や汚れなし',
            3 => 'やや傷や汚れあり',
            4 => '状態が悪い',
        ];

        return view('item.create', compact('categories', 'conditions'));
    }

    public function store(ExhibitionRequest $request)
    {
        $validated = $request->validated();

        $path = null;

        try {
            DB::beginTransaction();

            $path = $request->file('image')->store('items', 'public');

            $item = Item::create([
                'seller_id'   => auth()->id(),
                'name'        => $validated['name'],
                'brand'       => $validated['brand'] ?? null,
                'description' => $validated['description'],
                'price'       => $validated['price'],
                'condition'   => $validated['condition'],
                'imagepath'   => $path,
                'status'      => 'on_sale',
            ]);

            $item->categories()->sync($validated['category_ids']);

            DB::commit();

            return redirect()->route('items.index');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }
}
