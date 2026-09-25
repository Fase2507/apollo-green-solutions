<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    /**
     * List tasks belonging to a project owned by the user.
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->ensureProjectOwnership($request, $project);

        return TaskResource::collection($project->tasks()->latest()->get());
    }

    /**
     * Create a task inside a project owned by the user.
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->ensureProjectOwnership($request, $project);

        $task = $project->tasks()->create($request->validated());
        $task->refresh();

        return (new TaskResource($task))->response($request)->setStatusCode(201);
    }

    /**
     * Update a task whose parent project is owned by the user.
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->ensureTaskOwnership($request, $task);

        $task->update($request->validated());

        return new TaskResource($task);
    }

    /**
     * Delete a task whose parent project is owned by the user.
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->ensureTaskOwnership($request, $task);

        $task->delete();

        return response()->json(null, 204);
    }

    /**
     * Abort with 404 unless the project belongs to the authenticated user.
     * 404 (instead of 403) avoids leaking that another user's project exists.
     */
    private function ensureProjectOwnership(Request $request, Project $project): void
    {
        abort_unless($request->user()->id === $project->user_id, 404, 'Project not found.');
    }

    /**
     * Abort with 404 unless the task's parent project belongs to the user.
     */
    private function ensureTaskOwnership(Request $request, Task $task): void
    {
        abort_unless($request->user()->id === $task->project->user_id, 404, 'Task not found.');
    }
}
