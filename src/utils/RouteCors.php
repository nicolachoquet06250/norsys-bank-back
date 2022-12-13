<?php

namespace NorsysBank\utils;

class RouteCors {
    public function __construct(
        private array $origins = ['*'],
        private array $methods = ['*']
    )
    {}

    public function getOrigins(): string|array
    {
        return $this->origins;
    }

    public function getMethods(): string|array
    {
        return $this->methods;
    }
}