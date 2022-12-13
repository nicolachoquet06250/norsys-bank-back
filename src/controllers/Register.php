<?php

namespace NorsysBank\controllers;

use NorsysBank\attributes\{
    Controller,
    Route
};
use NorsysBank\enums\HttpMethod;
use NorsysBank\models\User as ModelsUser;
use NorsysBank\repositories\User;
use lbuchs\WebAuthn\WebAuthn;

#[Controller]
#[Route]
class Register {
    public function __construct(
        private array $query = []
    ) {}

    #[Route]
    public function begin()
    {
        if (!isset($this->query['email'])) {
            return "must supply a valid email i.e. foo@bar.com";
        }

        ['email' => $email] = $this->query;

        $user = User::findFromEmail($email);

        if (is_null($user)) {
            $displayName = explode("@", $email)[0];
            $user = new ModelsUser(name: $email, displayName: $displayName);
        }

        return 'coucou toi !! ' . $email;
    }

    #[Route(httpMethod: HttpMethod::POST)]
    public function finish()
    {
        return 'coucou toi !!';
    }
}