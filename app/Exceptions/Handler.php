<?php

namespace App\Exceptions;

use App\Exceptions\Drivers\DriverExceptionInterface;
use App\Exceptions\Services\LogExceptionInterface;
use App\Exceptions\Services\ServiceExceptionInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class Handler
 *
 * @package App\Exceptions
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    protected $handlers = [];

    /**
     * Handler constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->setHandlers();
    }

    protected function setHandlers(): void
    {
        $this->handlers = [

            AuthenticationException::class => function (AuthenticationException $e) {
                return [
                    'statusCode' => Response::HTTP_UNAUTHORIZED,
                    'error' => 'Authorization failed',
                    'errorCode' => Response::HTTP_UNAUTHORIZED,
                ];
            },
            AuthorizationException::class => function (AuthorizationException $e) {
                return [
                    'statusCode' => Response::HTTP_FORBIDDEN,
                    'error' => 'Access denied ' . URL::current(),
                    'errorCode' => Response::HTTP_FORBIDDEN,
                ];
            },
            ValidationException::class => function (ValidationException $e) {
                return [
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'error' => 'Rules validation error',
                    'errors' => $e->errors(),
                    'errorCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                ];
            },
            NotFoundHttpException::class => function (NotFoundHttpException $e) {
                return [
                    'statusCode' => Response::HTTP_NOT_FOUND,
                    'error' => 'Resource ' . URL::current() . ' not found',
                    'errorCode' => Response::HTTP_NOT_FOUND,
                ];
            },
            ModelNotFoundException::class => function (ModelNotFoundException $e) {
                return [
                    'statusCode' => Response::HTTP_NOT_FOUND,
                    'error' => "Entity not found. " . URL::current(),
                    'errorCode' => Response::HTTP_NOT_FOUND,
                ];
            }
        ];
    }

    /**
     * @param Request $request
     * @param Exception $e
     * @return JsonResponse|Response|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e): JsonResponse
    {
        $response = [
            'success' => false
        ];
        $error = $this->resolveHandler($e)($e);
        $response = array_merge($response, [
            'error' => $error['error'],
            'errorCode' => $error['errorCode'],
        ]);

        if (Arr::get($error, 'errors')) {
            $response['errors'] = Arr::get($error, 'errors');
        }

        if (config('app.debug')) {
            Log::debug(
                sprintf(
                    "%s - %s (%d)\nIn file %s on line %d\n%s",
                    get_class($e),
                    $e->getMessage(),
                    $e->getCode(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                )
            );
        }
        Log::error('Error response: ' . $e->getMessage(), $response);

        return response()->json(
            $response,
            $error['statusCode'] ?: Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * @param Throwable $exception
     * @return callable
     */
    protected function resolveHandler(Throwable $exception): callable
    {
        $handler = $this->handlers[get_class($exception)] ?? static function (Throwable $e) {
                return [
                    'statusCode' => (400 < $e->getCode() ? Response::HTTP_INTERNAL_SERVER_ERROR : $e->getCode()),
                    'error' => $e->getMessage(),
                    'errorCode' => $e->getCode(),
                ];
            };
        if ($exception instanceof DriverExceptionInterface) {
            $handler = static function (DriverExceptionInterface $e) {
                $code = (400 < $e->getCode() ? Response::HTTP_NOT_ACCEPTABLE : $e->getCode());
                return [
                    'statusCode' => $code,
                    'error' => $e->getMessage(),
                    'errorCode' => $code,
                ];
            };
        }
        if ($exception instanceof LogExceptionInterface) {
            $handler = static function (LogExceptionInterface $e) {
                $e->log();
                $code = $e->getCode();
                return [
                    'statusCode' => $code,
                    'error' => $e->getMessage(),
                    'errorCode' => $code,
                ];
            };
        }
        if ($exception instanceof ServiceExceptionInterface) {
            $handler = static function (ServiceExceptionInterface $e) {
                $code = $e->getCode();
                return [
                    'statusCode' => $code,
                    'error' => $e->getMessage(),
                    'errorCode' => $code,
                ];
            };
        }

        return $handler;
    }
}
