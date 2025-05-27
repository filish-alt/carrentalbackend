use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

protected $dontReport = [
    \Illuminate\Auth\AuthenticationException::class,
    \Illuminate\Validation\ValidationException::class,
    \Symfony\Component\HttpKernel\Exception\HttpException::class,
];

public function render($request, Throwable $exception)
{
    // Validation error
    if ($exception instanceof ValidationException) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $exception->errors(),
        ], 422);
    }

    // Model not found
    if ($exception instanceof ModelNotFoundException) {
        return response()->json([
            'success' => false,
            'message' => 'Resource not found'
        ], 404);
    }

    // Route not found
    if ($exception instanceof NotFoundHttpException) {
        return response()->json([
            'success' => false,
            'message' => 'Endpoint not found'
        ], 404);
    }

    // Fallback error
    return response()->json([
        'success' => false,
        'message' => $exception->getMessage(),
        'trace' => config('app.debug') ? $exception->getTrace() : []
    ], 500);
}
