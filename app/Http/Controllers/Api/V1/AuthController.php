<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Auth\Contracts\AuthenticationService;
use App\Application\Auth\Data\AuthResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\DeleteAccountRequest;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    /**
     * @OA\Post(path="/api/v1/auth/register", tags={"Authentication"}, summary="Register a Bride, Groom, or Vendor account", @OA\RequestBody(required=true, @OA\JsonContent(required={"name", "email", "password", "password_confirmation", "role"}, @OA\Property(property="name", type="string"), @OA\Property(property="email", type="string", format="email"), @OA\Property(property="password", type="string", format="password"), @OA\Property(property="password_confirmation", type="string", format="password"), @OA\Property(property="role", type="string", enum={"bride", "groom", "vendor"}))), @OA\Response(response=201, description="Account created"), @OA\Response(response=422, description="Validation error"))
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authentication->register($request->validated());
        $result->user->sendEmailVerificationNotification();

        return $this->authenticatedResponse($result, __('app.auth.registered'), Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(path="/api/v1/auth/login", tags={"Authentication"}, summary="Create a Sanctum access token", @OA\RequestBody(required=true, @OA\JsonContent(required={"email", "password"}, @OA\Property(property="email", type="string", format="email"), @OA\Property(property="password", type="string", format="password"), @OA\Property(property="device_name", type="string"))), @OA\Response(response=200, description="Authenticated"), @OA\Response(response=401, description="Invalid credentials"), @OA\Response(response=422, description="Validation error"))
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authentication->login($request->validated());

        if ($result === null) {
            return self::errorResponse(__('app.errors.invalid_credentials'), Response::HTTP_UNAUTHORIZED);
        }

        return $this->authenticatedResponse($result, __('app.auth.logged_in'));
    }

    /** @OA\Post(path="/api/v1/auth/logout", tags={"Authentication"}, security={{"sanctum":{}}}, summary="Revoke current access token", @OA\Response(response=204, description="Logged out"), @OA\Response(response=401, description="Unauthenticated")) */
    public function logout(): JsonResponse
    {
        $this->authentication->logout(request()->user());

        return $this->success(message: __('app.auth.logged_out'));
    }

    /** @OA\Post(path="/api/v1/auth/forgot-password", tags={"Authentication"}, summary="Send password reset link", @OA\Response(response=200, description="Reset link processed"), @OA\Response(response=422, description="Validation error")) */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->safe()->only('email'));

        return $this->success(message: __('app.auth.password_reset_link_sent'));
    }

    /** @OA\Post(path="/api/v1/auth/reset-password", tags={"Authentication"}, summary="Reset password using a reset token", @OA\Response(response=200, description="Password reset"), @OA\Response(response=422, description="Invalid reset data")) */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset($request->validated(), function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return self::errorResponse(__('app.errors.password_reset'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success(message: __('app.auth.password_reset'));
    }

    /** @OA\Get(path="/api/v1/auth/email/verify/{id}/{hash}", tags={"Authentication"}, security={{"sanctum":{}}}, summary="Verify email address", @OA\Response(response=204, description="Email verified"), @OA\Response(response=401, description="Unauthenticated")) */
    public function verifyEmail(EmailVerificationRequest $request): JsonResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return $this->success(message: __('app.auth.email_verified'));
    }

    /** @OA\Post(path="/api/v1/auth/email/verification-notification", tags={"Authentication"}, security={{"sanctum":{}}}, summary="Resend email verification notification", @OA\Response(response=202, description="Notification queued"), @OA\Response(response=401, description="Unauthenticated")) */
    public function sendVerificationNotification(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        if (! $user->hasVerifiedEmail()) {
            $user->notify(new VerifyEmail);
        }

        return $this->success(message: __('app.auth.email_verification_sent'), status: Response::HTTP_ACCEPTED);
    }

    /** @OA\Delete(path="/api/v1/auth/account", tags={"Authentication"}, security={{"sanctum":{}}}, summary="Permanently delete the current account", @OA\Response(response=204, description="Account deleted"), @OA\Response(response=401, description="Unauthenticated"), @OA\Response(response=422, description="Invalid password")) */
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authentication->deleteAccount($user);

        return $this->success(message: __('app.auth.account_deleted'));
    }

    private function authenticatedResponse(AuthResult $result, string $message, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->success([
            'token' => $result->token,
            'token_type' => 'Bearer',
            'user' => new UserResource($result->user),
        ], $message, $status);
    }
}
