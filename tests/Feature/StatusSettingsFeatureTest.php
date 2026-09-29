<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StatusSettingsFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], [
            'name' => 'Super Admin',
            'permissions' => ['*'],
        ]);

        $staffRole = Role::firstOrCreate(['slug' => 'staff'], [
            'name' => 'Staff Member',
            'permissions' => ['tasks.view'],
        ]);

        $this->superAdmin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin_status_'.uniqid().'@example.com',
        ]);

        $this->staffUser = User::factory()->create([
            'role_id' => $staffRole->id,
            'email' => 'staff_status_'.uniqid().'@example.com',
        ]);
    }

    public function test_super_admin_can_view_statuses_index_with_stats(): void
    {
        $status = Status::create([
            'name' => 'QA Review '.uniqid(),
            'slug' => 'qa-review-'.uniqid(),
            'color' => '#6366f1',
            'order' => 99,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.settings.statuses'));

        $response->assertStatus(200);
        $response->assertViewHas('statuses');
        $response->assertViewHas('statusStats');
        $this->assertDatabaseHas('statuses', ['id' => $status->id]);
    }

    public function test_status_search_filtering(): void
    {
        $uniqueName = 'UniqueSearchableStatus_'.uniqid();
        $status = Status::create([
            'name' => $uniqueName,
            'slug' => 'uniquesearchablestatus-'.uniqid(),
            'color' => '#10b981',
            'order' => 100,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.settings.statuses', ['search' => $uniqueName]));

        $response->assertStatus(200);
        $response->assertSee($uniqueName);
    }

    public function test_super_admin_can_create_status_with_auto_slug_and_order(): void
    {
        $uniqueName = 'Deployed to Staging '.uniqid();

        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.status.store'), [
            'name' => $uniqueName,
            'color' => '#8b5cf6',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('statuses', [
            'name' => $uniqueName,
            'color' => '#8b5cf6',
        ]);
    }

    public function test_status_creation_handles_duplicate_slug_uniqueness(): void
    {
        $baseName = 'Review Stage '.uniqid();
        $baseSlug = 'review-stage-'.uniqid();

        $status1 = Status::create([
            'name' => $baseName,
            'slug' => $baseSlug,
            'color' => '#3b82f6',
            'order' => 101,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.status.store'), [
            'name' => $baseName,
            'slug' => $baseSlug,
            'color' => '#f59e0b',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('statuses', [
            'slug' => "{$baseSlug}-1",
            'color' => '#f59e0b',
        ]);
    }

    public function test_setting_status_as_default_unsets_previous_default(): void
    {
        $status1 = Status::create([
            'name' => 'Default One '.uniqid(),
            'slug' => 'default-one-'.uniqid(),
            'color' => '#3b82f6',
            'order' => 102,
            'is_default' => true,
        ]);

        $status2 = Status::create([
            'name' => 'Default Two '.uniqid(),
            'slug' => 'default-two-'.uniqid(),
            'color' => '#10b981',
            'order' => 103,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.status.update', $status2->id), [
            'name' => $status2->name,
            'slug' => $status2->slug,
            'color' => $status2->color,
            'order' => $status2->order,
            'is_default' => 1,
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue((bool) $status2->fresh()->is_default);
        $this->assertFalse((bool) $status1->fresh()->is_default);
    }

    public function test_cannot_delete_default_status_or_status_with_tasks(): void
    {
        $defaultStatus = Status::create([
            'name' => 'Default Protected '.uniqid(),
            'slug' => 'default-protected-'.uniqid(),
            'color' => '#3b82f6',
            'order' => 104,
            'is_default' => true,
        ]);

        // Attempt delete default
        $response = $this->actingAs($this->superAdmin)->delete(route('admin.settings.status.delete', $defaultStatus->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('statuses', ['id' => $defaultStatus->id]);

        // Create status with task
        $inUseStatus = Status::create([
            'name' => 'In Use Protected '.uniqid(),
            'slug' => 'in-use-protected-'.uniqid(),
            'color' => '#ef4444',
            'order' => 105,
            'is_default' => false,
        ]);

        $task = Task::factory()->create([
            'user_id' => $this->superAdmin->id,
            'status_id' => $inUseStatus->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.settings.status.delete', $inUseStatus->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('statuses', ['id' => $inUseStatus->id]);
    }

    public function test_super_admin_can_delete_unassigned_non_default_status(): void
    {
        $status = Status::create([
            'name' => 'Deletable Status '.uniqid(),
            'slug' => 'deletable-status-'.uniqid(),
            'color' => '#64748b',
            'order' => 106,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.settings.status.delete', $status->id));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('statuses', ['id' => $status->id]);
    }

    public function test_super_admin_can_bulk_delete_eligible_statuses(): void
    {
        $status1 = Status::create([
            'name' => 'Bulk Status 1 '.uniqid(),
            'slug' => 'bulk-status-1-'.uniqid(),
            'color' => '#ec4899',
            'order' => 107,
            'is_default' => false,
        ]);
        $status2 = Status::create([
            'name' => 'Bulk Status 2 '.uniqid(),
            'slug' => 'bulk-status-2-'.uniqid(),
            'color' => '#f97316',
            'order' => 108,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.status.bulkAction'), [
            'action' => 'delete',
            'ids' => [$status1->id, $status2->id],
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('statuses', ['id' => $status1->id]);
        $this->assertDatabaseMissing('statuses', ['id' => $status2->id]);
    }
}
