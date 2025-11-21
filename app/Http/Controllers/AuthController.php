<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Exception;

class AuthController extends Controller
{
    /**
     * Handle user login with JWT
     *
     * Validates user credentials and returns JWT token
     */
    public function login(Request $request)
    {
        try {
            Log::info('JWT Login attempt', [
                'email' => $request->input('email'),
                'has_password' => $request->has('password'),
            ]);

            // Validate input data
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $credentials = $request->only('email', 'password');

            // Attempt to generate JWT token
            if (!$token = JWTAuth::attempt($credentials)) {
                Log::warning('JWT Login failed - invalid credentials', [
                    'email' => $request->input('email')
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'The provided credentials are incorrect.',
                    'errors' => [
                        'email' => ['The provided credentials are incorrect.']
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = Auth::user();

            Log::info('JWT Login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'status_id' => $user->status_id,
                'type' => $user->type
            ]);

            return response()->json([
                'success' => true,
                'user' => $user,
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60, // in seconds
                'message' => 'Login successful'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (JWTException $e) {
            Log::error('JWT Token creation error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Could not create token.',
                'errors' => [
                    'general' => ['Authentication failed. Please try again.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            Log::error('Login error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle user registration with JWT
     *
     * Creates new user account and returns JWT token
     */
    public function register(Request $request)
    {
        try {
            Log::info('JWT Registration attempt', [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'region' => $request->input('region'),
                'phone' => $request->input('phone'),
            ]);

            // Validate registration data
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'region' => 'nullable|string|max:255',
                'phone' => 'nullable|string|regex:/^[\+]?[0-9\s\-\(\)]{10,20}$/',
            ]);

            // Get registration domain from Origin or Referer header
            $registrationDomain = $this->getRegistrationDomain($request);

            // Determine status_id based on registration domain
            $statusId = $this->getStatusIdByDomain($registrationDomain);

            Log::info('Creating user with status', [
                'registration_domain' => $registrationDomain,
                'status_id' => $statusId
            ]);

            // Create new user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'region' => $request->region,
                'registration_domain' => $registrationDomain,
                'status_id' => $statusId,
            ]);

            // Create user phone if provided
            if ($request->filled('phone')) {
                $user->phones()->create([
                    'value' => $request->phone,
                    'is_primary' => true,
                ]);
            }

            // Send email verification notification
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Exception $e) {
                Log::error('Failed to send email verification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Generate JWT token for the new user
            $token = JWTAuth::fromUser($user);

            Log::info('JWT Registration successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'status_id' => $user->status_id,
                'type' => $user->type
            ]);

            return response()->json([
                'success' => true,
                'user' => $user->fresh(), // Reload with relationships
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'message' => 'Registration successful. Please check your email to verify your account.'
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            Log::warning('Registration validation failed', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (JWTException $e) {
            Log::error('JWT Token creation error after registration', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'User created but could not create token.',
                'errors' => [
                    'general' => ['Registration partially successful. Please try to log in.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            Log::error('Registration error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle user logout (invalidate JWT token)
     *
     * Invalidates the user's JWT token
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            Log::info('JWT Logout initiated', ['user_id' => $user?->id, 'email' => $user?->email]);

            // Invalidate the token
            JWTAuth::invalidate(JWTAuth::getToken());

            Log::info('JWT Logout completed successfully');

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (JWTException $e) {
            Log::error('JWT Logout error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout, please try again.',
                'errors' => [
                    'general' => ['Failed to invalidate token.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            Log::error('Logout error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Refresh JWT token
     *
     * Returns a new JWT token
     */
    public function refresh(Request $request)
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'message' => 'Token refreshed successfully'
            ]);
        } catch (JWTException $e) {
            Log::error('JWT Refresh error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Could not refresh token.',
                'errors' => [
                    'general' => ['Token refresh failed. Please log in again.']
                ]
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Get authenticated user data
     *
     * Returns current authenticated user information
     */
    public function user(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.',
                    'errors' => [
                        'auth' => ['User not authenticated.']
                    ]
                ], Response::HTTP_UNAUTHORIZED);
            }

            return response()->json([
                'success' => true,
                'user' => $user
            ]);
        } catch (Exception $e) {
            Log::error('Get user error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching user data.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Send email verification notification.
     */
    public function sendEmailVerification(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email уже подтвержден.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $user->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Письмо с подтверждением отправлено.'
            ]);
        } catch (Exception $e) {
            Log::error('Send verification email error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending verification email.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify email address.
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        try {
            Log::info('Email verification started', [
                'id' => $id,
                'hash' => $hash,
            ]);

            $user = User::findOrFail($id);

            $expectedHash = sha1($user->getEmailForVerification());

            if (!hash_equals((string) $hash, $expectedHash)) {
                Log::warning('Email verification failed: hash mismatch');
                return response()->json([
                    'success' => false,
                    'message' => 'Недействительная ссылка для подтверждения.'
                ], Response::HTTP_FORBIDDEN);
            }

            if ($user->hasVerifiedEmail()) {
                Log::info('Email already verified');
                return response()->json([
                    'success' => true,
                    'message' => 'Email уже был подтвержден ранее.',
                    'data' => [
                        'user' => $user
                    ]
                ]);
            }

            $user->markEmailAsVerified();
            event(new \Illuminate\Auth\Events\Verified($user));

            Log::info('Email verification completed successfully', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email успешно подтвержден.',
                'data' => [
                    'user' => $user->fresh()
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Email verification failed: user not found', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не найден.'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Email verification error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during email verification.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Extract registration domain from request headers.
     */
    private function getRegistrationDomain(Request $request): ?string
    {
        // Try to get domain from Origin header first
        $origin = $request->header('Origin');
        if ($origin) {
            return $origin;
        }

        // Fallback to Referer header
        $referer = $request->header('Referer');
        if ($referer) {
            $parsedUrl = parse_url($referer);
            if (isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
                $domain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                if (isset($parsedUrl['port']) && !in_array($parsedUrl['port'], [80, 443])) {
                    $domain .= ':' . $parsedUrl['port'];
                }
                return $domain;
            }
        }

        // Fallback to FRONTEND_URL from env
        return env('FRONTEND_URL', 'http://localhost:5040');
    }

    /**
     * Determine user status_id based on registration domain.
     *
     * Domain mapping:
     * - admin.bonus.band -> Админ
     * - bonus.band -> Куратор
     * - rubonus.info -> Менеджер
     * - bonus5.ru -> Агент
     * - all others -> Не определено (default)
     */
    private function getStatusIdByDomain(?string $registrationDomain): string
    {
        if (!$registrationDomain) {
            return $this->getDefaultStatusId();
        }

        // Extract host from full URL
        $parsedUrl = parse_url($registrationDomain);
        $host = $parsedUrl['host'] ?? $registrationDomain;

        // Map domains to status slugs
        $domainStatusMap = [
            'admin.bonus.band' => 'admin',
            'bonus.band' => 'curators',
            'rubonus.info' => 'managers',
            'bonus5.ru' => 'agents',
        ];

        // Check if domain matches any known domain
        $statusSlug = $domainStatusMap[$host] ?? null;

        if ($statusSlug) {
            // Get status_id by slug
            $status = DB::table('user_statuses')
                ->where('slug', $statusSlug)
                ->where('is_active', true)
                ->first();

            if ($status) {
                return $status->id;
            }
        }

        // Return default status if no match found
        return $this->getDefaultStatusId();
    }

    /**
     * Get default status_id (Не определено).
     */
    private function getDefaultStatusId(): string
    {
        $defaultStatus = DB::table('user_statuses')
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if (!$defaultStatus) {
            throw new \Exception('Default user status not found in database');
        }

        return $defaultStatus->id;
    }

    /**
     * Send password reset link to user's email.
     */
    public function forgotPassword(Request $request)
    {
        try {
            Log::info('Password reset request', [
                'email' => $request->input('email')
            ]);

            $request->validate([
                'email' => 'required|email',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                Log::warning('Password reset requested for non-existent email', [
                    'email' => $request->email
                ]);
                // Return success even if user doesn't exist (security best practice)
                return response()->json([
                    'success' => true,
                    'message' => 'Если указанный email существует, на него будет отправлена ссылка для сброса пароля.'
                ]);
            }

            // Delete old tokens for this email
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            // Create new token
            $token = Str::random(64);

            // Store token in database
            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]);

            // Send notification
            $user->notify(new ResetPasswordNotification($token, $request->email));

            Log::info('Password reset email sent', [
                'email' => $request->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ссылка для сброса пароля отправлена на ваш email.'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректный email.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Password reset error', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при отправке ссылки для сброса пароля.',
                'errors' => [
                    'general' => ['Произошла ошибка. Попробуйте позже.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reset user password using token.
     */
    public function resetPassword(Request $request)
    {
        try {
            Log::info('Password reset attempt', [
                'email' => $request->input('email'),
                'has_token' => $request->has('token')
            ]);

            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Find token record
            $tokenRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$tokenRecord) {
                Log::warning('Password reset failed: token not found', [
                    'email' => $request->email
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Недействительный или истекший токен.',
                    'errors' => [
                        'token' => ['Недействительный или истекший токен.']
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check if token is expired (60 minutes)
            $createdAt = \Carbon\Carbon::parse($tokenRecord->created_at);
            if ($createdAt->addMinutes(60)->isPast()) {
                Log::warning('Password reset failed: token expired', [
                    'email' => $request->email,
                ]);

                // Delete expired token
                DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Токен истек. Запросите новую ссылку для сброса пароля.',
                    'errors' => [
                        'token' => ['Токен истек.']
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Verify token
            if (!Hash::check($request->token, $tokenRecord->token)) {
                Log::warning('Password reset failed: invalid token', [
                    'email' => $request->email
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Недействительный токен.',
                    'errors' => [
                        'token' => ['Недействительный токен.']
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Find user
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                Log::warning('Password reset failed: user not found', [
                    'email' => $request->email
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не найден.',
                    'errors' => [
                        'email' => ['Пользователь не найден.']
                    ]
                ], Response::HTTP_NOT_FOUND);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->save();

            // Delete token after successful reset
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            Log::info('Password reset successful', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Пароль успешно изменен. Теперь вы можете войти с новым паролем.'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректные данные.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Password reset error', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при сбросе пароля.',
                'errors' => [
                    'general' => ['Произошла ошибка. Попробуйте позже.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
