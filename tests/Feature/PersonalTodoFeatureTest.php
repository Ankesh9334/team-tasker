<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PersonalTodoFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected User $staffUser;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], [
            'name' => 'Super Admin',
            'permissions' => ['*'],
        ]);

        $staffRole = Role::firstOrCreate(['slug' => 'staff-todo'], [
            'name' => 'Staff Todo',
            'permissions' => ['tasks.todo'],
        ]);

        $noAccessRole = Role::firstOrCreate(['slug' => 'no-access-todo'], [
            'name' => 'No Access Todo',
            'permissions' => [],
        ]);

        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin_todo_'.uniqid().'@example.com',
        ]);

        $this->staffUser = User::factory()->create([
            'role_id' => $staffRole->id,
            'email' => 'staff_todo_'.uniqid().'@example.com',
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => $noAccessRole->id,
            'email' => 'regular_todo_'.uniqid().'@example.com',
        ]);
    }

    public function test_user_with_permission_can_access_todo_page(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->get(route('admin.todos.index'));

        $response->assertStatus(200);
        $response->assertSee('My Personal To-Do');
        $response->assertSee('TOTAL TO-DOS');
    }

    public function test_user_without_permission_cannot_access_todo_page(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.todos.index'));

        $response->assertStatus(403);
    }

    public function test_todo_list_component_renders_metrics_and_filters(): void
    {
        Todo::create([
            'user_id' => $this->staffUser->id,
            'user_type' => 'web',
            'title' => 'Prepare presentation slides',
            'is_completed' => false,
        ]);

        Todo::create([
            'user_id' => $this->staffUser->id,
            'user_type' => 'web',
            'title' => 'Submit expense report',
            'is_completed' => true,
        ]);

        $this->actingAs($this->staffUser);

        Volt::test('todo-list', ['standalone' => true])
            ->assertSee('Prepare presentation slides')
            ->assertSee('Submit expense report')
            ->assertSee('TOTAL TO-DOS')
            ->assertSee('PENDING')
            ->assertSee('COMPLETED')
            ->assertViewHas('totalCount', 2)
            ->assertViewHas('pendingCount', 1)
            ->assertViewHas('completedCount', 1);
    }

    public function test_todo_list_can_filter_by_status(): void
    {
        Todo::create([
            'user_id' => $this->staffUser->id,
            'user_type' => 'web',
            'title' => 'Task One Active',
            'is_completed' => false,
        ]);

        Todo::create([
            'user_id' => $this->staffUser->id,
            'user_type' => 'web',
            'title' => 'Task Two Done',
            'is_completed' => true,
        ]);

        $this->actingAs($this->staffUser);

        Volt::test('todo-list', ['standalone' => true])
            ->set('status', 'pending')
            ->assertSee('Task One Active')
            ->assertDontSee('Task Two Done')
            ->set('status', 'completed')
            ->assertSee('Task Two Done')
            ->assertDontSee('Task One Active');
    }

    public function test_todo_list_can_search_tasks(): void
    {
        Todo::create([
            'user_id' => $this->staffUser->id,
            'user_type' => 'web',
            'title' => 'Audit security protocols',
            'is_completed' => false,
        ]);

        Todo::create([
            'user_id' => $this->staffUser->id,
            'user_type' => 'web',
            'title' => 'Clean up desk',
            'is_completed' => false,
        ]);

        $this->actingAs($this->staffUser);

        Volt::test('todo-list', ['standalone' => true])
            ->set('search', 'security')
            ->assertSee('Audit security protocols')
            ->assertDontSee('Clean up desk');
    }

    public function test_todo_toggle_completion(): void
    {
        $todo = Todo::create([
            'user_id' => $this->staffUser->id,
            'user_type' => 'web',
            'title' => 'Review PR',
            'is_completed' => false,
        ]);

        $this->actingAs($this->staffUser);

        Volt::test('todo-list', ['standalone' => true])
            ->call('toggleTodo', $todo->id);

        $this->assertTrue($todo->fresh()->is_completed);
    }
}
