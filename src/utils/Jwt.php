<?php

namespace NorsysBank\utils;

use DateTime;
use Firebase\JWT\JWT as JwtService;
use Firebase\JWT\Key as JwtKeyService;

class Jwt {
    private string $key = 'norsys_bank';
    private array $payload = [
        'data' => [],
        'expires' => 20000
    ];
    
    public function __construct(private array|string|null $data = [])
    {
        $this->payload = [
            ...$this->data,
            'domain' => Router::instantiate()->getBaseUrl(),
            'referrer' => Router::instantiate()->getReferrer(),
            'created-at' => (new DateTime())->getTimestamp(),
            'data' => $data
        ];

        // $jwt = JwtService::encode($this->payload, $this->key, 'HS256');
        // var_dump($jwt);
        // $decoded = JwtService::decode($jwt, new JwtKeyService($this->key, 'HS256'));

        // var_dump($decoded);
        
        // /*
        //  NOTE: This will now be an object instead of an associative array. To get
        //  an associative array, you will need to cast it as such:
        // */
        
        // $decoded_array = (array) $decoded;
        
        // /**
        //  * You can add a leeway to account for when there is a clock skew times between
        //  * the signing and verifying servers. It is recommended that this leeway should
        //  * not be bigger than a few minutes.
        //  *
        //  * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
        //  */
        // JwtService::$leeway = 60; // $leeway in seconds
        // $decoded = JwtService::decode($jwt, new JwtKeyService($this->key, 'HS256'));
    }

    public function encode(): string
    {
        return JwtService::encode($this->payload, $this->key, 'HS256');
    }

    public function decode(string $token): array
    {
        $this->payload = (array) JwtService::decode($token, new JwtKeyService($this->key, 'HS256'));

        return $this->payload['data'];
    }

    public function setData(array $data = [])
    {
        $this->payload['data'] = $data;
    }

    public function setExpires(int $expires)
    {
        $this->payload['expires'] = $expires;
    }

    public function isValid(): bool
    {
        return true;
    }
}