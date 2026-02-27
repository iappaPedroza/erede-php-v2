<?php

namespace RedeV2\Service;

use RedeV2\Exception\RedeException;
use RuntimeException;

/**
 * Contract for OAuth 2.0 token providers used by the eRede API v2.
 *
 * Implementations must return a valid Bearer access_token.
 * Token caching and renewal are implementation responsibilities.
 */
interface OAuthServiceInterface
{
    /**
     * Returns a valid OAuth 2.0 access_token.
     *
     * Implementations should cache the token and only request a new one
     * when the current token is absent or about to expire.
     *
     * @return string
     * @throws RuntimeException  On transport/connection errors.
     * @throws RedeException     On OAuth authentication failures (4xx/5xx).
     */
    public function getAccessToken(): string;
}
