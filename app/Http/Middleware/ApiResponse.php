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
                    'status_code' => $response->status(),
                ],
                'jrn' => microtime(true) * 10000, // Unique journal number
            ];

        // Check if the response is a valid Response object
        if ($response instanceof Response) {
            // Decode the response content if it's JSON
            $responseData = json_decode($response->getContent(), true) ?? [];

            // Check if the response status indicates an error
            if ($response->status() >= 400) {
                $formattedResponse['encoded']['isError'] = true;
                $formattedResponse['encoded']['error'] = [
                    'code' => $response->status(),
                    'message' => Response::$statusTexts[$response->status()] ?? 'Unknown error',
                    'errMsg' => 'Check the API documentation for details',
                ];
                $formattedResponse['encoded']['status_code'] = $response->status();
            } else {
                // For successful responses, include the decoded data
                $formattedResponse['encoded']['data'] = $responseData;
            }

            // Return a 200 status code with the formatted response
            return response()->json($formattedResponse, 200);
        }

    }
        // If the response is not an instance of Response, return it as is
        return $response;
    }
}
