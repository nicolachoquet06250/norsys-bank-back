<?php

namespace NorsysBank\controllers;

use lbuchs\WebAuthn\WebAuthn;
use NorsysBank\attributes\Controller;
use NorsysBank\attributes\Route;
use NorsysBank\enums\HttpMethod;
// use NorsysBank\utils\Jwt;
// use NorsysBank\utils\Router;

#[Controller]
#[Route('/')]
class Demo {
    #[Route('/')]
    public function client() {
        // echo '<pre>';
        // var_dump($_SESSION);
        // echo '</pre>';
        return file_get_contents(__DIR__.'/../templates/index.html');
    }
}