<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function index(Request $request): JsonResponse
    {
        $tasks = $this->taskService->listForUser(
            auth()->id(),
            $request->only(['status', 'due_date', 'search'])
        );

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
        $task = $this->taskService->create(auth()->id(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data'    => new TaskResource($task),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if ($task instanceof JsonResponse) return $task;

        return response()->json([
            'success' => true,
            'message' => 'Task fetched successfully',
            'data'    => new TaskResource($task),
        ]);
    }

    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if ($task instanceof JsonResponse) return $task;

        $task = $this->taskService->update($task, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data'    => new TaskResource($task),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if ($task instanceof JsonResponse) return $task;

        $this->taskService->delete($task);

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);
    }

    public function markCompleted(int $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if ($task instanceof JsonResponse) return $task;

        $changed = $this->taskService->markCompleted($task);

        return response()->json([
            'success' => true,
            'message' => $changed ? 'Task marked as completed' : 'Task is already completed',
            'data'    => new TaskResource($task->fresh()),
        ]);
    }

    private function resolveTask(int $id): Task|JsonResponse
    {
        try {
            return $this->taskService->findForUser($id, auth()->id());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        } catch (AuthorizationException) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
    }
}
