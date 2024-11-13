<?php

namespace Mochi\Middleware;

use Mochi\Http\Request;
use Mochi\Http\Response;

class JsonMiddleware implements MiddlewareInterface {
    public function handle(Request $request, callable $next): Response {
        if ($request->header("content-type") !== "application/json") {
            return new Response(400, json_encode(["error" => "Invalid Content-Type, expected application/json"]));
        }

        $response = $next();
        
        if ($response === null) {
            return new Response(200, json_encode(["message" => "Request successfully processed"]));
        }

        return $response;
    }
}
