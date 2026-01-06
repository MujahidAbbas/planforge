<?php

namespace App\Providers;

use App\Events\TasksChanged;
use App\Listeners\QueueGitHubSync;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        // Rate limiter for LLM API requests (queue jobs)
        // 30 requests per minute globally to avoid hitting provider limits
        RateLimiter::for('llm:requests', function ($job) {
            return Limit::perMinute(30)->by('llm:global');
        });

        // Register GitHub sync event listener
        Event::listen(TasksChanged::class, QueueGitHubSync::class);
    }
}
