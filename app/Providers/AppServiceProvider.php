<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Paginator::defaultView('components.pagination.premium');

        \App\Models\Project::observe(\App\Observers\ProjectObserver::class);

        // Dynamic Mail Configuration from Database
        try {
            if (\Schema::hasTable('settings')) {
                $settings = \App\Models\Setting::all()->pluck('value', 'key');

                if (isset($settings['smtp_host'])) {
                    config([
                        'mail.default' => $settings['mail_mailer'] ?? config('mail.default'),
                        'mail.mailers.smtp.host' => $settings['smtp_host'] ?? config('mail.mailers.smtp.host'),
                        'mail.mailers.smtp.port' => $settings['smtp_port'] ?? config('mail.mailers.smtp.port'),
                        'mail.mailers.smtp.username' => $settings['smtp_user'] ?? config('mail.mailers.smtp.username'),
                        'mail.mailers.smtp.password' => $settings['smtp_password'] ?? config('mail.mailers.smtp.password'),
                        'mail.mailers.smtp.encryption' => $settings['smtp_encryption'] ?? config('mail.mailers.smtp.encryption'),
                        'mail.from.address' => $settings['from_email'] ?? config('mail.from.address'),
                        'mail.from.name' => $settings['from_name'] ?? config('mail.from.name'),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Avoid failing if DB is not ready
        }
    }
}
