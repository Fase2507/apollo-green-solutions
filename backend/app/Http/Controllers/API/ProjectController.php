<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    /**
     * List the authenticated user's projects.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = $request->user()->projects()
            ->withCount('tasks')
            ->latest()
            ->get();

        return ProjectResource::collection($projects);
    }

    /**
     * Create a new project.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $request->user()->projects()->create($request->validated());
        $project->refresh()->loadCount('tasks');

        return (new ProjectResource($project))->response($request)->setStatusCode(201);
    }

    /**
     * Show a single project owned by the user.
     */
    public function show(Request $request, Project $project): ProjectResource
    {
        $this->ensureOwnership($request, $project);

        return new ProjectResource($project->loadCount('tasks')->load('tasks'));
    }

    /**
     * Update a project owned by the user.
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->ensureOwnership($request, $project);

        $project->update($request->validated());

        return new ProjectResource($project->loadCount('tasks'));
    }

    /**
     * Delete a project owned by the user (cascades to its tasks).
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->ensureOwnership($request, $project);

        $project->delete();

        return response()->json(null, 204);
    }

    /**
     * Abort with 404 unless the project belongs to the authenticated user.
     * 404 (instead of 403) avoids leaking that another user's project exists.
     */
    private function ensureOwnership(Request $request, Project $project): void
    {
        abort_unless($request->user()->id === $project->user_id, 404, 'Project not found.');
    }
}
