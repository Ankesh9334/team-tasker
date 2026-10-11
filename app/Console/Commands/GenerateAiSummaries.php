<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateAiSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:summarize-ai';

    protected $description = 'Generate daily AI summaries of all task logs across the platform';

    public function handle()
    {
        $this->info('Starting AI Task Summarization...');

        // 1. Fetch all activity logs created today
        $logs = \App\Models\TaskLog::with('user', 'task', 'project')
            ->whereDate('created_at', now()->toDateString())
            ->get();

        if ($logs->isEmpty()) {
            $this->info('No task logs found for today.');

            return;
        }

        // 2. Prepare text payload for the AI
        $logEntries = [];
        foreach ($logs as $log) {
            $user = $log->user->name ?? 'System';
            $taskTitle = $log->task->title ?? 'General Task';
            $projectName = $log->project->name ?? 'Unassigned Project';
            $logEntries[] = "- [{$projectName}] {$user} worked on '{$taskTitle}': {$log->note}";
        }
        $logText = implode("\n", $logEntries);

        // 3. Construct the prompt
        $prompt = "You are a professional project manager. Summarize the following daily activity logs into a concise, professional executive summary. Highlight major completions, ongoing work, and any obvious blockers. Do not use generic filler.\n\nLogs:\n".$logText;

        $this->info('Sending '.count($logs).' logs to AI for processing...');

        // 4. API Call (Using OpenAI as an example)
        $apiKey = env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            $this->error('OPENAI_API_KEY is not set in the .env file.');
            $this->warn('Here is the generated prompt for testing:');
            $this->line($prompt);

            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an executive project assistant.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 500,
                ]);

            if ($response->successful()) {
                $summary = $response->json('choices.0.message.content');

                // 5. Store or Distribute (Here we just log it, but typically save to DB or email)
                $this->info('=== AI Generated Summary ===');
                $this->line($summary);
                $this->info('============================');

                // TODO: Save to a daily_summaries table or send via email notification
            } else {
                $this->error('API Error: '.$response->body());
            }
        } catch (\Exception $e) {
            $this->error('Failed to communicate with AI API: '.$e->getMessage());
        }
    }
}
