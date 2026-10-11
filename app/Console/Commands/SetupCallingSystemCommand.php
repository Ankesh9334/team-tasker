<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupCallingSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calling:setup {--livekit-url=wss://demo.livekit.cloud} {--api-key=devkey} {--api-secret=secret} {--ring-timeout=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup and verify LiveKit Audio/Video Calling & Meeting System for Team Tasker';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('====================================================');
        $this->info('🚀 Setting up LiveKit Calling System for Team Tasker');
        $this->info('====================================================');

        $livekitUrl = $this->option('livekit-url');
        $apiKey = $this->option('api-key');
        $apiSecret = $this->option('api-secret');
        $ringTimeout = $this->option('ring-timeout');

        // 1. Publish & Verify LiveKit Configuration
        $this->components->task('Configuring Environment Variables', function () use ($livekitUrl, $apiKey, $apiSecret, $ringTimeout) {
            $envPath = base_path('.env');
            if (File::exists($envPath)) {
                $envContent = File::get($envPath);

                $vars = [
                    'LIVEKIT_URL' => $livekitUrl,
                    'LIVEKIT_API_KEY' => $apiKey,
                    'LIVEKIT_API_SECRET' => $apiSecret,
                    'CALL_RING_TIMEOUT' => $ringTimeout,
                    'ENABLE_WEBSOCKETS' => 'true',
                ];

                foreach ($vars as $key => $val) {
                    if (str_contains($envContent, "{$key}=")) {
                        $envContent = preg_replace("/{$key}=.*/", "{$key}={$val}", $envContent);
                    } else {
                        $envContent .= "\n{$key}={$val}";
                    }
                }

                File::put($envPath, $envContent);
            }

            return true;
        });

        // 2. Execute Database Migrations
        $this->components->task('Running Database Migrations', function () {
            $this->callSilently('migrate', ['--force' => true]);

            return true;
        });

        // 3. Clear & Refresh Application Cache
        $this->components->task('Clearing Application & Config Cache', function () {
            $this->callSilently('config:clear');
            $this->callSilently('cache:clear');
            $this->callSilently('view:clear');

            return true;
        });

        // 4. Verify Socket.IO Server Status
        $this->info("\n📡 Verifying WebSocket Server Connection...");
        $socketPort = 3000;
        $connection = @fsockopen('localhost', $socketPort, $errno, $errstr, 2);

        if (is_resource($connection)) {
            fclose($connection);
            $this->info("✅ Socket.IO Server is running on port {$socketPort}");
        } else {
            $this->warn("⚠️  Socket.IO Server is NOT running on port {$socketPort}.");
            $this->line('👉 To start the Node.js signaling server, run:');
            $this->comment('   node socket-server/server.js');
        }

        $this->info("\n🎉 Calling System Setup Complete!");
        $this->table(
            ['Configuration', 'Value'],
            [
                ['LiveKit URL', config('livekit.url', $livekitUrl)],
                ['LiveKit API Key', config('livekit.api_key', $apiKey)],
                ['Ring Timeout', config('livekit.call_ring_timeout', 30).' seconds'],
                ['WebSockets Enabled', env('ENABLE_WEBSOCKETS', true) ? 'Yes' : 'No'],
                ['Socket Server Port', '3000'],
            ]
        );

        return Command::SUCCESS;
    }
}
