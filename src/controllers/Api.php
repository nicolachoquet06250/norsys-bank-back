<?php

namespace NorsysBank\controllers;

use lbuchs\WebAuthn\WebAuthn;
use NorsysBank\attributes\Controller;
use NorsysBank\attributes\Route;
use NorsysBank\enums\HttpMethod;

#[Controller]
#[Route]
class Api {
    private array $formats = [];
    private string $userName;
    private string $userDisplayName;
    private string $userVerification = 'discouraged';
    private string $userId;
    private ?bool $crossPlatformAttachment = null;
    private bool $typeUsb;
    private bool $typeNfc;
    private bool $typeBle;
    private bool $typeInt;
    private mixed $post;
    private ?WebAuthn $webAuthn = null;

    public function __construct()
    {
        if ($_SERVER['REQUEST_URI'] !== '/') {
            // read get argument and post body
            $this->requireResidentKey = !!filter_input(INPUT_GET, 'requireResidentKey');
            $this->userVerification = filter_input(INPUT_GET, 'userVerification', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'discouraged';

            $this->userId = filter_input(INPUT_GET, 'userId', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
            $this->userName = filter_input(INPUT_GET, 'userName', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
            $this->userDisplayName = filter_input(INPUT_GET, 'userDisplayName', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

            $this->userId = preg_replace('/[^0-9a-f]/i', '', $this->userId);
            $this->userName = preg_replace('/[^0-9a-z]/i', '', $this->userName);
            $this->userDisplayName = preg_replace('/[^0-9a-z öüäéèàÖÜÄÉÈÀÂÊÎÔÛâêîôû]/i', '', $this->userDisplayName);

            $this->post = trim(file_get_contents('php://input'));
            if ($this->post) {
                $this->post = json_decode($this->post);
            }
        }
    }

    private function common() {
        // Formats
        if (filter_input(INPUT_GET, 'fmt_android-key')) {
            $this->formats[] = 'android-key';
        }
        if (filter_input(INPUT_GET, 'fmt_android-safetynet')) {
            $this->formats[] = 'android-safetynet';
        }
        if (filter_input(INPUT_GET, 'fmt_apple')) {
            $this->formats[] = 'apple';
        }
        if (filter_input(INPUT_GET, 'fmt_fido-u2f')) {
            $this->formats[] = 'fido-u2f';
        }
        if (filter_input(INPUT_GET, 'fmt_none')) {
            $this->formats[] = 'none';
        }
        if (filter_input(INPUT_GET, 'fmt_packed')) {
            $this->formats[] = 'packed';
        }
        if (filter_input(INPUT_GET, 'fmt_tpm')) {
            $this->formats[] = 'tpm';
        }

        $rpId = 'localhost';
        if (filter_input(INPUT_GET, 'rpId')) {
            $rpId = filter_input(INPUT_GET, 'rpId', FILTER_VALIDATE_DOMAIN);
            if ($rpId === false) {
                throw new \Exception('invalid relying party ID');
            }
        }

        // types selected on front end
        $this->typeUsb = !!filter_input(INPUT_GET, 'type_usb');
        $this->typeNfc = !!filter_input(INPUT_GET, 'type_nfc');
        $this->typeBle = !!filter_input(INPUT_GET, 'type_ble');
        $this->typeInt = !!filter_input(INPUT_GET, 'type_int');

        // cross-platform: true, if type internal is not allowed
        //                 false, if only internal is allowed
        //                 null, if internal and cross-platform is allowed
        if (($this->typeUsb || $this->typeNfc || $this->typeBle) && !$this->typeInt) {
            $this->crossPlatformAttachment = true;

        } else if (!$this->typeUsb && !$this->typeNfc && !$this->typeBle && $this->typeInt) {
            $this->crossPlatformAttachment = false;
        }

        // new Instance of the server library.
        // make sure that $rpId is the domain name.
        $this->webAuthn = new WebAuthn('Norsys Bank', $rpId, $this->formats);
        
        // add root certificates to validate new registrations
        if (filter_input(INPUT_GET, 'solo')) {
            $this->webAuthn->addRootCertificates('rootCertificates/solo.pem');
        }
        if (filter_input(INPUT_GET, 'apple')) {
            $this->webAuthn->addRootCertificates('rootCertificates/apple.pem');
        }
        if (filter_input(INPUT_GET, 'yubico')) {
            $this->webAuthn->addRootCertificates('rootCertificates/yubico.pem');
        }
        if (filter_input(INPUT_GET, 'hypersecu')) {
            $this->webAuthn->addRootCertificates('rootCertificates/hypersecu.pem');
        }
        if (filter_input(INPUT_GET, 'google')) {
            $this->webAuthn->addRootCertificates('rootCertificates/globalSign.pem');
            $this->webAuthn->addRootCertificates('rootCertificates/googleHardware.pem');
        }
        if (filter_input(INPUT_GET, 'microsoft')) {
            $this->webAuthn->addRootCertificates('rootCertificates/microsoftTpmCollection.pem');
        }
        if (filter_input(INPUT_GET, 'mds')) {
            $this->webAuthn->addRootCertificates('rootCertificates/mds');
        }
    }

    #[Route]
    public function getStoredDataHtml() {
        try {
            $html = <<<HTML
                <!DOCTYPE html>
                <html>
                    <head>
                        <style>
                            tr:nth-child(even) {
                                background-color: #f2f2f2;
                            }
                        </style>
                    </head>
                    <body style="font-family:sans-serif">
            HTML;
            
            if (isset($_SESSION['registrations']) && is_array($_SESSION['registrations'])) {
                $nbRegistrations = count($_SESSION['registrations']);

                $html .= <<<HTML
                    <p>There are {$nbRegistrations} registrations in this session:</p>
                HTML;
                foreach ($_SESSION['registrations'] as $reg) {
                    $html .= <<<HTML
                        <table style="border:1px solid black;margin:10px 0;">
                    HTML;

                    foreach ($reg as $key => $value) {
                        if (is_bool($value)) {
                            $value = $value ? 'yes' : 'no';
                        } else if (is_null($value)) {
                            $value = 'null';
                        } else if (is_object($value)) {
                            $value = chunk_split(strval($value), 64);
                        } else if (is_string($value) && strlen($value) > 0 && htmlspecialchars($value, ENT_QUOTES) === '') {
                            $value = chunk_split(bin2hex($value), 64);
                        }

                        $key = htmlspecialchars($key);
                        $value = nl2br(htmlspecialchars($value));

                        $html .= <<<HTML
                            <tr>
                                <td>{$key}</td>

                                <td style="font-family:monospace;">
                                    {$value}
                                </td>
                        HTML;
                    }
                    $html .= '</table>';
                }
            } else {
                $html .= <<<HTML
                    <p>There are no registrations in this session.</p>
                HTML;
            }

            $html .= <<<HTML
                    </body>
                </html>
            HTML;
            
            return $html;
        } catch (\Throwable $ex) {
            $return = [
                'success' => false,
                'msg' => $ex->getMessage()
            ];
        
            header('Content-Type: application/json');
            print(json_encode($return));
        }
    }

    #[Route]
    public function getCreateArgs() {
        header('Content-Type: application/json');

        try {
            $this->common();
            
            $return = $this->webAuthn->getCreateArgs(\hex2bin($this->userId), $this->userName, $this->userDisplayName, 20, $this->requireResidentKey, $this->userVerification, $this->crossPlatformAttachment);
        
            // save challange to session. you have to deliver it to processGet later.
            $_SESSION['challenge'] = $this->webAuthn->getChallenge();

            // $headers = apache_request_headers();
            // $headers['Authorization']
            // if (Router::instantiate()->getBaseUrl() !== Router::instantiate()->getReferrer()) {
            //     $jwt = new Jwt((array) $return);
            //     $token = $jwt->encode();

            //     $return->token = $token;
            // }
        } catch (\Throwable $ex) {
            $return = [
                'success' => false,
                'msg' => $ex->getMessage()
            ];
        } finally {
            return json_encode($return);
        }
    }

    #[Route]
    public function getGetArgs() {
        try {
            $this->common();
            
            $ids = array();
            if ($this->requireResidentKey) {
                if (!is_array($_SESSION['registrations']) || count($_SESSION['registrations']) === 0) {
                    throw new \Exception('we do not have any registrations in session to check the registration');
                }
            } else {
                // load registrations from session stored there by processCreate.
                // normaly you have to load the credential Id's for a username
                // from the database.
                if (is_array($_SESSION['registrations'])) {
                    foreach ($_SESSION['registrations'] as $reg) {
                        if ($reg->userId === $this->userId) {
                            $ids[] = $reg->credentialId;
                        }
                    }
                }
        
                if (count($ids) === 0) {
                    throw new \Exception('no registrations in session for userId ' . $this->userId);
                }
            }
        
            $return = $this->webAuthn->getGetArgs($ids, 20, $this->typeUsb, $this->typeNfc, $this->typeBle, $this->typeInt, $this->userVerification);
        
            header('Content-Type: application/json');
        
            // save challange to session. you have to deliver it to processGet later.
            $_SESSION['challenge'] = $this->webAuthn->getChallenge();
        } catch (\Throwable $ex) {
            $return = [
                'success' => false,
                'msg' => $ex->getMessage()
            ];
        
            header('Content-Type: application/json');
        } finally {
            return json_encode($return);
        }
    }

    #[Route(httpMethod: HttpMethod::POST)]
    public function processCreate() {
        // $headers = apache_request_headers();
        // if ($headers['Authorization'] && Router::instantiate()->getBaseUrl() !== Router::instantiate()->getReferrer()) {
        //     $jwt = new Jwt();
        //     $data = $jwt->decode($headers['Authorization']);
        //     var_dump($data);
        // }

        header('Content-Type: application/json');

        try {
            $this->common();

            $clientDataJSON = base64_decode($this->post->clientDataJSON);
            $attestationObject = base64_decode($this->post->attestationObject);
            $challenge = $_SESSION['challenge'];

            // processCreate returns data to be stored for future logins.
            // in this example we store it in the php session.
            // Normaly you have to store the data in a database connected
            // with the user name.
            $data = $this->webAuthn->processCreate($clientDataJSON, $attestationObject, $challenge, $this->userVerification === 'required', true, false);

            // add user infos
            $data->userId = $this->userId;
            $data->userName = $this->userName;
            $data->userDisplayName = $this->userDisplayName;

            if (!array_key_exists('registrations', $_SESSION) || !is_array($_SESSION['registrations'])) {
                $_SESSION['registrations'] = [];
            }

            $_SESSION['registrations'][] = $data;
        
            $msg = 'registration success.';
            if ($data->rootValid === false) {
                $msg = 'registration ok';//.', but certificate does not match any of the selected root ca.';
            }

            foreach ($data as $key => $value) {
                /*if (is_object($value)) {
                    $value = chunk_split(strval($value), 64);
                } else */if (is_string($value) && strlen($value) > 0 && htmlspecialchars($value, ENT_QUOTES) === '') {
                    $value = chunk_split(bin2hex($value), 64);
                }
                $data->$key = $value;
            }

            $registrations = [];
            foreach ($_SESSION['registrations'] as $id => $registration) {
                foreach ($registration as $key => $value) {
                    /*if (is_object($value)) {
                        $value = chunk_split(strval($value), 64);
                    } else */if (is_string($value) && strlen($value) > 0 && htmlspecialchars($value, ENT_QUOTES) === '') {
                        $value = chunk_split(bin2hex($value), 64);
                    }
                    $registration->$key = $value;
                }
                $registrations[$id] = $registration;
            }

            $return = [
                'success' => true,
                'msg' => $msg,
                'data' => [
                    'registration' => $data,
                    'registrations' => $registrations
                ]
            ];
        } catch (\Throwable $ex) {
            $return = [
                'success' => false,
                'msg' => $ex->getMessage()
            ];
        } finally {
            return json_encode($return);
        }
    }

    #[Route(httpMethod: HttpMethod::POST)]
    public function processGet() {
        try {
            $this->common();
        
            $clientDataJSON = base64_decode($this->post->clientDataJSON);
            $authenticatorData = base64_decode($this->post->authenticatorData);
            $signature = base64_decode($this->post->signature);
            $userHandle = base64_decode($this->post->userHandle);
            $id = base64_decode($this->post->id);
            $challenge = $_SESSION['challenge'];
            $credentialPublicKey = null;
        
            // looking up correspondending public key of the credential id
            // you should also validate that only ids of the given user name
            // are taken for the login.
            if (is_array($_SESSION['registrations'])) {
                foreach ($_SESSION['registrations'] as $reg) {
                    if ($reg->credentialId === $id) {
                        $credentialPublicKey = $reg->credentialPublicKey;
                        break;
                    }
                }
            }
        
            if ($credentialPublicKey === null) {
                throw new \Exception('Public Key for credential ID not found!');
            }
        
            // if we have resident key, we have to verify that the userHandle is the provided userId at registration
            if ($this->requireResidentKey && $userHandle !== hex2bin($reg->userId)) {
                throw new \Exception('userId doesnt match (is ' . bin2hex($userHandle) . ' but expect ' . $reg->userId . ')');
            }
        
            // process the get request. throws WebAuthnException if it fails
            $this->webAuthn->processGet($clientDataJSON, $authenticatorData, $signature, $credentialPublicKey, $challenge, null, $this->userVerification === 'required');
        
            $return = [
                'success' => true
            ];
        
            header('Content-Type: application/json');
            return json_encode($return);
        } catch (\Throwable $ex) {
            $return = [
                'success' => false,
                'msg' => $ex->getMessage()
            ];
        
            header('Content-Type: application/json');
            return json_encode($return);
        }
    }

    #[Route]
    public function clearRegistrations() {
        try {
            $this->common();

            $_SESSION['registrations'] = null;
            $_SESSION['challenge'] = null;
        
            $return = [
                'success' => true,
                'msg' => 'all registrations deleted'
            ];
        
            header('Content-Type: application/json');
        } catch (\Throwable $ex) {
            $return = [
                'success' => false,
                'msg' => $ex->getMessage()
            ];
        
            header('Content-Type: application/json');
        } finally {
            return json_encode($return);
        }
    }

    #[Route]
    public function queryFidoMetaDataService() {
        try {
            $this->common();
        
            $mdsFolder = 'rootCertificates/mds';
            $success = false;
            $msg = null;
        
            // fetch only 1x / 24h
            $lastFetch = \is_file($mdsFolder .  '/lastMdsFetch.txt') ? \strtotime(\file_get_contents($mdsFolder .  '/lastMdsFetch.txt')) : 0;
            if ($lastFetch + (3600*48) < \time()) {
                $cnt = $this->webAuthn->queryFidoMetaDataService($mdsFolder);
                $success = true;
                \file_put_contents($mdsFolder .  '/lastMdsFetch.txt', date('r'));
                $msg = 'successfully queried FIDO Alliance Metadata Service - ' . $cnt . ' certificates downloaded.';
        
            } else {
                $msg = 'Fail: last fetch was at ' . date('r', $lastFetch) . ' - fetch only 1x every 48h';
            }
        
            $return = [
                'success' => $success,
                'msg' => $msg
            ];
        
            header('Content-Type: application/json');
        } catch (\Throwable $ex) {
            $return = [
                'success' => false,
                'msg' => $ex->getMessage()
            ];
        
            header('Content-Type: application/json');
        } finally {
            return json_encode($return);
        }
    }
}