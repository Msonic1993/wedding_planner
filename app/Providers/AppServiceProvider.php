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
use App\Application\Location\Contracts\LocationCatalog;
use App\Application\Notification\Contracts\PushDeviceService;
use App\Application\Notification\Contracts\PushNotifier;
use App\Application\Notification\Services\EloquentPushDeviceService;
use App\Application\Profile\Contracts\WeddingProfileService;
use App\Application\Profile\Services\EloquentWeddingProfileService;
use App\Application\Vendor\Contracts\VendorProfileService;
use App\Application\Vendor\Contracts\VendorReviewService;
use App\Application\Vendor\Services\EloquentVendorProfileService;
use App\Application\Vendor\Services\EloquentVendorReviewService;
use App\Infrastructure\Location\GeoNamesJsonLocationCatalog;
use App\Infrastructure\Notification\ExpoPushNotifier;
use App\Infrastructure\Translation\YamlFileLoader;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->app->singleton(LocationCatalog::class, GeoNamesJsonLocationCatalog::class);
        $this->app->bind(PushDeviceService::class, EloquentPushDeviceService::class);
        $this->app->bind(PushNotifier::class, fn (Application $app): ExpoPushNotifier => new ExpoPushNotifier(
            $app->make(HttpFactory::class),
            (string) config('services.expo.push_url'),
            config('services.expo.access_token'),
        ));
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

        RateLimiter::for('chat-messages', fn (Request $request): Limit => $this->chatMessageLimit($request));

        ResetPassword::createUrlUsing(
            fn (object $notifiable, string $token): string => sprintf(
                '%s/reset-password?token=%s&email=%s',
                rtrim((string) config('app.url'), '/'),
                $token,
                urlencode((string) $notifiable->getEmailForPasswordReset()),
            ),
        );
    }

    /**
     * Rate limit for POST /chat/inquiries/{inquiry}/messages.
     * Every message costs a DB transaction and a broadcast to several channels.
     */
    private function chatMessageLimit(Request $request): Limit
    {
        return Limit::perMinute(30)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
    }
}
