<?php

namespace NorsysBank\models;

class User {
    public function __construct(
        private ?float $id = null,
        private string $name,
        private string $displayName
    )
    {
        if (is_null($id)) {
            $this->id = $this->random_float(10000000000000000000, 99999999999999999999);
        }
    }

    private function random_float(float $min, float $max): float {
        return random_int($min, $max - 1) + (random_int(0, PHP_INT_MAX - 1) / PHP_INT_MAX );
    }

    public function getId(): float
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->name;
    }

    public function getName(): string
    {
        return $this->displayName;
    }
}