<?php

namespace Rede\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Rede\Exception\RedeException;
use Rede\Store;
use RuntimeException;

/**
 * Guzzle-based OAuth 2.0 token provider for the eRede API v2.
 *
 * Use this implementation when your project already depends on
 * guzzlehttp/guzzle (^7.0) and you prefer a higher-level HTTP client
 * over the built-in cURL implementation.
 *
 * Installation:
 *   composer require guzzlehttp/guzzle
 *
 * Usage:
 *   $oauthService = new GuzzleOAuthService($store);
 *   $eRede = new eRede($store, logger: null, oauthService: $oauthService);
 */
class GuzzleOAuthService implements OAuthServiceInterface
{
    private Client $client;

    /**
     * @param Store       $store  The store holding credentials (PV/filiation + token).
     * @param Client|null $client Optional pre-configured Guzzle client for testing/customisation.
     */
    public function __construct(private readonly Store $store, ?Client $client = null)
    {
        $this->client = $client ?? new Client([
            'timeout'         => 10,
            'connect_timeout' => 5,
            'verify'          => true,
        ]);
    }

    /**
     * Returns a valid access_token, fetching a new one when the cached
     * token is absent or close to expiry.
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

        try {
            $response = $this->client->post($oauthEndpoint, [
                'headers' => [
                    'Authorization' => sprintf('Basic %s', $credentials),
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException(
                sprintf('OAuth Guzzle error: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        $httpCode = $response->getStatusCode();
        $body     = (string) $response->getBody();

        $data = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                sprintf('OAuth: invalid JSON response: %s', json_last_error_msg())
            );
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
