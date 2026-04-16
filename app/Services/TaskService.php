<?php

namespace App\Services;

use App\Enums\TaskStatusEnum;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaskService
{
    public function listForUser(int $userId, array $filters): LengthAwarePaginator
    {
        return Task::where('user_id', $userId)
            ->when($filters['status'] ?? null,  fn($q) => $q->where('status', $filters['status']))
            ->when($filters['due_date'] ?? null, fn($q) => $q->whereDate('due_date', $filters['due_date']))
            ->when($filters['search'] ?? null,   fn($q) => $q->where('title', 'like', "%{$filters['search']}%"))
            ->latest()
            ->paginate(10);
    }

    public function create(int $userId, array $data): Task
    {
        return Task::create([
            'user_id'     => $userId,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? TaskStatusEnum::PENDING->value,
            'due_date'    => $data['due_date'] ?? null,
        ]);
    }

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function findForUser(int $taskId, int $userId): Task
    {
        $task = Task::find($taskId);

        if (! $task) {
            throw new ModelNotFoundException("Task #{$taskId} not found.");
        }

        if ($task->user_id !== $userId) {
            throw new AuthorizationException('You do not own this task.');
        }

        return $task;
    }

    public function update(Task $task, array $data): Task
    {
        $task->update([
            'title'       => $data['title']       ?? $task->title,
            'description' => $data['description'] ?? $task->description,
            'status'      => $data['status']      ?? $task->status,
            'due_date'    => $data['due_date']     ?? $task->due_date,
        ]);

        return $task->fresh();
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function markCompleted(Task $task): bool
    {
        if ($task->status === TaskStatusEnum::COMPLETED) {
            return false;
        }

        $task->update(['status' => TaskStatusEnum::COMPLETED->value]);

        return true;
    }
}
