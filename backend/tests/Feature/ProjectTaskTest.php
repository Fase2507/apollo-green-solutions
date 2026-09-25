<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTaskTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(): array
    {
        $user = User::factory()->create();

        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_user_can_create_project(): void
    {
        $this->postJson('/api/projects', [
            'name' => 'Solar Farm Alpha',
            'description' => 'A new solar array',
            'status' => 'in_progress',
        ], $this->authHeaders())
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Solar Farm Alpha')
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_project_defaults_to_planned_status(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/projects', ['name' => 'Wind Farm Beta']);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', ProjectStatus::Planned->value);
    }

    public function test_project_requires_a_name(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/projects', ['name' => ''])
            ->assertStatus(422);
    }

    public function test_user_sees_only_own_projects(): void
    {
        $me = User::factory()->create();
        Project::factory()->count(2)->create(['user_id' => $me->id]);
        Project::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($me, 'sanctum')
            ->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_cannot_access_another_users_project(): void
    {
        $others = Project::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/projects/{$others->id}")
            ->assertNotFound();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->deleteJson("/api/projects/{$others->id}")
            ->assertNotFound();
    }

    public function test_user_can_update_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/projects/{$project->id}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', ProjectStatus::Completed->value);
    }

    public function test_project_show_includes_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Task::factory()->count(3)->create(['project_id' => $project->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.tasks_count', 3)
            ->assertJsonCount(3, 'data.tasks');
    }

    public function test_user_can_create_task_with_defaults(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Install panels',
                'priority' => 'high',
                'due_date' => '2026-10-15',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Install panels')
            ->assertJsonPath('data.status', TaskStatus::Todo->value)
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.due_date', '2026-10-15');
    }

    public function test_task_rejects_invalid_status(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Bad task',
                'status' => 'not_a_status',
            ])
            ->assertStatus(422);
    }

    public function test_task_must_belong_to_users_project_to_be_updated(): void
    {
        $task = Task::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->putJson("/api/tasks/{$task->id}", ['status' => 'done'])
            ->assertNotFound();
    }

    public function test_user_can_delete_own_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_deleting_project_cascades_to_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Task::factory()->count(3)->create(['project_id' => $project->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseCount('tasks', 0);
    }
}
