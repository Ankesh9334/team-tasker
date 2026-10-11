<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TagSettingsFeatureTest extends TestCase
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
            'email' => 'admin_tags_'.uniqid().'@example.com',
        ]);

        $this->staffUser = User::factory()->create([
            'role_id' => $staffRole->id,
            'email' => 'staff_tags_'.uniqid().'@example.com',
        ]);
    }

    public function test_super_admin_can_view_tags_index_with_stats(): void
    {
        $tag = Tag::create([
            'name' => 'Feature Test Tag '.uniqid(),
            'slug' => 'feature-test-tag-'.uniqid(),
            'color' => '#3b82f6',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.settings.tags'));

        $response->assertStatus(200);
        $response->assertViewHas('tags');
        $response->assertViewHas('tagStats');
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_tag_search_filtering(): void
    {
        $uniqueName = 'UniqueSearchableTag_'.uniqid();
        $tag = Tag::create([
            'name' => $uniqueName,
            'slug' => 'uniquesearchabletag-'.uniqid(),
            'color' => '#10b981',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.settings.tags', ['search' => $uniqueName]));

        $response->assertStatus(200);
        $response->assertSee($uniqueName);
    }

    public function test_super_admin_can_create_tag_with_auto_slug(): void
    {
        $uniqueName = 'Urgent Review '.uniqid();

        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.tag.store'), [
            'name' => $uniqueName,
            'color' => '#ef4444',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tags', [
            'name' => $uniqueName,
            'color' => '#ef4444',
        ]);
    }

    public function test_tag_creation_handles_duplicate_slug_uniqueness(): void
    {
        $tag1 = Tag::create([
            'name' => 'Backend Tag',
            'slug' => 'backend-tag',
            'color' => '#6366f1',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.tag.store'), [
            'name' => 'Backend Tag',
            'slug' => 'backend-tag',
            'color' => '#8b5cf6',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tags', [
            'slug' => 'backend-tag-1',
            'color' => '#8b5cf6',
        ]);
    }

    public function test_super_admin_can_update_tag(): void
    {
        $tag = Tag::create([
            'name' => 'Initial Tag '.uniqid(),
            'slug' => 'initial-tag-'.uniqid(),
            'color' => '#3b82f6',
        ]);

        $newName = 'Updated Tag '.uniqid();
        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.tag.update', $tag->id), [
            'name' => $newName,
            'slug' => 'updated-tag-'.uniqid(),
            'color' => '#10b981',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => $newName,
            'color' => '#10b981',
        ]);
    }

    public function test_super_admin_can_delete_tag_and_pivot_is_detached(): void
    {
        $tag = Tag::create([
            'name' => 'Tag To Delete '.uniqid(),
            'slug' => 'tag-to-delete-'.uniqid(),
            'color' => '#f59e0b',
        ]);

        $task = Task::factory()->create([
            'user_id' => $this->superAdmin->id,
        ]);

        $tag->tasks()->attach($task->id);
        $this->assertDatabaseHas('tag_task', [
            'tag_id' => $tag->id,
            'task_id' => $task->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.settings.tag.delete', $tag->id));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('tag_task', ['tag_id' => $tag->id]);
    }

    public function test_super_admin_can_bulk_delete_tags(): void
    {
        $tag1 = Tag::create([
            'name' => 'Bulk Tag 1 '.uniqid(),
            'slug' => 'bulk-tag-1-'.uniqid(),
            'color' => '#ec4899',
        ]);
        $tag2 = Tag::create([
            'name' => 'Bulk Tag 2 '.uniqid(),
            'slug' => 'bulk-tag-2-'.uniqid(),
            'color' => '#f97316',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.tag.bulkAction'), [
            'action' => 'delete',
            'ids' => [$tag1->id, $tag2->id],
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('tags', ['id' => $tag1->id]);
        $this->assertDatabaseMissing('tags', ['id' => $tag2->id]);
    }
}
