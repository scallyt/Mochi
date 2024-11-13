<?php
namespace Mochi\Http;

use Mochi\Validator\Validator;
use Mochi\Exceptions\ValidationException;

class Request
{
    private $data;
    private $headers;
    private $server;

    public static function createFromGlobals(): self
{
    $request = new self();
    $request->headers = getallheaders();
    $request->server = $_SERVER;

    $input = file_get_contents('php://input');

    $contentType = $request->headers['Content-Type'] ?? $request->headers['content-type'] ?? null;
    
    if ($contentType && stripos($contentType, 'application/json') !== false) {
        $json = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $request->data = [];
        } else {
            $request->data = $json;
        }
    } else {
        $request->data = $_REQUEST;
    }

    return $request;
}

    private function parseJsonBody()
    {
        if (isset($this->headers['Content-Type']) && stripos($this->headers['Content-Type'], 'application/json') !== false) {
            $json = json_decode(file_get_contents('php://input'), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ValidationException(['Invalid JSON payload.']);
            }

            return $json;
        }
        return null;
    }

    public function validate(array $rules)
{
    $validator = new Validator();
    $validator->validate($this->data, $rules);

    $errors = $validator->getErrors();
    if (!empty($errors)) {
        return ['errors' => $errors];
    }
    
    return $this->data;
}

    public function getParams($key = null, $default = null)
    {
        return $key ? ($this->data[$key] ?? $default) : $this->data;
    }

    public function all()
    {
        return $this->data;
    }

    public function input($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function headers()
    {
        return $this->headers;
    }

    public function header($key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    public function server($key = null)
    {
        return $key ? ($this->server[$key] ?? null) : $this->server;
    }

    public function method()
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function statusCode()
    {
        return http_response_code();
    }

    public function setStatusCode($code)
    {
        http_response_code($code);
    }
}
