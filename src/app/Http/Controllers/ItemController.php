<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Item;
use App\Models\Like;
use App\Models\Category;
use App\Http\Requests\ExhibitionRequest;
use App\Http\Requests\CommentRequest;
use App\Models\Order;

class ItemController extends Controller
{
    private function baseQuery(): Builder
    {
        return Item::query()
            ->with(['seller'])
            ->withCount(['likes', 'comments'])
            ->withExists('activeorder')
            ->when(auth()->check(), function (Builder $q) {
                // for FN014-4
                $q->where('seller_id', '!=', auth()->id());
            })
            ->latest();
    }

    public function index(Request $request)
    {
        $this->releaseExpiredReservations();
        $query = Item::query();

        $tab     = (string) $request->query('tab', '');
        $keyword = trim((string) $request->query('keyword', ''));

        $query = $this->baseQuery();

        $query->when($keyword !== '', function (Builder $q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%");
        });

        // for PG02
        if ($tab === 'mylist') {
            if (auth()->check()) {
                $query->whereHas('likes', function (Builder $q) {
                    $q->where('user_id', auth()->id());
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $items = $query->paginate(20)->appends($request->only('tab', 'keyword'));

        return view('item.index', compact('items', 'tab', 'keyword'));
    }

    public function show(Item $item)
    {
        $item->releaseProcessingIfExpired();
        $item->refresh();

        $item->load([
            'seller',
            'categories',
            'comments.user',
        ])->loadCount(['likes', 'comments']);

        $isLiked = auth()->check()
            ? $item->likes()->where('user_id', auth()->id())->exists()
            : false;

        return view('item.show', compact('item', 'isLiked'));
    }

    public function search(Request $request)
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $tab = (string) $request->query('tab', '');

        return redirect()->route('items.index', [
            'keyword' => $keyword,
            'tab' => $tab,
        ]);
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
                'image_path'  => $path,
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

    public function toggleLike(Item $item, Request $request)
    {
        $user = $request->user();

        $like = Like::where('user_id', $user->id)->where('item_id', $item->id)->first();

        if ($like) {
            $like->delete();
        } else {
            Like::create(['user_id' => $user->id, 'item_id' => $item->id]);
        }

        return back();
    }

    public function storeComment(CommentRequest $request, Item $item)
    {
        $item->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated()['body'],
        ]);
        return back()->with('message', 'コメントを投稿しました。');
    }

    private function releaseExpiredReservations(): void
    {
        $now = now();

        DB::transaction(function () use ($now) {

            Order::where('payment_status', 'pending')
                ->where('reserved_until', '<=', $now)
                ->update([
                    'payment_status' => 'expired',
                    'expired_at'      => $now,
                    'updated_at'      => $now,
                ]);

            Item::where('status', 'processing')
                ->whereNotNull('processing_expires_at')
                ->where('processing_expires_at', '<=', $now)

                ->whereDoesntHave('orders', function ($q) {
                    $q->where('payment_status', 'paid');
                })

                ->whereDoesntHave('orders', function ($q) use ($now) {
                    $q->where('payment_status', 'pending')
                        ->where('reserved_until', '>', $now);
                })

                ->update([
                    'status'               => 'on_sale',
                    'processing_expires_at' => null,
                    'updated_at'           => $now,
                ]);
        });
    }
}
