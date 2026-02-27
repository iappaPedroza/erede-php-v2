<?php

namespace Rede\Service;

use CurlHandle;
use Rede\Exception\RedeException;
use Rede\Store;
use RuntimeException;

/**
 * cURL-based OAuth 2.0 token provider for the eRede API v2.
 *
 * Obtains an access_token via the client_credentials grant and caches it in
 * the Store. A new token is requested automatically when the cached one is
 * absent or within 60 seconds of expiry.
 *
 * For a Guzzle-based alternative see {@see GuzzleOAuthService}.
 *
 * Endpoint (production): https://api.userede.com.br/redelabs/oauth2/token
 * Endpoint (sandbox):    https://rl7-sandbox-api.useredecloud.com.br/oauth2/token
 */
class OAuthService implements OAuthServiceInterface
{
    public function __construct(private readonly Store $store)
    {
    }

    /**
     * Returns a valid access_token, fetching a new one if the cached token
     * is absent or expired.
     *
     * @return string
     * @throws RuntimeException
     * @throws RedeException
     */
    public function getAccessToken(): string
    {
        $cached = $this->store->getAccessToken();

        if ($cached !== null) {
            return $cached;
        }

        return $this->fetchAccessToken();
    }

    /**
     * Fetches a new access_token from the OAuth 2.0 endpoint and stores it
     * in the Store for subsequent requests.
     *
     * @return string
     * @throws RuntimeException
     * @throws RedeException
     */
    private function fetchAccessToken(): string
    {
        $oauthEndpoint = $this->store->getEnvironment()->getOAuthEndpoint();

        $credentials = base64_encode(
            sprintf('%s:%s', $this->store->getFiliation(), $this->store->getToken())
        );

        $curl = curl_init($oauthEndpoint);

        if (!$curl instanceof CurlHandle) {
            throw new RuntimeException('Was not possible to create a curl instance for OAuth.');
        }

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            sprintf('Authorization: Basic %s', $credentials),
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            throw new RuntimeException(
                sprintf('OAuth cURL error[%d]: %s', curl_errno($curl), curl_error($curl))
            );
        }

        if (!is_string($response)) {
            throw new RuntimeException('OAuth: empty response from token endpoint.');
        }

        $data = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf('OAuth: invalid JSON response: %s', json_last_error_msg()));
        }

        if ($httpCode >= 400 || !isset($data->access_token)) {
            $message = $data->error_description ?? $data->error ?? 'OAuth authentication failed.';
            throw new RedeException((string) $message, $httpCode);
        }

        $expiresIn = (int) ($data->expires_in ?? 1440);

        $this->store->setAccessToken($data->access_token, $expiresIn);

        return $data->access_token;
    }
}
