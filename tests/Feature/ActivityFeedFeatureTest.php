<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActivityFeedFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected User $userOne;

    protected User $userTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], [
            'name' => 'Super Admin',
            'permissions' => ['*'],
        ]);

        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin_act_'.uniqid().'@example.com',
        ]);

        $this->userOne = User::factory()->create([
            'role_id' => $adminRole->id,
            'name' => 'Alice Activity '.uniqid(),
            'email' => 'alice_'.uniqid().'@example.com',
        ]);

        $this->userTwo = User::factory()->create([
            'role_id' => $adminRole->id,
            'name' => 'Bob Activity '.uniqid(),
            'email' => 'bob_'.uniqid().'@example.com',
        ]);
    }

    public function test_admin_can_view_activity_feed_with_stats_and_users(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('tasks.activity'));

        $response->assertStatus(200);
        $response->assertViewHas('activities');
        $response->assertViewHas('users');
        $response->assertViewHas('activityStats');
    }

    public function test_can_filter_activity_by_single_user(): void
    {
        $task = Task::factory()->create(['user_id' => $this->adminUser->id]);

        $logOne = TaskLog::create([
            'task_id' => $task->id,
            'user_id' => $this->userOne->id,
            'note' => 'Action by user one '.uniqid(),
            'type' => 'log',
        ]);

        $logTwo = TaskLog::create([
            'task_id' => $task->id,
            'user_id' => $this->userTwo->id,
            'note' => 'Action by user two '.uniqid(),
            'type' => 'log',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('tasks.activity', [
            'user_id' => $this->userOne->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee($logOne->note);
        $response->assertDontSee($logTwo->note);
    }

    public function test_can_filter_activity_by_multiple_users(): void
    {
        $task = Task::factory()->create(['user_id' => $this->adminUser->id]);

        $userThree = User::factory()->create(['email' => 'charlie_'.uniqid().'@example.com']);

        $logOne = TaskLog::create([
            'task_id' => $task->id,
            'user_id' => $this->userOne->id,
            'note' => 'Multi action one '.uniqid(),
            'type' => 'log',
        ]);

        $logTwo = TaskLog::create([
            'task_id' => $task->id,
            'user_id' => $this->userTwo->id,
            'note' => 'Multi action two '.uniqid(),
            'type' => 'log',
        ]);

        $logThree = TaskLog::create([
            'task_id' => $task->id,
            'user_id' => $userThree->id,
            'note' => 'Multi action three '.uniqid(),
            'type' => 'log',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('tasks.activity', [
            'user_id' => [$this->userOne->id, $this->userTwo->id],
        ]));

        $response->assertStatus(200);
        $response->assertSee($logOne->note);
        $response->assertSee($logTwo->note);
        $response->assertDontSee($logThree->note);
    }

    public function test_can_filter_activity_by_type(): void
    {
        $task = Task::factory()->create(['user_id' => $this->adminUser->id]);

        $uniqueKey = uniqid();
        $systemLog = TaskLog::create([
            'task_id' => $task->id,
            'user_id' => $this->userOne->id,
            'note' => 'System log note '.$uniqueKey,
            'type' => 'log',
        ]);

        $messageLog = TaskLog::create([
            'task_id' => $task->id,
            'user_id' => $this->userOne->id,
            'note' => 'Message note '.$uniqueKey,
            'type' => 'message',
        ]);

        // Filter type=message
        $response = $this->actingAs($this->adminUser)->get(route('tasks.activity', [
            'type' => 'message',
            'search' => $uniqueKey,
        ]));

        $response->assertStatus(200);
        $response->assertSee($messageLog->note);
        $response->assertDontSee($systemLog->note);

        // Filter type=log
        $responseLog = $this->actingAs($this->adminUser)->get(route('tasks.activity', [
            'type' => 'log',
            'search' => $uniqueKey,
        ]));

        $responseLog->assertStatus(200);
        $responseLog->assertSee($systemLog->note);
        $responseLog->assertDontSee($messageLog->note);
    }

    public function test_can_filter_activity_by_search_keyword(): void
    {
        $task = Task::factory()->create(['user_id' => $this->adminUser->id]);
        $uniquePhrase = 'UniqueSearchPhrase_'.uniqid();

        $matchingLog = TaskLog::create([
            'task_id' => $task->id,
            'user_id' => $this->userOne->id,
            'note' => 'Here is the matching log: '.$uniquePhrase,
            'type' => 'log',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('tasks.activity', [
            'search' => $uniquePhrase,
        ]));

        $response->assertStatus(200);
        $response->assertSee($uniquePhrase);
    }
}
