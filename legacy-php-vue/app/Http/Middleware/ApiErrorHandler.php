<?php

namespace App\Http\Middleware;

use App\Exceptions\InsufficientStockException;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ApiErrorHandler
{
    /**
     * Handle an incoming request and catch exceptions to provide
     * consistent JSON error responses for the API.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    /**
     * Handle exception and return appropriate JSON response
     */
    protected function handleException(Request $request, Throwable $e): Response
    {
        // Log the exception with context
        $this->logException($request, $e);

        // Determine status code and error message
        [$statusCode, $message, $errors] = $this->parseException($e);

        // Build response data
        $response = [
            'success' => false,
            'message' => $message,
        ];

        // Add validation errors if present
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        // Add exception details in non-production environments
        if (config('app.debug') && !app()->environment('production')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->map(function ($trace) {
                    return [
                        'file' => $trace['file'] ?? 'unknown',
                        'line' => $trace['line'] ?? 0,
                        'function' => $trace['function'] ?? 'unknown',
                    ];
                })->toArray(),
            ];
        }

        // Add request ID for tracking
        $response['request_id'] = $request->header('X-Request-ID', uniqid('req_'));

        return response()->json($response, $statusCode);
    }

    /**
     * Parse exception to determine status code and message
     */
    protected function parseException(Throwable $e): array
    {
        $statusCode = 500;
        $message = 'An unexpected error occurred. Please try again later.';
        $errors = [];

        // Validation exceptions
        if ($e instanceof ValidationException) {
            $statusCode = 422;
            $message = 'The given data was invalid.';
            $errors = $e->errors();
        }
        // Authentication exceptions
        elseif ($e instanceof AuthenticationException) {
            $statusCode = 401;
            $message = 'Unauthenticated. Please log in to continue.';
        }
        // Model not found exceptions
        elseif ($e instanceof ModelNotFoundException) {
            $statusCode = 404;
            $model = class_basename($e->getModel());
            $message = "{$model} not found.";
        }
        // HTTP exceptions (404, 403, etc.)
        elseif ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $message = $e->getMessage() ?: Response::$statusTexts[$statusCode] ?? 'Error occurred';
        }
        // Insufficient stock exception (custom)
        elseif ($e instanceof InsufficientStockException) {
            $statusCode = 422;
            $message = $e->getMessage();
        }
        // Optimistic locking failures
        elseif (str_contains($e->getMessage(), 'Optimistic lock failed')) {
            $statusCode = 409; // Conflict
            $message = $e->getMessage();
        }
        // Database errors
        elseif ($e instanceof \Illuminate\Database\QueryException) {
            $statusCode = 500;
            // Don't expose sensitive database errors in production
            $message = app()->environment('production')
                ? 'A database error occurred. Please try again.'
                : $e->getMessage();
        }
        // Generic runtime exceptions
        elseif ($e instanceof \RuntimeException) {
            $statusCode = 400;
            $message = $e->getMessage();
        }
        // All other exceptions
        else {
            $statusCode = 500;
            // In production, use generic message; in development, show actual error
            $message = app()->environment('production')
                ? 'An unexpected error occurred. Please contact support.'
                : $e->getMessage();
        }

        return [$statusCode, $message, $errors];
    }

    /**
     * Log exception with context for debugging
     */
    protected function logException(Request $request, Throwable $e): void
    {
        $context = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'input' => $this->sanitizeInput($request->all()),
        ];

        // Log different levels based on exception type
        if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
            // These are expected errors, log as info
            Log::info('API validation/auth error', array_merge($context, [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]));
        } elseif ($e instanceof ModelNotFoundException) {
            // Not found errors, log as notice
            Log::notice('API resource not found', array_merge($context, [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]));
        } else {
            // Unexpected errors, log as error with full stack trace
            Log::error('API error', array_merge($context, [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]));
        }
    }

    /**
     * Sanitize input to remove sensitive data before logging
     */
    protected function sanitizeInput(array $input): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];

        foreach ($sensitiveFields as $field) {
            if (isset($input[$field])) {
                $input[$field] = '***REDACTED***';
            }
        }

        return $input;
    }
}
