<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthController extends Controller
{
    /**
     * Handle user login
     *
     * Validates user credentials and creates authenticated session
     * Requirements: 2.1, 2.2, 2.3
     */
    public function login(Request $request)
    {
        try {
            // Validate input data
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            // Attempt authentication
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'The provided credentials are incorrect.',
                    'errors' => [
                        'email' => ['The provided credentials are incorrect.']
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Regenerate session for security
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'user' => $request->user(),
                'message' => 'Login successful'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
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
     * Handle user registration
     *
     * Creates new user account and automatically logs them in
     * Requirements: 1.1, 1.2, 1.3
     */
    public function register(Request $request)
    {
        try {
            // Validate registration data
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Create new user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // FIXME: This is not working Регистрация работает, но письмо не отправляется на сервере, а локально нормально
            // Send email verification notification after user is saved
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Exception $e) {
                \Log::error('Failed to send email verification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Automatically log in the new user
            Auth::login($user);
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'user' => $user,
                'message' => 'Registration successful. Please check your email to verify your account.'
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
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
     * Handle user logout
     *
     * Terminates user session and invalidates tokens
     * Requirements: 3.1, 3.2, 3.3
     */
    public function logout(Request $request)
    {
        try {
            // For Sanctum, use the web guard directly
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (Exception $e) {
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
     * Get authenticated user data
     *
     * Returns current authenticated user information
     * Requirements: 2.1, 4.1, 4.2
     */
    public function user(Request $request)
    {
        try {
            $user = $request->user();

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
            $user = $request->user();

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
            $user = User::findOrFail($id);

            if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Недействительная ссылка для подтверждения.'
                ], Response::HTTP_FORBIDDEN);
            }

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email уже был подтвержден ранее.',
                    'data' => [
                        'user' => $user
                    ]
                ]);
            }

            if ($user->markEmailAsVerified()) {
                event(new \Illuminate\Auth\Events\Verified($user));
            }

            return response()->json([
                'success' => true,
                'message' => 'Email успешно подтвержден.',
                'data' => [
                    'user' => $user
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during email verification.',
                'errors' => [
                    'general' => ['An unexpected error occurred. Please try again.']
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
