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

    // ID8-1
    public function test_user_can_like_item(): void
    {
        //testuser and testitem
        $user = User::factory()->create();
        $item = Item::factory()->create();

        //POST like
        $response = $this->actingAs($user)->post(route('items.like', $item));

        //302
        $response->assertStatus(302);

        //record exists on likes table
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
    }

    //ID8-2
    public function test_like_toggles_count_and_icon(): void
    {
        //testuser and testitem
        $user = User::factory()->create();
        $item = Item::factory()->create();

        //get
        $res = $this->actingAs($user)->get(route('items.show', $item));

        //200
        $res->assertOk();

        //likes_count=0
        $res->assertViewHas('item', fn($viewItem) => $viewItem->likes_count === 0);

        //can see blankheart and can't see pinkheart
        $res->assertSee('heart-blank.png');
        $res->assertDontSee('heart-pink.png');

        //post like
        $this->actingAs($user)->post(route('items.like', $item))->assertRedirect();

        //record exists on likes table
        $this->assertDatabaseHas('likes', ['user_id' => $user->id, 'item_id' => $item->id]);

        //get
        $res = $this->actingAs($user)->get(route('items.show', $item));

        //likes_count=1
        $res->assertViewHas('item', fn($viewItem) => $viewItem->likes_count === 1);

        //can see pinkheart and can't see blankheart
        $res->assertSee('heart-pink.png');
        $res->assertDontSee('heart-blank.png');

        //post like again
        $this->actingAs($user)->post(route('items.like', $item))->assertRedirect();

        //record doesn't exist on likes table
        $this->assertDatabaseMissing('likes', ['user_id' => $user->id, 'item_id' => $item->id]);

        //get
        $res = $this->actingAs($user)->get(route('items.show', $item));

        //likes_count=1
        $res->assertViewHas('item', fn($viewItem) => $viewItem->likes_count === 0);

        //can see blankheart and can't see pinkheart
        $res->assertSee('heart-blank.png');
        $res->assertDontSee('heart-pink.png');
    }

    // ID8-3
    public function test_user_can_remove_like(): void
    {
        //testuser and testitem
        $user = User::factory()->create();
        $item = Item::factory()->create();

        //make like
        Like::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        //record exists on likes table
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        //POST like again
        $response = $this->actingAs($user)->post(route('items.like', $item));

        //302
        $response->assertStatus(302);

        //record doesn't exists on likes table
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
    }

    public function test_user_can_comment_and_count_increases(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $beforeCount = Comment::where('item_id', $item->id)->count();

        $response = $this->actingAs($user)->post(
            route('items.comment', $item),
            ['body' => 'コメント本文です']
        );

        //302
        $response->assertStatus(302);

        //comment exists on comments table
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'body'    => 'コメント本文です',
        ]);

        //get
        $show = $this->get(route('items.show', $item));

        //200
        $show->assertOk();
        $show->assertSee('コメント(' . ($beforeCount + 1) . ')');
        $show->assertSee('コメント本文です');
        $show->assertSee($user->name);
    }

    //ID9-2
    public function test_guest_cannot_comment_item(): void
    {
        $item = Item::factory()->create();

        $beforeCount = Comment::count();

        $response = $this->post(
            route('items.comment', $item),
            ['body' => 'ゲストコメント（保存されないはず）']
        );

        $response->assertStatus(302);
        $response->assertRedirect('/login');

        $this->assertGuest();

        $this->assertSame($beforeCount, Comment::count());

        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
            'body'    => 'ゲストコメント（保存されないはず）',
        ]);
    }

    //ID9-3
    public function test_comment_body_required(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('items.show', $item))
            ->post(route('items.comment', $item), ['body' => '']);

        $response->assertStatus(302);
        $response->assertRedirect(route('items.show', $item));
        $response->assertSessionHasErrors(['body']);
    }

    //ID9-4
    public function test_comment_body_max_255(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $tooLong = str_repeat('あ', 256);

        $response = $this->actingAs($user)
            ->from(route('items.show', $item))
            ->post(route('items.comment', $item), ['body' => $tooLong]);

        $response->assertStatus(302);
        $response->assertRedirect(route('items.show', $item));
        $response->assertSessionHasErrors(['body']);
    }
}
