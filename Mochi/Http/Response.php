<?php

namespace Mochi\Http;

class Response {
    public function __construct(
        public int $status = 200,
        public string|array|null $content = "",
        public array $headers = [],
        public bool $json = false
    ) {}

    public function send(): void {
        if ($this->json) {
            $this->headers[] = 'Content-Type: application/json';
            $this->content = json_encode($this->content);
        }

        foreach ($this->headers as $header) {
            header($header);
        }

        http_response_code($this->status);
        echo $this->content;
    }
}
