<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use SensioLabs\Consul\Consul;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ProxyController extends Controller
{
    protected $guzzleClient;
    protected $consulClient;

    public function __construct(Consul $consulClient)
    {
        $this->guzzleClient = new Client([
            'timeout' => 10.0, // Timeout for HTTP requests to microservices
        ]);
        $this->consulClient = $consulClient;
    }

    /**
     * Generic proxy method to forward requests to microservices.
     *
     * @param Request $request
     * @param string $serviceName The name of the target microservice (e.g., 'user-management-service')
     * @param string $path The path to forward (e.g., '/api/users')
     * @return \Illuminate\Http\JsonResponse
     */
    public function proxy(Request $request, $serviceName, $path = null)
    {
        try {
            // Discover service instance from Consul
            $services = $this->consulClient->catalog->service($serviceName)->json();

            if (empty($services)) {
                Log::warning("No healthy instances found for service: {$serviceName}");
                return response()->json(['message' => 'Service not found or unhealthy.'], 503); // Service Unavailable
            }

            // For simplicity, pick the first healthy instance. In production, use load balancing.
            $serviceInstance = $services[array_rand($services)];
            $targetUrl = "http://{$serviceInstance['ServiceAddress']}:{$serviceInstance['ServicePort']}";

            $fullPath = '/api/' . ($path ? $path : ''); // Construct the full path with /api/ prefix

            // Determine the method and options for the Guzzle request
            $method = $request->method();
            $options = [
                'headers' => $request->headers->all(),
                'query' => $request->query->all(),
            ];

            if ($request->isJson()) {
                $options['json'] = $request->json()->all();
            } else {
                $options['form_params'] = $request->all();
            }

            // Remove Host header to prevent issues with target service
            if (isset($options['headers']['host'])) {
                unset($options['headers']['host']);
            }

            Log::info("Forwarding request: {$method} {$targetUrl}{$fullPath} with options: " . json_encode($options));

            $response = $this->guzzleClient->request($method, $targetUrl . $fullPath, $options);

            return response($response->getBody(), $response->getStatusCode())
                ->withHeaders($response->getHeaders());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;
            $message = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            Log::error("Proxy request failed: " . $message);
            return response()->json(['message' => 'Error communicating with service.', 'details' => json_decode($message, true)], $statusCode);
        } catch (\Exception $e) {
            Log::error("Unexpected proxy error: " . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred.'], 500);
        }
    }
}
