<?php

namespace App\Providers;

use App\Repositories\Contracts\AdjustmentRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\IncomeRepositoryInterface;
use App\Repositories\Contracts\InviteRepositoryInterface;
use App\Repositories\Contracts\SalaryRepositoryInterface;
use App\Repositories\Contracts\TelegramLinkRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\AdjustmentRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\GroupRepository;
use App\Repositories\IncomeRepository;
use App\Repositories\InviteRepository;
use App\Repositories\SalaryRepository;
use App\Repositories\TelegramLinkRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AdjustmentRepositoryInterface::class, AdjustmentRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(ExpenseRepositoryInterface::class, ExpenseRepository::class);
        $this->app->bind(IncomeRepositoryInterface::class, IncomeRepository::class);
        $this->app->bind(SalaryRepositoryInterface::class, SalaryRepository::class);
        $this->app->bind(GroupRepositoryInterface::class, GroupRepository::class);
        $this->app->bind(InviteRepositoryInterface::class, InviteRepository::class);
        $this->app->bind(TelegramLinkRepositoryInterface::class, TelegramLinkRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
