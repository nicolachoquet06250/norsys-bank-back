<?php

namespace NorsysBank\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Controller {
    private $target;

    public function __construct()
    {}

    public function setTarget($target) {
        $this->target = $target;
    }

    public function getTarget() {
        return $this->target;
    }
}