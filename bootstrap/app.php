<?php

use App\Domain\Chat\Exceptions\InquiryAlreadyExistsException;
use App\Domain\Profile\Exceptions\WeddingProfileAlreadyExistsException;
use App\Domain\Vendor\Exceptions\VendorProfileAlreadyExistsException;
use App\Domain\Vendor\Exceptions\VendorReviewAlreadyExistsException;
use App\Http\Controllers\BaseController;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api/v1', 'middleware' => ['auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // TLS terminates at Cloudflare / the mikr.us proxy; trust X-Forwarded-* to keep https URLs and signatures valid.
        $middleware->trustProxies(at: '*');
        $middleware->alias(['role' => RoleMiddleware::class]);
        $middleware->redirectGuestsTo(fn (): null => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return BaseController::errorResponse(__('app.errors.unauthenticated'), Response::HTTP_UNAUTHORIZED);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return BaseController::errorResponse(__('app.errors.unauthorized'), Response::HTTP_FORBIDDEN);
            }
        });

        $exceptions->render(function (SpatieUnauthorizedException $exception, Request $request) {
            if ($request->is('api/*')) {
                return BaseController::errorResponse(__('app.errors.unauthorized'), Response::HTTP_FORBIDDEN);
            }
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if ($request->is('api/*')) {
                return BaseController::errorResponse(__('app.errors.not_found'), Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return BaseController::errorResponse(__('app.errors.not_found'), Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->render(function (WeddingProfileAlreadyExistsException $exception, Request $request) {
            if ($request->is('api/*')) {
                return BaseController::errorResponse(__('app.profile.already_exists'), Response::HTTP_CONFLICT);
            }
        });
        $exceptions->render(function (VendorProfileAlreadyExistsException $exception, Request $request) {
            if ($request->is('api/*')) {
                return BaseController::errorResponse(__('app.vendor.already_exists'), Response::HTTP_CONFLICT);
            }
        });
        $exceptions->render(function (VendorReviewAlreadyExistsException $exception, Request $request) {
            if ($request->is('api/*')) {
                return BaseController::errorResponse(__('app.review.already_exists'), Response::HTTP_CONFLICT);
            }
        });
        $exceptions->render(function (InquiryAlreadyExistsException $exception, Request $request) {
            if ($request->is('api/*')) {
                return BaseController::errorResponse(__('app.chat.inquiry_already_exists'), Response::HTTP_CONFLICT);
            }
        });
    })->create();
