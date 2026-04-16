<?php

namespace Tests\Feature;

use App\Enums\TaskStatusEnum;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
    }

    public function test_store_creates_task(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks', ['title' => 'New Task'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'New Task')
            ->assertJsonPath('data.status', TaskStatusEnum::PENDING->value);
    }

    public function test_store_requires_title(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_cannot_access_another_users_task(): void
    {
        $task = Task::factory()->create(); // belongs to a different user

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tasks/{$task->id}")
            ->assertForbidden();
    }

    public function test_mark_completed_is_idempotent(): void
    {
        $task = Task::factory()->completed()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/tasks/{$task->id}/complete")
            ->assertOk()
            ->assertJsonPath('message', 'Task is already completed');
    }
}
