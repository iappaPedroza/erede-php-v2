<?php

namespace Rede;

class Store
{
    /**
     * Which environment will this store used for?
     * @var Environment
     */
    private Environment $environment;

    /**
     * @var string|null OAuth 2.0 access token (cached)
     */
    private ?string $accessToken = null;

    /**
     * @var int|null Timestamp when the access token expires
     */
    private ?int $accessTokenExpiry = null;

    /**
     * Creates a store.
     *
     * @param string           $filiation  The PV / clientId
     * @param string           $token      The integration key / clientSecret
     * @param Environment|null $environment if none provided, production will be used.
     */
    public function __construct(
        private string $filiation,
        private string $token,
        ?Environment $environment = null
    ) {
        $this->environment = $environment ?? Environment::production();
    }

    /**
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * @param Environment $environment
     *
     * @return $this
     */
    public function setEnvironment(Environment $environment): static
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * @return string
     */
    public function getFiliation(): string
    {
        return $this->filiation;
    }

    /**
     * @param string $filiation
     *
     * @return $this
     */
    public function setFiliation(string $filiation): static
    {
        $this->filiation = $filiation;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return $this
     */
    public function setToken(string $token): static
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Returns the cached OAuth 2.0 access token, or null if not set / expired.
     *
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        if ($this->accessToken === null) {
            return null;
        }

        // Consider expired if within 60 seconds of expiry
        if ($this->accessTokenExpiry !== null && time() >= ($this->accessTokenExpiry - 60)) {
            $this->accessToken = null;
            $this->accessTokenExpiry = null;
            return null;
        }

        return $this->accessToken;
    }

    /**
     * Stores an OAuth 2.0 access token with its expiry duration.
     *
     * @param string $accessToken
     * @param int    $expiresIn   Seconds until the token expires (from API response)
     *
     * @return $this
     */
    public function setAccessToken(string $accessToken, int $expiresIn = 1440): static
    {
        $this->accessToken = $accessToken;
        $this->accessTokenExpiry = time() + $expiresIn;
        return $this;
    }

    /**
     * Invalidates the cached access token, forcing a new fetch on next request.
     *
     * @return $this
     */
    public function invalidateAccessToken(): static
    {
        $this->accessToken = null;
        $this->accessTokenExpiry = null;
        return $this;
    }
}
