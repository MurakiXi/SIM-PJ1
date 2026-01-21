<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\Comment;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeCommentTest extends TestCase
{
    use RefreshDatabase;

    //ID8-1　いいねアイコンを押下することによって、いいねした商品として登録することができる
    public function test_user_can_like_item(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        //1.ユーザーにログインする
        $this->actingAs($user)
            //2.商品詳細ページを開く
            ->get(route('items.show', $item))
            ->assertSee('<span data-testid="likes-count">0</span>', false);

        //3.いいねアイコンを押下
        $response = $this->actingAs($user)->post(route('items.like', $item));

        $response->assertRedirect();

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($user)
            ->get(route('items.show', $item))
            ->assertSee('<span data-testid="likes-count">1</span>', false);
    }


    //ID8-2
    public function test_like_toggles_count_and_icon(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        //1.ユーザーにログインする
        //2.商品詳細ページを開く
        $res = $this->actingAs($user)->get(route('items.show', $item));
        $res->assertOk();
        $res->assertSee('<span data-testid="likes-count">0</span>', false);
        $res->assertSee('heart-blank.png');
        $res->assertDontSee('heart-pink.png');

        //3.いいねアイコンを押下
        $this->actingAs($user)->post(route('items.like', $item))
            ->assertRedirect(route('items.show', $item));
        $this->assertDatabaseHas('likes', ['user_id' => $user->id, 'item_id' => $item->id]);

        $res = $this->actingAs($user)->get(route('items.show', $item));
        $res->assertSee('<span data-testid="likes-count">1</span>', false);
        $res->assertSee('heart-pink.png');
        $res->assertDontSee('heart-blank.png');
    }

    //ID8-3　再度いいねアイコンを押下することによって、いいねを解除することができる。
    public function test_user_can_remove_like(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        //1.ユーザーにログインする
        $this->actingAs($user)
            //2.商品詳細ページを開く
            ->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('<span data-testid="likes-count">0</span>', false);

        //3.いいねアイコンを押下(いいね)
        $this->actingAs($user)
            ->post(route('items.like', $item))
            ->assertRedirect(route('items.show', $item));

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($user)
            ->get(route('items.show', $item))
            ->assertSee('<span data-testid="likes-count">1</span>', false);

        //3.いいねアイコンを押下(いいね解除)
        $this->actingAs($user)
            ->post(route('items.like', $item))
            ->assertRedirect(route('items.show', $item));

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($user)
            ->get(route('items.show', $item))
            ->assertSee('<span data-testid="likes-count">0</span>', false);
    }

    //ID9-1 ログイン済みのユーザーはコメントを送信できる
    public function test_user_can_comment_and_count_increases(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $beforeCount = Comment::where('item_id', $item->id)->count();

        $response = $this->actingAs($user)
            ->from(route('items.show', $item))
            ->post(route('items.comment', $item), ['body' => 'コメント本文です']);

        $response->assertRedirect(route('items.show', $item));

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'body'    => 'コメント本文です',
        ]);

        $show = $this->actingAs($user)->get(route('items.show', $item));

        $show->assertOk();
        $show->assertSee('<span data-testid="comments-count">1</span>', false);
        $show->assertSee('コメント本文です');
    }

    //ID9-2 ログイン前のユーザーはコメントを送信できない
    public function test_guest_cannot_comment_item(): void
    {
        $item = Item::factory()->create();

        $beforeCount = Comment::count();

        //1.コメントを入力する
        //2.コメントボタンを押す
        $response = $this->post(
            route('items.comment', $item),
            ['body' => 'ゲストコメント']
        );

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertSame($beforeCount, Comment::where('item_id', $item->id)->count());

        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
            'body'    => 'ゲストコメント',
        ]);
    }

    //ID9-3
    public function test_comment_body_required(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        //1.ユーザーにログインする
        //2.コメントボタンを押す
        $response = $this->actingAs($user)
            ->from(route('items.show', $item))
            ->post(route('items.comment', $item), ['body' => '']);

        $response->assertStatus(302);
        $response->assertRedirect(route('items.show', $item));
        $response->assertSessionHasErrors(['body']);

        $show = $this->actingAs($user)->get(route('items.show', $item));
        $show->assertSee('コメントを入力してください。');

        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
            'body' => '',
        ]);
    }

    //ID9-4
    public function test_comment_body_max_255(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $tooLong = str_repeat('あ', 256);

        $before = Comment::where('item_id', $item->id)->count();

        //1.ユーザーにログインする
        //2.255文字以上(256文字)のコメントを入力する
        //3.コメントボタンを押す
        $response = $this->actingAs($user)
            ->from(route('items.show', $item))
            ->post(route('items.comment', $item), ['body' => $tooLong]);

        $response->assertStatus(302);
        $response->assertRedirect(route('items.show', $item));
        $response->assertSessionHasErrors(['body']);

        $show = $this->actingAs($user)->get(route('items.show', $item));
        $show->assertSee('コメントは255文字以内で入力してください。');

        $this->assertSame($before, Comment::where('item_id', $item->id)->count());
    }
}
