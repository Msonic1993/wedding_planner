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
use Illuminate\Http\Request;
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

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *     summary="Get the authenticated user",
     *     description="Validates the current Bearer token and returns the latest account data and roles. Clients should use this endpoint when restoring a persisted session.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Current user.",
     *
     *         @OA\JsonContent(
     *             required={"success","message","data","errors"},
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", nullable=true, example=null),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(new UserResource($user));
    }

    /**
     * @OA\Post(path="/api/v1/auth/logout",tags={"Authentication"},security={{"sanctum":{}}},summary="Logout current device",description="Revokes only the Bearer token used by this request.",@OA\Response(response=200,description="Structured success response: success=true, message='Wylogowano pomyślnie.', data=null, errors=null"),@OA\Response(response=401,description="Missing or invalid token",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
     */
    public function logout(): JsonResponse
    {
        $this->authentication->logout(request()->user());

        return $this->success(message: __('app.auth.logged_out'));
    }

    /**
     * @OA\Post(path="/api/v1/auth/forgot-password",tags={"Authentication"},summary="Request password reset",description="Always returns 200 to avoid exposing whether email exists.",@OA\RequestBody(required=true,@OA\JsonContent(required={"email"},@OA\Property(property="email",type="string",format="email",maxLength=255,example="anna@example.com"))),@OA\Response(response=200,description="Structured success response with reset-link message"),@OA\Response(response=422,description="Invalid email",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->safe()->only('email'));

        return $this->success(message: __('app.auth.password_reset_link_sent'));
    }

    /**
     * @OA\Post(path="/api/v1/auth/reset-password",tags={"Authentication"},summary="Reset password",@OA\RequestBody(required=true,@OA\JsonContent(required={"token","email","password","password_confirmation"},@OA\Property(property="token",type="string",example="token-from-email"),@OA\Property(property="email",type="string",format="email",example="anna@example.com"),@OA\Property(property="password",type="string",format="password",minLength=8,example="NoweHaslo123"),@OA\Property(property="password_confirmation",type="string",format="password",example="NoweHaslo123"))),@OA\Response(response=200,description="Structured success response"),@OA\Response(response=422,description="Invalid token, email or password",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
     */
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

    /**
     * @OA\Get(path="/api/v1/auth/email/verify/{id}/{hash}",tags={"Authentication"},security={{"sanctum":{}}},summary="Verify email",description="Use the signed URL received in e-mail. id is the authenticated user's id; hash is SHA-1 e-mail hash.",@OA\Parameter(name="id",in="path",required=true,@OA\Schema(type="integer",example=1)),@OA\Parameter(name="hash",in="path",required=true,@OA\Schema(type="string",example="40-character-sha1")),@OA\Response(response=200,description="Structured success response"),@OA\Response(response=401,description="Unauthenticated",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")),@OA\Response(response=403,description="Invalid signed URL or another user",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
     */
    public function verifyEmail(EmailVerificationRequest $request): JsonResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return $this->success(message: __('app.auth.email_verified'));
    }

    /** @OA\Post(path="/api/v1/auth/email/verification-notification",tags={"Authentication"},security={{"sanctum":{}}},summary="Resend verification e-mail",description="No request body. Returns 202 and structured success response.",@OA\Response(response=202,description="Notification processed"),@OA\Response(response=401,description="Unauthenticated",@OA\JsonContent(ref="#/components/schemas/ErrorResponse"))) */
    public function sendVerificationNotification(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        if (! $user->hasVerifiedEmail()) {
            $user->notify(new VerifyEmail);
        }

        return $this->success(message: __('app.auth.email_verification_sent'), status: Response::HTTP_ACCEPTED);
    }

    /** @OA\Delete(path="/api/v1/auth/account",tags={"Authentication"},security={{"sanctum":{}}},summary="Permanently delete own account",@OA\RequestBody(required=true,@OA\JsonContent(required={"password"},@OA\Property(property="password",type="string",format="password",example="AktualneHaslo123"))),@OA\Response(response=200,description="Structured success response"),@OA\Response(response=401,description="Unauthenticated",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")),@OA\Response(response=422,description="Current password invalid",@OA\JsonContent(ref="#/components/schemas/ErrorResponse"))) */
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
