<?php

namespace Mochi\Renderer;

use Mochi\Http\Response;

class JsonRenderer {
    public static function render(?int $status = 200, ?array $headers = [], array $data): Response {
        return new Response($status, $data, $headers, true);
    }
}
