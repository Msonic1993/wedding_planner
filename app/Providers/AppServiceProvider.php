<?php

namespace App\Providers;

use App\Application\Auth\Contracts\AuthenticationService;
use App\Application\Auth\Services\SanctumAuthenticationService;
use App\Application\Budget\Contracts\BudgetService;
use App\Application\Budget\Services\EloquentBudgetService;
use App\Application\Chat\Contracts\InquiryChatService;
use App\Application\Chat\Services\EloquentInquiryChatService;
use App\Application\Favorite\Contracts\FavoriteService;
use App\Application\Favorite\Services\EloquentFavoriteService;
use App\Application\Profile\Contracts\WeddingProfileService;
use App\Application\Profile\Services\EloquentWeddingProfileService;
use App\Application\Vendor\Contracts\VendorProfileService;
use App\Application\Vendor\Contracts\VendorReviewService;
use App\Application\Vendor\Services\EloquentVendorProfileService;
use App\Application\Vendor\Services\EloquentVendorReviewService;
use App\Infrastructure\Translation\YamlFileLoader;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthenticationService::class, SanctumAuthenticationService::class);
        $this->app->bind(BudgetService::class, EloquentBudgetService::class);
        $this->app->bind(InquiryChatService::class, EloquentInquiryChatService::class);
        $this->app->bind(FavoriteService::class, EloquentFavoriteService::class);
        $this->app->bind(WeddingProfileService::class, EloquentWeddingProfileService::class);
        $this->app->bind(VendorProfileService::class, EloquentVendorProfileService::class);
        $this->app->bind(VendorReviewService::class, EloquentVendorReviewService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->extend('translation.loader', fn (): YamlFileLoader => new YamlFileLoader(
            $this->app->make(Filesystem::class),
            [lang_path(), base_path('languages')],
        ));

        ResetPassword::createUrlUsing(
            fn (object $notifiable, string $token): string => sprintf(
                '%s/reset-password?token=%s&email=%s',
                rtrim((string) config('app.url'), '/'),
                $token,
                urlencode((string) $notifiable->getEmailForPasswordReset()),
            ),
        );
    }
}
