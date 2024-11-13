<?php

namespace Mochi\Http;

use Mochi\Route\Router;

class Kernel {
    private array $middleware = [];

    public function __construct(private Router $router) {}

    // Add a method to register global middleware
    public function addMiddleware(callable $middleware): void {
        $this->middleware[] = $middleware;
    }

    // Handle the request, apply global middleware first
    public function handle(Request $request): Response {
        // Execute global middleware
        foreach ($this->middleware as $middleware) {
            $response = $middleware($request);
            
            if ($response instanceof Response) {
                return $response;  // If middleware returns a response, stop and return it
            }
        }

        // Now dispatch the request to the router which will handle route-specific middleware
        $response = $this->router->dispatch($request);

        // Fallback if no response is returned from the router
        if (!$response) {
            $response = new Response(404, '<h1>404 Not Found</h1>');
        }

        return $response;
    }
}
