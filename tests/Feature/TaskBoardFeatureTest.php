<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Volt\Volt;
use Tests\TestCase;

class TaskBoardFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected User $staffUser;

    protected Status $statusTodo;

    protected Status $statusDone;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], [
            'name' => 'Super Admin',
            'permissions' => ['*'],
        ]);

        $staffRole = Role::firstOrCreate(['slug' => 'staff'], [
            'name' => 'Staff Member',
            'permissions' => ['tasks.board', 'tasks.my_tasks'],
        ]);

        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin_board_'.uniqid().'@example.com',
        ]);

        $this->staffUser = User::factory()->create([
            'role_id' => $staffRole->id,
            'email' => 'staff_board_'.uniqid().'@example.com',
        ]);

        $this->statusTodo = Status::firstOrCreate(['slug' => 'to-do'], [
            'name' => 'To Do',
            'color' => '#3b82f6',
            'order' => 1,
        ]);

        $this->statusDone = Status::firstOrCreate(['slug' => 'completed-test'], [
            'name' => 'Completed Test',
            'color' => '#10b981',
            'order' => 2,
        ]);
    }

    public function test_user_can_access_task_board_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('tasks.board'));
        $response->assertStatus(200);
        $response->assertSee('Task Board');
    }

    public function test_admin_with_view_all_sees_all_tasks_on_board(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->staffUser->id,
            'status_id' => $this->statusTodo->id,
            'title' => 'Admin Visible Task '.uniqid(),
        ]);

        $this->actingAs($this->adminUser);

        Volt::test('task-board')
            ->assertSee($task->title);
    }

    public function test_staff_without_view_all_only_sees_their_tasks(): void
    {
        $myTask = Task::factory()->create([
            'user_id' => $this->staffUser->id,
            'status_id' => $this->statusTodo->id,
            'title' => 'My Staff Task '.uniqid(),
        ]);

        $otherTask = Task::factory()->create([
            'user_id' => $this->adminUser->id,
            'assigned_to' => $this->adminUser->id,
            'status_id' => $this->statusTodo->id,
            'title' => 'Other Admin Task '.uniqid(),
        ]);

        $this->actingAs($this->staffUser);

        Volt::test('task-board')
            ->assertSee($myTask->title)
            ->assertDontSee($otherTask->title);
    }

    public function test_update_task_status_changes_status_and_logs_activity(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->adminUser->id,
            'status_id' => $this->statusTodo->id,
            'title' => 'Movable Task '.uniqid(),
        ]);

        $this->actingAs($this->adminUser);

        Volt::test('task-board')
            ->call('updateTaskStatus', $task->id, $this->statusDone->id)
            ->assertDispatched('status-updated');

        $this->assertEquals($this->statusDone->id, $task->fresh()->status_id);

        $this->assertDatabaseHas('task_logs', [
            'task_id' => $task->id,
            'user_id' => $this->adminUser->id,
            'type' => 'log',
        ]);
    }

    public function test_live_search_filters_tasks_on_board(): void
    {
        $uniquePhrase = 'UniqueSearchTitle_'.uniqid();
        $matchingTask = Task::factory()->create([
            'user_id' => $this->adminUser->id,
            'status_id' => $this->statusTodo->id,
            'title' => $uniquePhrase,
        ]);

        $otherTask = Task::factory()->create([
            'user_id' => $this->adminUser->id,
            'status_id' => $this->statusTodo->id,
            'title' => 'Other Title '.uniqid(),
        ]);

        $this->actingAs($this->adminUser);

        Volt::test('task-board')
            ->set('search', $uniquePhrase)
            ->assertSee($matchingTask->title)
            ->assertDontSee($otherTask->title);
    }
}
