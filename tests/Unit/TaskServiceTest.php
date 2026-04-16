<?php

namespace Tests\Unit;

use App\Enums\TaskStatusEnum;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaskService();
        $this->user    = User::factory()->create();
    }

    public function test_creates_task_with_default_pending_status(): void
    {
        $task = $this->service->create($this->user->id, ['title' => 'My Task']);

        $this->assertDatabaseHas('tasks', [
            'title'   => 'My Task',
            'user_id' => $this->user->id,
            'status'  => TaskStatusEnum::PENDING->value,
        ]);
    }

    public function test_find_throws_not_found_for_missing_task(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->findForUser(999, $this->user->id);
    }

    public function test_find_throws_unauthorized_for_wrong_user(): void
    {
        $task = Task::factory()->create();

        $this->expectException(AuthorizationException::class);

        $this->service->findForUser($task->id, $this->user->id);
    }
}
