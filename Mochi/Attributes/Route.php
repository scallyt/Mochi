<?php

namespace Mochi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route {
    public string $path;
    public array $methods;
    public array $middleware;

    public function __construct(string $path, array $methods = ['GET'], array $middleware = []) {
        $this->path = $path;
        $this->methods = $methods;
        $this->middleware = $middleware;
    }
}
