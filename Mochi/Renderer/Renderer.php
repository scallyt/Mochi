<?php

namespace Mochi\Renderer;

use Mochi\Http\Response;

class Renderer {
    private $config;

    public function __construct(array $config = []) {
        $this->config = $config;
    }

    public function renderJson(?int $status = 200, ?array $headers = [], array $data): Response {
        $jsonRenderer = new JsonRenderer();
        return $jsonRenderer->render(status: $status, headers: $headers, data: $data);
    }

    public function renderTwig(int $status, string $template, array $data = [], array $headers = []): Response {
        $renderer = new TemplateRenderer($this->config); 
        $content = $renderer->render(template: $template, data: $data);

        return new Response(status: $status, content: $content, headers: $headers);
    }
}
