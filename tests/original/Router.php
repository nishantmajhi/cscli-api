<?php

class Router {
    private $uri;
    private $segments;

    public function __construct() {
        $this->uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $this->segments = explode('/', $this->uri);
    }

    public function getUri() {
        return $this->uri;
    }

    public function getSegments() {
        return $this->segments;
    }

    public function response($body, $status = 200) {
        http_response_code($status);
        return new class ($body) {
            private $body;
            public function __construct($body) {
                $this->body = $body;
            }
            public function head($key, $value) {
                header("$key: $value");
                echo $this->body;
                exit;
            }
        };
    }
}
