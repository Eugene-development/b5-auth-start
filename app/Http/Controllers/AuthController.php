<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

use function Illuminate\Support\defer;

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
                'remember' => $request->input('remember', false),
            ]);

            // Validate input data
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'remember' => 'nullable|boolean',
            ]);

            $credentials = $request->only('email', 'password');
            $remember = $request->boolean('remember', false);

            // Select the lifetime for every login, including reused application instances.
            JWTAuth::factory()->setTTL($remember ? 43200 : config('jwt.ttl'));

            // Attempt to generate JWT token
            if (! $token = JWTAuth::claims(['remember' => $remember])->attempt($credentials)) {
                Log::warning('JWT Login failed - invalid credentials', [
                    'email' => $request->input('email'),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Неверный email или пароль.',
                    'errors' => [
                        'email' => ['Неверный email или пароль.'],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = Auth::user();

            if (! $user || $user->ban || ! $user->is_active) {
                JWTAuth::setToken($token)->invalidate();

                return response()->json([
                    'success' => false,
                    'message' => 'Учётная запись недоступна.',
                    'errors' => [
                        'email' => ['Учётная запись заблокирована или отключена.'],
                    ],
                ], Response::HTTP_FORBIDDEN);
            }

            Log::info('JWT Login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'status_id' => $user->status_id,
                'type' => $user->type,
                'remember' => $remember,
            ]);

            // Calculate TTL in minutes (will be 43200 if remember, otherwise default)
            $ttl = JWTAuth::factory()->getTTL();

            // Get cookie domain dynamically from Origin header for multi-domain support
            $cookieDomain = $this->getCookieDomainFromOrigin($request);

            // Create httpOnly cookie
            // SameSite=None required for cross-origin requests (rubonus.pro -> api.rubonus.pro)
            $cookie = cookie(
                'b5_auth_token',           // Cookie name
                $token,                     // JWT token
                $remember ? $ttl : 0,       // Persist only when "remember me" is checked
                '/',                        // Path
                $cookieDomain,              // Domain (dynamic based on origin)
                true,                       // Secure (required for SameSite=None)
                true,                       // HttpOnly
                false,                      // Raw
                'none'                      // SameSite (none for cross-origin)
            );

            Log::info('JWT Cookie created for login', [
                'domain' => $cookieDomain,
                'origin' => $request->header('Origin'),
            ]);

            return response()->json([
                'success' => true,
                'user' => $user,
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $ttl * 60, // in seconds
                'message' => 'Login successful',
            ])->cookie($cookie);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (JWTException $e) {
            Log::error('JWT Token creation error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Could not create token.',
                'errors' => [
                    'general' => ['Authentication failed. Please try again.'],
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            Log::error('Login error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.'],
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle user registration with JWT
     *
     * Creates new user account with company and returns JWT token
     */
    public function register(Request $request)
    {
        try {
            Log::info('JWT Registration attempt', [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'company_name' => $request->input('company_name'),
                'phone' => $request->input('phone'),
            ]);

            // Validate registration data
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'company_name' => 'nullable|string|min:2|max:255',
                'region' => 'nullable|string|max:255',
                'phone' => 'nullable|string|regex:/^[\+]?[0-9\s\-\(\)]{10,20}$/',
                'ref' => 'nullable|string|max:255',
                'campaign_token' => ['nullable', 'string', 'regex:/^[a-f0-9]{48}$/'],
            ]);

            // Validate referrer if provided
            $referrerKey = null;
            if ($request->filled('campaign_token')) {
                $campaignKey = app(\App\Services\GrowthRegistrationService::class)->referrerKey($request->campaign_token);
                $referrerKey = app(\App\Services\ReferralService::class)->validateReferrer($campaignKey, 0);
                if ($referrerKey === null) {
                    throw ValidationException::withMessages(['campaign_token' => 'Приглашение недоступно.']);
                }
                if ($request->filled('ref') && $request->ref !== $referrerKey) {
                    throw ValidationException::withMessages(['ref' => 'Источник приглашения не совпадает с реферером.']);
                }
            } elseif ($request->filled('ref')) {
                $referralService = app(\App\Services\ReferralService::class);
                $referrerKey = $referralService->validateReferrer($request->ref, 0);

                Log::info('Referrer validation result', [
                    'ref_input' => $request->ref,
                    'validated_referrer_key' => $referrerKey,
                ]);
            }

            // Get registration domain from Origin or Referer header
            $registrationDomain = $this->getRegistrationDomain($request);

            // Public registration always creates an agent. Origin/Referer is audit
            // metadata only and must never grant a privileged status.
            $companyStatusId = $this->getCompanyStatusId('not-defined');
            $userStatusId = $this->getPublicRegistrationStatusId();

            Log::info('Creating company and user', [
                'registration_domain' => $registrationDomain,
                'company_status_id' => $companyStatusId,
                'user_status_id' => $userStatusId,
            ]);

            // Create company and user in transaction
            $result = DB::transaction(function () use ($request, $registrationDomain, $companyStatusId, $userStatusId, $referrerKey) {
                $company = null;

                // Create company only if company_name is provided
                if ($request->filled('company_name')) {
                    $company = Company::create([
                        'name' => $request->company_name,
                        'legal_name' => $request->company_name,
                        'ban' => false,
                        'is_active' => true,
                        'status_id' => $companyStatusId,
                    ]);
                }

                // Create user linked to company (if exists) and referrer (if validated)
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'registration_domain' => $registrationDomain,
                    'status_id' => $userStatusId,
                    'company_id' => $company?->id,
                    'referrer_key' => $referrerKey, // ULID ключ реферера (если есть)
                ]);

                // Add region if provided
                if ($request->filled('campaign_token')) {
                    app(\App\Services\GrowthRegistrationService::class)->attributeUser($request->campaign_token, $user);
                }

                if ($request->filled('region')) {
                    $user->region = $request->region;
                    $user->save();
                }

                // Create user phone if provided
                if ($request->filled('phone')) {
                    $user->phones()->create([
                        'value' => $request->phone,
                        'is_primary' => true,
                    ]);
                }

                return ['user' => $user, 'company' => $company];
            });

            $user = $result['user'];
            $company = $result['company'];

            // Send email verification notification
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Exception $e) {
                Log::error('Failed to send email verification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Generate JWT token for the new user
            $token = JWTAuth::fromUser($user);

            Log::info('JWT Registration successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'status_id' => $user->status_id,
                'company_id' => $company?->id,
                'company_name' => $company?->name,
                'referrer_key' => $user->referrer_key,
            ]);

            // Calculate TTL in minutes
            $ttl = JWTAuth::factory()->getTTL();

            // Get cookie domain dynamically from Origin header for multi-domain support
            $cookieDomain = $this->getCookieDomainFromOrigin($request);

            // Create httpOnly cookie
            // SameSite=None required for cross-origin requests (rubonus.pro -> api.rubonus.pro)
            $cookie = cookie(
                'b5_auth_token',           // Cookie name
                $token,                     // JWT token
                $ttl,                       // Expiration time (minutes)
                '/',                        // Path
                $cookieDomain,              // Domain (dynamic based on origin)
                true,                       // Secure (required for SameSite=None)
                true,                       // HttpOnly
                false,                      // Raw
                'none'                      // SameSite (none for cross-origin)
            );

            Log::info('JWT Cookie created for registration', [
                'domain' => $cookieDomain,
                'origin' => $request->header('Origin'),
            ]);

            return response()->json([
                'success' => true,
                'user' => $user->fresh(), // Reload with relationships
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $ttl * 60,
                'message' => 'Registration successful. Please check your email to verify your account.',
            ], Response::HTTP_CREATED)->cookie($cookie);
        } catch (ValidationException $e) {
            Log::warning('Registration validation failed', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (JWTException $e) {
            Log::error('JWT Token creation error after registration', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'User created but could not create token.',
                'errors' => [
                    'general' => ['Registration partially successful. Please try to log in.'],
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            Log::error('Registration error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.'],
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a user from the administrative panel without replacing the
     * administrator's JWT cookie.
     */
    public function createManagedUser(Request $request)
    {
        $actor = Auth::guard('api')->user();
        $actorStatus = $actor?->status?->slug;

        if (! $actor || $actor->ban || ! $actor->is_active || ! in_array($actorStatus, ['admin', 'curator'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Недостаточно прав для создания пользователя.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'region' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[\+]?[0-9\s\-\(\)]{10,20}$/'],
            'status_id' => [
                'required',
                'string',
                Rule::exists('user_statuses', 'id')->where('is_active', true),
            ],
        ]);

        $targetStatus = DB::table('user_statuses')->where('id', $validated['status_id'])->first();
        if (
            $actorStatus === 'curator'
            && ! in_array($targetStatus?->slug, ['curator', 'manager', 'designer'], true)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Куратор не может назначить этот статус.',
            ], Response::HTTP_FORBIDDEN);
        }

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'region' => $validated['region'] ?? null,
                'status_id' => $validated['status_id'],
                'ban' => false,
                'is_active' => true,
            ]);

            if (! empty($validated['phone'])) {
                $user->phones()->create([
                    'value' => $validated['phone'],
                    'is_primary' => true,
                ]);
            }

            return $user;
        });

        return response()->json([
            'success' => true,
            'user' => $user->fresh(['status', 'phones']),
            'message' => 'Пользователь создан.',
        ], Response::HTTP_CREATED);
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

            // The API guard owns the token parsed during authentication.
            Auth::guard('api')->logout();

            Log::info('JWT Logout completed successfully');

            // Get cookie domain dynamically for proper cookie deletion
            $cookieDomain = $this->getCookieDomainFromOrigin($request);

            // Clear the httpOnly cookie with the same domain it was set with
            // SameSite=None required for cross-origin requests
            $cookie = cookie(
                'b5_auth_token',
                null,
                -1,  // Expire immediately
                '/',
                $cookieDomain,
                true,                       // Secure (required for SameSite=None)
                true,
                false,
                'none'                      // SameSite (none for cross-origin)
            );

            Log::info('JWT Cookie cleared for logout', [
                'domain' => $cookieDomain,
                'origin' => $request->header('Origin'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ])->cookie($cookie);
        } catch (JWTException $e) {
            Log::error('JWT Logout error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to logout, please try again.',
                'errors' => [
                    'general' => ['Failed to invalidate token.'],
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            Log::error('Logout error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.'],
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Refresh JWT token
     *
     * Rotates the JWT and replaces the httpOnly cookie.
     */
    public function refresh(Request $request)
    {
        try {
            $guard = Auth::guard('api');
            // Only the signed token can authorize persistent login; legacy tokens stay session-only.
            $remember = $guard->getPayload()->get('remember') === true;
            JWTAuth::factory()->setTTL($remember ? 43200 : config('jwt.ttl'));
            $newToken = $guard->refresh();
            $ttl = JWTAuth::factory()->getTTL();
            $cookie = cookie(
                'b5_auth_token',
                $newToken,
                $remember ? $ttl : 0,
                '/',
                $this->getCookieDomainFromOrigin($request),
                true,
                true,
                false,
                'none'
            );

            return response()->json([
                'success' => true,
                'expires_in' => $ttl * 60,
                'message' => 'Token refreshed successfully',
            ])->cookie($cookie);
        } catch (JWTException $e) {
            Log::error('JWT Refresh error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Could not refresh token.',
                'errors' => [
                    'general' => ['Token refresh failed. Please log in again.'],
                ],
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

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.',
                    'errors' => [
                        'auth' => ['User not authenticated.'],
                    ],
                ], Response::HTTP_UNAUTHORIZED);
            }

            return response()->json([
                'success' => true,
                'user' => $user,
            ]);
        } catch (Exception $e) {
            Log::error('Get user error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching user data.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.'],
                ],
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
                    'message' => 'Email уже подтвержден.',
                ], Response::HTTP_BAD_REQUEST);
            }

            $user->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Письмо с подтверждением отправлено.',
            ]);
        } catch (Exception $e) {
            Log::error('Send verification email error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending verification email.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.'],
                ],
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

            if (! hash_equals((string) $hash, $expectedHash)) {
                Log::warning('Email verification failed: hash mismatch');

                return response()->json([
                    'success' => false,
                    'message' => 'Недействительная ссылка для подтверждения.',
                ], Response::HTTP_FORBIDDEN);
            }

            if ($user->hasVerifiedEmail()) {
                Log::info('Email already verified');

                return response()->json([
                    'success' => true,
                    'message' => 'Email уже был подтвержден ранее.',
                    'data' => [
                        'user' => $user,
                    ],
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
                    'user' => $user->fresh(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Email verification failed: user not found', ['id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Пользователь не найден.',
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Email verification error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during email verification.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.'],
                ],
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
                $domain = $parsedUrl['scheme'].'://'.$parsedUrl['host'];
                if (isset($parsedUrl['port']) && ! in_array($parsedUrl['port'], [80, 443])) {
                    $domain .= ':'.$parsedUrl['port'];
                }

                return $domain;
            }
        }

        // Fallback to FRONTEND_URL from env
        return config('app.frontend_url');
    }

    /**
     * Resolve the only status permitted for unauthenticated registration.
     */
    private function getPublicRegistrationStatusId(): string
    {
        $agentStatus = DB::table('user_statuses')
            ->where('slug', 'agent')
            ->where('is_active', true)
            ->first();

        if ($agentStatus) {
            return $agentStatus->id;
        }

        $nonPrivilegedStatus = DB::table('user_statuses')
            ->where('is_active', true)
            ->whereNotIn('slug', ['admin', 'curator', 'manager', 'designer'])
            ->orderByDesc('is_default')
            ->first();

        if (! $nonPrivilegedStatus) {
            throw new \RuntimeException('No non-privileged user status is available for public registration');
        }

        return $nonPrivilegedStatus->id;
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

        if (! $defaultStatus) {
            throw new \Exception('Default user status not found in database');
        }

        return $defaultStatus->id;
    }

    /**
     * Get company status_id by slug.
     */
    private function getCompanyStatusId(string $slug): string
    {
        $status = DB::table('company_statuses')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $status) {
            // Fallback to default company status
            $status = DB::table('company_statuses')
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
        }

        if (! $status) {
            throw new \Exception("Company status '{$slug}' not found in database");
        }

        return $status->id;
    }

    /**
     * Get user status_id by slug.
     */
    private function getUserStatusId(string $slug): string
    {
        $status = DB::table('user_statuses')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $status) {
            // Fallback to default user status
            return $this->getDefaultStatusId();
        }

        return $status->id;
    }

    /**
     * Send password reset link to user's email.
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email|max:255',
            ]);

            $token = Str::random(64);
            $tokenHash = Hash::make($token);
            $table = config('auth.passwords.users.table', 'password_reset_tokens');

            $user = DB::transaction(function () use ($validated, $tokenHash, $table) {
                // Serialize issuance and consumption using the same account lock.
                $user = User::where('email', $validated['email'])->lockForUpdate()->first();
                if (! $user) {
                    return null;
                }

                $existingToken = DB::table($table)->where('email', $user->email)->first();
                $throttle = (int) config('auth.passwords.users.throttle', 60);
                if ($existingToken && $existingToken->created_at !== null
                    && \Carbon\Carbon::parse($existingToken->created_at)->addSeconds($throttle)->isFuture()) {
                    return null;
                }

                DB::table($table)->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => $tokenHash, 'created_at' => now()]
                );

                return $user;
            });

            if ($user) {
                $origin = $request->header('Origin');
                // SMTP latency must not reveal whether this email is registered.
                defer(function () use ($user, $token, $tokenHash, $table, $origin) {
                    try {
                        $user->notify(new ResetPasswordNotification($token, $user->email, $origin));
                    } catch (Exception $e) {
                        DB::table($table)->where('email', $user->email)->where('token', $tokenHash)->delete();
                        Log::error('Password reset email delivery failed', ['user_id' => $user->id, 'exception' => $e::class]);
                    }
                });
            }

            return response()->json([
                'success' => true,
                'message' => 'Если указанный email существует, на него будет отправлена ссылка для сброса пароля.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректный email.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Password reset error', ['exception' => $e::class]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при отправке ссылки для сброса пароля.',
                'errors' => [
                    'general' => ['Произошла ошибка. Попробуйте позже.'],
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reset user password using token.
     */
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string|size:64',
                'email' => 'required|string|email|max:255',
                'password' => [
                    'bail', 'required', 'string', 'min:8', 'confirmed',
                    function (string $attribute, string $value, \Closure $fail) {
                        if (str_contains($value, "\0")) {
                            $fail('Пароль содержит недопустимый символ.');
                        } elseif (config('hashing.driver', 'bcrypt') === 'bcrypt' && strlen($value) > 72) {
                            $fail('Пароль слишком длинный. Используйте более короткий пароль.');
                        }
                    },
                ],
            ]);

            $table = config('auth.passwords.users.table', 'password_reset_tokens');
            $result = DB::transaction(function () use ($validated, $table) {
                $user = User::where('email', $validated['email'])->lockForUpdate()->first();
                $tokenRecord = DB::table($table)->where('email', $validated['email'])->lockForUpdate()->first();
                if (! $user || ! $tokenRecord) {
                    return 'invalid';
                }

                $expire = (int) config('auth.passwords.users.expire', 60);
                if ($tokenRecord->created_at === null
                    || \Carbon\Carbon::parse($tokenRecord->created_at)->addMinutes($expire)->lessThanOrEqualTo(now())) {
                    DB::table($table)->where('email', $validated['email'])->delete();

                    return 'expired';
                }

                if (! Hash::check($validated['token'], $tokenRecord->token)) {
                    return 'invalid';
                }

                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                DB::table($table)->where('email', $validated['email'])->delete();

                return 'success';
            });

            if ($result !== 'success') {
                $message = $result === 'expired'
                    ? 'Токен истек. Запросите новую ссылку для сброса пароля.'
                    : 'Недействительный или истекший токен.';

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => ['token' => [$message]],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response()->json([
                'success' => true,
                'message' => 'Пароль успешно изменен. Теперь вы можете войти с новым паролем.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректные данные.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Password reset error', ['exception' => $e::class]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при сбросе пароля.',
                'errors' => [
                    'general' => ['Произошла ошибка. Попробуйте позже.'],
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get cookie domain from request Origin header for multi-domain support.
     *
     * Dynamically determines the appropriate cookie domain based on the request origin,
     * allowing the same backend to serve multiple frontend domains.
     *
     * @return string|null Cookie domain (e.g., '.bonus5.ru', '.rubonus.pro', '.bonus.band')
     */
    private function getCookieDomainFromOrigin(Request $request): ?string
    {
        // Try Origin header first (standard for CORS requests)
        $origin = $request->header('Origin');

        // Fallback to Referer if Origin is not present (some proxies/Nginx strip Origin)
        if (! $origin) {
            $origin = $request->header('Referer');
            if ($origin) {
                Log::info('Using Referer header as Origin fallback', ['referer' => $origin]);
            }
        }

        if (! $origin) {
            Log::warning('No Origin or Referer header in request, using fallback domain');

            return config('session.domain', null);
        }

        // List of allowed domains for multi-domain support
        $allowedDomains = [
            'bonus5.ru',
            'rubonus.pro',
            'bonus.band',
            'mebelmobile.ru',
        ];

        // Parse host from origin/referer URL
        $parsedUrl = parse_url($origin);
        $host = $parsedUrl['host'] ?? $origin;

        // Special handling for localhost and 127.0.0.1 (development)
        if ($host === 'localhost' || $host === '127.0.0.1' || str_starts_with($host, 'localhost:')) {
            Log::info('Cookie domain set to null for localhost', [
                'host' => $host,
                'origin' => $origin,
            ]);

            return null; // null allows cookie to work on current domain without subdomain sharing
        }

        // Find matching domain and return with leading dot for subdomain support
        foreach ($allowedDomains as $domain) {
            if (str_ends_with($host, $domain)) {
                $cookieDomain = '.'.$domain;
                Log::info('Cookie domain determined', [
                    'source' => $request->header('Origin') ? 'Origin' : 'Referer',
                    'value' => $origin,
                    'host' => $host,
                    'cookie_domain' => $cookieDomain,
                ]);

                return $cookieDomain;
            }
        }

        // Fallback: no matching domain found
        Log::warning('No matching domain found for origin/referer', [
            'origin' => $origin,
            'host' => $host,
        ]);

        return config('session.domain', null);
    }

    /**
     * Send a commercial proposal email to a partner.
     * Only users with admin status can send proposals.
     */
    public function sendCommercialProposal(Request $request)
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не авторизован.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Validate input
            $request->validate([
                'recipient_email' => 'required|email|max:255',
                'subject' => 'required|string|max:500',
                'body' => 'required|string|min:10',
            ]);

            // Check if user has admin status
            $userStatus = $user->status;
            if (! $userStatus || $userStatus->slug !== 'admin') {
                Log::warning('Non-admin user attempted to send commercial proposal', [
                    'user_id' => $user->id,
                    'user_status' => $userStatus?->slug,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещён. Только администраторы могут отправлять коммерческие предложения.',
                ], Response::HTTP_FORBIDDEN);
            }

            $recipientEmail = $request->input('recipient_email');
            $subject = $request->input('subject');
            $body = $request->input('body');

            // Static sender info — always from project lead
            $staticSenderName = 'Челноков Евгений';
            $staticSenderEmail = 'info@rubonus.pro';

            \Illuminate\Support\Facades\Mail::to($recipientEmail)->send(
                new \App\Mail\CommercialProposalMail(
                    subject: $subject,
                    body: $body,
                    senderName: $staticSenderName,
                    senderEmail: $staticSenderEmail
                )
            );

            Log::info('Commercial proposal sent successfully', [
                'recipient' => $recipientEmail,
                'subject' => $subject,
                'admin_user_id' => $user->id,
                'admin_name' => $user->name,
                'static_sender' => $staticSenderName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Коммерческое предложение успешно отправлено на '.$recipientEmail,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Failed to send commercial proposal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке письма: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
