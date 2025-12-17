<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CommentSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $member;
    private User $outsider;
    private Project $project;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Users
        $this->manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $this->member = User::factory()->create(['role' => UserRole::USER]);
        $this->outsider = User::factory()->create(['role' => UserRole::USER]);

        // Setup Project and Team
        $this->project = Project::factory()->create(['manager_id' => $this->manager->id]);
        $this->project->members()->attach($this->member);

        // Setup Task
        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to' => $this->member->id,
        ]);
    }

    /** @test */
    public function test_project_member_can_comment_on_task()
    {
        $response = $this->actingAs($this->member)
            ->postJson("/api/tasks/{$this->task->id}/comments", [
                'body' => 'This is a task comment.',
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['body' => 'This is a task comment.']);

        $this->assertDatabaseHas('comments', [
            'body' => 'This is a task comment.',
            'commentable_id' => $this->task->id,
            'commentable_type' => Task::class,
            'user_id' => $this->member->id,
        ]);
    }

    /** @test */
    public function test_project_member_can_comment_on_project()
    {
        $response = $this->actingAs($this->member)
            ->postJson("/api/projects/{$this->project->id}/comments", [
                'body' => 'This is a project comment.',
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['body' => 'This is a project comment.']);

        $this->assertDatabaseHas('comments', [
            'body' => 'This is a project comment.',
            'commentable_id' => $this->project->id,
            'commentable_type' => Project::class,
            'user_id' => $this->member->id,
        ]);
    }

    /** @test */
    public function test_user_can_see_comments_for_their_project_task()
    {
        // Create a comment
        Comment::factory()->create([
            'body' => 'Existing comment',
            'user_id' => $this->manager->id,
            'commentable_id' => $this->task->id,
            'commentable_type' => Task::class,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson("/api/tasks/{$this->task->id}/comments");

        $response->assertOk()
            ->assertJsonFragment(['body' => 'Existing comment']);
    }

    /** @test */
    public function test_author_can_delete_their_own_comment()
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->member->id,
            'commentable_id' => $this->task->id,
            'commentable_type' => Task::class,
        ]);

        $response = $this->actingAs($this->member)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function test_project_manager_can_delete_member_comment()
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->member->id,
            'commentable_id' => $this->task->id,
            'commentable_type' => Task::class,
        ]);

        $response = $this->actingAs($this->manager)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function test_non_member_cannot_comment_on_task()
    {
        $response = $this->actingAs($this->outsider)
            ->postJson("/api/tasks/{$this->task->id}/comments", [
                'body' => 'Intruder comment',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('comments', ['body' => 'Intruder comment']);
    }

    /** @test */
    public function test_non_member_cannot_see_comments()
    {
        Comment::factory()->create([
            'body' => 'Secret comment',
            'user_id' => $this->manager->id,
            'commentable_id' => $this->task->id,
            'commentable_type' => Task::class,
        ]);

        $response = $this->actingAs($this->outsider)
            ->getJson("/api/tasks/{$this->task->id}/comments");

        $response->assertForbidden();
    }

    /** @test */
    public function test_regular_member_cannot_delete_others_comment()
    {
        $otherMember = User::factory()->create(['role' => UserRole::USER]);
        $this->project->members()->attach($otherMember);

        $comment = Comment::factory()->create([
            'user_id' => $otherMember->id,
            'commentable_id' => $this->task->id,
            'commentable_type' => Task::class,
        ]);

        $response = $this->actingAs($this->member)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }
}
