<?php
namespace Mochi\Middleware;

use Mochi\Http\Request;
use Mochi\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
