<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SensioLabs\Consul\Consul; // Make sure to use the correct namespace
use Illuminate\Support\Facades\Log;

class ConsulServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Consul::class, function ($app) {
            return new Consul(['base_uri' => env('CONSUL_HOST', 'http://consul:8500')]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $consul = $this->app->make(Consul::class);
        $serviceId = 'user-management-service-' . gethostname(); // Unique ID for the service instance
        $serviceName = 'user-management-service'; // The name of your service

        $port = env('APP_PORT', 80); // Assuming your Docker container runs on port 80

        try {
            $consul->agent->serviceRegister([
                'ID' => $serviceId,
                'Name' => $serviceName,
                'Address' => gethostbyname(gethostname()), // Get container's IP
                'Port' => (int)$port,
                'Check' => [
                    'HTTP' => 'http://' . gethostbyname(gethostname()) . ':' . $port . '/api/health', // Health check endpoint
                    'Interval' => '10s',
                    'Timeout' => '1s'
                ]
            ]);
            Log::info("Service {$serviceName} registered with ID {$serviceId} on Consul.");
        } catch (\Exception $e) {
            Log::error("Failed to register service with Consul: " . $e->getMessage());
        }

        // Register shutdown function to deregister service
        register_shutdown_function(function () use ($consul, $serviceId, $serviceName) {
            try {
                $consul->agent->serviceDeregister($serviceId);
                Log::info("Service {$serviceName} with ID {$serviceId} deregistered from Consul.");
            } catch (\Exception $e) {
                Log::error("Failed to deregister service with Consul: " . $e->getMessage());
            }
        });
    }
}
