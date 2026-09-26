<?php

namespace App\Providers;

use App\Contracts\InstituteAiExtractionProvider;
use App\Services\OpenAiCompatibleInstituteAiExtractionProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            InstituteAiExtractionProvider::class,
            OpenAiCompatibleInstituteAiExtractionProvider::class,
        );
    }

    public function boot(): void
    {
    }
}
