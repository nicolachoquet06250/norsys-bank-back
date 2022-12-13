<?php

namespace NorsysBank\attributes;

use Attribute;
use NorsysBank\enums\HttpMethod;
use NorsysBank\utils\RouteCors;

#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD)]
class Route {
    private $target;
    private string $method = '__construct';

    public function __construct(
        private ?string $url = null,
        private RouteCors $cors = new RouteCors(),
        private HttpMethod $httpMethod = HttpMethod::GET
    )
    {}
    
    public function setTarget($target) {
        $this->target = $target;
    }

    public function setMethod(string $method) {
        $this->method = $method;
    }

    public function getTarget() {
        return $this->target;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function setUrl(string $url) {
        $this->url = $url;
    }

    public function getUrl(): ?string {
        return $this->url;
    }

    public function getCors(): RouteCors {
        return $this->cors;
    }

    public function getHttpMethod(): HttpMethod {
        return $this->httpMethod;
    }
}