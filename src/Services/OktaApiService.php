<?php
namespace App\Services;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OktaApiService
{
    private $session;
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $metadataUrl;

    public function __construct(SessionInterface $session)
    {
        $this->session      = $session;
        $this->clientId     = $_ENV['OKTA_CLIENT_ID'];
        $this->clientSecret = $_ENV['OKTA_CLIENT_SECRET'];
        $this->redirectUri  = $_ENV['OKTA_REDIRECT_URI'];
        $this->metadataUrl  = $_ENV['OKTA_METADATA_URL'];
    }

    public function buildAuthorizeUrl()
    {
        $this->session->set('state', bin2hex(random_bytes(5)));
        $metadata = $this->httpRequest($this->metadataUrl);
        $url = $metadata->authorization_endpoint . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $this->session->get('state')
        ]);
        return $url;
    }

    public function authorizeUser()
    {
        if ($this->session->get('state') != $_GET['state']) {
            return null;
        }

        if (isset($_GET['error'])) {
            return null;
        }

        $metadata = $this->httpRequest($this->metadataUrl);

        $response = $this->httpRequest($metadata->token_endpoint, [
            'grant_type' => 'authorization_code',
            'code' => $_GET['code'],
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ]);

        if (! isset($response->access_token)) {
            return null;
        }

        $this->session->set('access_token', $response->access_token);

        $token = $this->httpRequest($metadata->introspection_endpoint, [
            'token' => $response->access_token,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ]);

        if ($token->active == 1) {
            return $token;
        }

        return null;
    }

    private function httpRequest($url, $params = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($params) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        return json_decode(curl_exec($ch));
    }
}
