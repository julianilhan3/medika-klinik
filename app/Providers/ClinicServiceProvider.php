<?php

namespace App\Providers;

use App\Contracts\ClinicRepository;
use App\Repositories\EloquentClinicRepository;
use Illuminate\Support\ServiceProvider;

class ClinicServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ClinicRepository::class,
            EloquentClinicRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}