<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Capture the response
        $response = $next($request);

        // Format the response
        if ($response instanceof Response) {
            // Get the response data, decode it if it's JSON
            $responseData = json_decode($response->getContent(), true) ?? [];

            // Initialize the formatted response structure
            $formattedResponse = [
                'encoded' => [
                    'data' => $responseData,
                    'isError' => false,
                    'error' => null,
                ],
                'jrn' => microtime(true) * 10000, // Unique journal number
            ];

            // Handle error responses
            if ($response->status() >= 400) {
                $formattedResponse['encoded']['isError'] = true;
                $formattedResponse['encoded']['error'] = [
                    'code' => $response->status(),
                    'message' => Response::$statusTexts[$response->status()] ?? 'Unknown error',
                    'errMsg' => 'Check the API documentation for details',
                ];

                // Set the response data to the formatted error response
                return response()->json($formattedResponse, $response->status());
            }

            // Set the response data to the formatted success response
            return response()->json($formattedResponse);
        }

        return $response;
    }
}
