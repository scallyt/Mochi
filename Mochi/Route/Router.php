<?php

namespace Mochi\Route;

use Mochi\Attributes\Controller;
use Mochi\Attributes\Route;
use Mochi\Http\Request;
use Mochi\Http\Response;
use Mochi\Renderer\TemplateRenderer;
use ReflectionClass;

class Router
{
    private array $routes = [];
    private array $instances = [];
    private TemplateRenderer $renderer;

    public function __construct(TemplateRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Registers a controller class to the router.
     *
     * This method scans the given controller class for methods annotated with
     * the Route attribute and registers them as routes to the router. It also
     * checks if the controller class is annotated with the Controller attribute
     * to specify a prefix for all routes in the controller.
     *
     * @param string $controllerClass The fully qualified name of the controller class
     */
    public function register(string $controllerClass): void
    {
        // Create a reflection of the controller class to inspect its attributes and methods
        $reflection = new ReflectionClass($controllerClass);

        // Check if the controller class has a `@Controller` attribute and get its prefix if present
        $controllerAttr = $reflection->getAttributes(Controller::class)[0] ?? null;
        $prefix = $controllerAttr ? $controllerAttr->newInstance()->prefix : '';

        // Iterate over all methods of the controller class
        foreach ($reflection->getMethods() as $method) {
            // Get all Route attributes for the current method
            $routeAttrs = $method->getAttributes(Route::class);

            // Iterate over each Route attribute found
            foreach ($routeAttrs as $attr) {
                // Create an instance of the route attribute
                $route = $attr->newInstance();

                // Concatenate the prefix with the route path to form the full route path
                $routePath = $prefix . $route->path;

                // Convert the route path into a regex pattern to match dynamic segments
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);

                // Iterate over each HTTP method that the route supports
                foreach ($route->methods as $httpMethod) {
                    // Register the route with the HTTP method, storing the controller class, method name, and middleware
                    $this->routes[$pattern][$httpMethod] = [
                        'controller' => $controllerClass,
                        'method' => $method->name,
                        'middleware' => $route->middleware,
                    ];
                }
            }
        }
    }

    /**
     * Dispatches the given HTTP request to the corresponding controller action.
     *
     * This method looks up the given HTTP request in the registered routes and
     * executes the corresponding controller action. If the action returns a
     * Response instance, it is returned. Otherwise, a new Response instance is
     * created with the default status code 404 and a "Not Found" message.
     *
     * @param Request $request The HTTP request to dispatch
     * @return ?Response The response of the controller action, or null if none
     */
    public function dispatch(Request $request): ?Response
    {
    $uri = $request->server('REQUEST_URI') ?? '/'; // Get the URI of the request
    $method = $request->server('REQUEST_METHOD') ?? 'GET'; // Get the HTTP method of the request

    // Iterate over all registered routes to find a matching one
    foreach ($this->routes as $pattern => $actions) {
        // Check if the current route has an action registered for the given HTTP method
        if (isset($actions[$method])) {
            // Check if the given URI matches the route's regex pattern
            if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                // Get the controller class, action method, and parameters from the matching route
                $route = $actions[$method];
                [$controllerClass, $action] = [$route['controller'], $route['method']];
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Create an instance of the controller class
                $controller = $this->getInstance($controllerClass);

                // Inject the TemplateRenderer instance into the controller
                if (property_exists($controller, 'renderer')) {
                    $controller->renderer = $this->renderer;
                }

                // Create a new Response instance
                $response = new Response();

                // Define the final "next" function as the controller action itself
                // This "next" function will be passed to the middleware functions
                $finalNext = function() use ($controller, $action, $request, $response, $params) {
                    return $controller->$action($request, $response, ...$params);
                };

                // Execute middleware and then controller action
                // The middleware functions will be executed in reverse order
                // The "next" function will be called with the request and response as arguments
                // The middleware functions can modify the request and response before passing them on to the next middleware
                return $this->executeMiddleware($route['middleware'], $request, $finalNext);
            }
        }
    }

    // If no matching route was found, return a 404 response
    return new Response(404, '<h1>404 Not Found</h1>');
}


    /**
     * Execute the middleware chain and the controller action.
     *
     * The middleware functions are stored in an array and processed in reverse order.
     * The "next" function is the previous middleware function in the chain, or the
     * controller action if we're at the end of the chain.
     *
     * @param array $middleware List of middleware classes to process
     * @param Request $request The HTTP request to process
     * @param callable $finalNext The final "next" function in the chain, which is the
     *                            controller action
     *
     * @return Response The response of the controller action, or the response of the
     *                  last middleware function that returned a response
     */
    private function executeMiddleware(array $middleware, Request $request, callable $finalNext): Response
    {
        // Initialize the "next" function with the finalNext, which is the controller action
        $next = $finalNext;

        // Iterate over the middleware classes in reverse order
        // This allows us to process them like a stack,
        // ensuring the first middleware in the array is the last to execute
        foreach (array_reverse($middleware) as $middlewareClass) {
            // Redefine "next" as a new closure that wraps the current middleware class
            $next = function () use ($middlewareClass, $request, $next) {
                // Instantiate the middleware class
                $middlewareInstance = new $middlewareClass();
                // Call the handle method of the middleware class
                // Pass the request and the current "next" function to it
                return $middlewareInstance->handle($request, $next);
            };
        }

        // Start processing the middleware chain by calling the first "next" function
        // This will eventually execute the controller action
        return $next();
    }

    /**
     * Create an instance of a class, resolving any dependencies.
     *
     * The getInstance() method is a form of Dependency Injection. It's used to
     * create instances of classes that have dependencies, like a controller that
     * needs a View or a Model to work.
     *
     * If the class has a constructor, we resolve the dependencies by calling
     * getInstance() on each type hint. If the type hint is a built-in type, we
     * pass null as the dependency.
     *
     * The instance is stored in the $this->instances array so that we don't have
     * to create it again if it's requested again.
     *
     * @param string $className The class to instantiate
     *
     * @return object The instance of the class
     */
    private function getInstance(string $className): object
    {
        // If the class hasn't been instantiated before, create it
        if (!isset($this->instances[$className])) {
            // Get a reflection of the class so we can inspect it
            $classReflection = new ReflectionClass($className);

            // Get the constructor of the class so we can inspect its parameters
            $constructor = $classReflection->getConstructor();
            $parameters = $constructor ? $constructor->getParameters() : [];

            // Iterate over the parameters of the constructor and resolve the dependencies
            $dependencies = array_map(function (\ReflectionParameter $parameter) {
                // Get the type hint of the parameter, if it exists
                $type = $parameter->getType();

                // If the type hint is a class, instantiate it by calling getInstance() on it
                if ($type && !$type->isBuiltin()) {
                    // The type hint is a class, so instantiate it
                    $dependencyClass = $type->getName();
                    return $this->getInstance($dependencyClass);
                }

                // If the type hint is a built-in type, pass null as the dependency
                return null;
            }, $parameters);

            // Create the instance of the class, passing the dependencies to the constructor
            $this->instances[$className] = $classReflection->newInstanceArgs($dependencies);
        }

        // Return the instance of the class
        return $this->instances[$className];
    }
}
