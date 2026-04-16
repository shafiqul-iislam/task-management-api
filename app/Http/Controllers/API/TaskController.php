<?php

namespace App\Http\Controllers\API;

use App\Enums\TaskStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the authenticated user's tasks.
     * Supports filtering by: status, due_date, search (title).
     */
    public function index(Request $request): JsonResponse
    {
        $tasks = Task::where('user_id', auth()->id())
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->due_date, fn($q) => $q->whereDate('due_date', $request->due_date))
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(10);

        return response()->json([
            'success'    => true,
            'message'    => 'Tasks fetched successfully',
            'data'       => TaskResource::collection($tasks),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'last_page'    => $tasks->lastPage(),
                'per_page'     => $tasks->perPage(),
                'total'        => $tasks->total(),
            ],
        ]);
    }

     public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::create([
            'user_id'     => auth()->id(),
            'title'       => $request->title,
            'description' => $request->description,
            'status'      => $request->status ?? TaskStatusEnum::PENDING->value,
            'due_date'    => $request->due_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data'    => new TaskResource($task),
        ], 201);
    }
 
    public function show(int $id): JsonResponse
    {
        $task = $this->findOwnedTask($id);

        if ($task instanceof JsonResponse) {
            return $task;
        }

        return response()->json([
            'success' => true,
            'message' => 'Task fetched successfully',
            'data'    => new TaskResource($task),
        ]);
    }

      public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $task = $this->findOwnedTask($id);

        if ($task instanceof JsonResponse) {
            return $task;
        }

        $task->update([
            'title'       => $request->title,
            'description' => $request->description,
            'status'      => $request->status,
            'due_date'    => $request->due_date
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data'    => new TaskResource($task->fresh()),
        ]);
    }
 
    public function destroy(int $id): JsonResponse
    {
        $task = $this->findOwnedTask($id);

        if ($task instanceof JsonResponse) {
            return $task;
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);
    }

    /**
     * Mark a task as completed (idempotent — skips if already completed).
     */
    public function markCompleted(int $id): JsonResponse
    {
        $task = $this->findOwnedTask($id);

        if ($task instanceof JsonResponse) {
            return $task;
        }

        if ($task->status === TaskStatusEnum::COMPLETED) {
            return response()->json([
                'success' => true,
                'message' => 'Task is already completed',
                'data'    => new TaskResource($task),
            ]);
        }

        $task->update(['status' => TaskStatusEnum::COMPLETED->value]);

        return response()->json([
            'success' => true,
            'message' => 'Task marked as completed',
            'data'    => new TaskResource($task->fresh()),
        ]);
    }

    /**
     * Find a task by ID and verify it belongs to the authenticated user.
     * Returns the Task on success, or a JsonResponse on failure.
     */
    private function findOwnedTask(int $id): Task|JsonResponse
    {
        $task = Task::find($id);

        if (! $task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        if ($task->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return $task;
    }
}
