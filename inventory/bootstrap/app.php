<?php

use App\Domain\DomainConflict;
use App\Http\Middleware\EnforceSessionLifetime;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\RequestCorrelation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php')
    ->withCommands([__DIR__.'/../app/Console/Commands'])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(RequestCorrelation::class);
        $middleware->alias(['active' => EnsureActiveUser::class, 'session.lifetime' => EnforceSessionLifetime::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e): bool => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (DomainConflict $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return back()->withInput()->withErrors(['operation' => $e->getMessage()]);
            }

            return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'context' => (object) $e->context, 'request_id' => $request->attributes->get('request_id')]], 409);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => ['code' => 'unauthenticated', 'message' => 'Authentication is required.', 'request_id' => $request->attributes->get('request_id')]], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => ['code' => 'forbidden', 'message' => 'This action is not allowed.', 'request_id' => $request->attributes->get('request_id')]], 403);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => ['code' => 'forbidden', 'message' => 'This action is not allowed.', 'request_id' => $request->attributes->get('request_id')]], 403);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => ['code' => 'validation_failed', 'message' => 'Review the submitted fields.', 'fields' => $e->errors(), 'request_id' => $request->attributes->get('request_id')]], 422);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => ['code' => 'resource_not_found', 'message' => 'The requested resource was not found.', 'request_id' => $request->attributes->get('request_id')]], 404);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => ['code' => 'rate_limited', 'message' => 'Too many requests. Wait and retry.', 'request_id' => $request->attributes->get('request_id')]], 429, $e->getHeaders());
            }
        });

        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => ['code' => 'csrf_failed', 'message' => 'Refresh the session and retry safely.', 'request_id' => $request->attributes->get('request_id')]], 419);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if (($request->is('api/*') || $request->expectsJson()) && $e->getStatusCode() === 419) {
                return response()->json(['error' => ['code' => 'csrf_failed', 'message' => 'Refresh the session and retry safely.', 'request_id' => $request->attributes->get('request_id')]], 419);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson()) || $e instanceof DomainConflict || $e instanceof ValidationException || $e instanceof HttpExceptionInterface) {
                return null;
            }
            report($e);

            return response()->json(['error' => ['code' => 'internal_error', 'message' => 'The request could not be completed.', 'request_id' => $request->attributes->get('request_id')]], 500);
        });
    })->create();
