<?php

namespace Rede;

class Brand
{
    use CreateTrait;

    /**
     * @var string|null
     */
    private ?string $name = null;

    /**
     * @var string|null
     */
    private ?string $returnCode = null;

    /**
     * @var string|null
     */
    private ?string $returnMessage = null;

    /**
     * @var string|null Authorization code returned by the card issuer.
     */
    private ?string $authorizationCode = null;

    /**
     * @var string|null Merchant Advice Code (MAC) - Mastercard only.
     */
    private ?string $merchantAdviceCode = null;

    /**
     * @var string|null Brand transaction identifier for recurring/card-on-file (brandTid).
     */
    private ?string $brandTid = null;

    /**
     * @var string|null Transaction Link ID for Mastercard recurring/card-on-file (v2).
     */
    private ?string $transactionLinkId = null;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return Brand
     */
    public function setName(?string $name): Brand
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReturnCode(): ?string
    {
        return $this->returnCode;
    }

    /**
     * @param string|null $returnCode
     * @return Brand
     */
    public function setReturnCode(?string $returnCode): Brand
    {
        $this->returnCode = $returnCode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReturnMessage(): ?string
    {
        return $this->returnMessage;
    }

    /**
     * @param string|null $returnMessage
     * @return Brand
     */
    public function setReturnMessage(?string $returnMessage): Brand
    {
        $this->returnMessage = $returnMessage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAuthorizationCode(): ?string
    {
        return $this->authorizationCode;
    }

    /**
     * @param string|null $authorizationCode
     * @return Brand
     */
    public function setAuthorizationCode(?string $authorizationCode): Brand
    {
        $this->authorizationCode = $authorizationCode;
        return $this;
    }

    /**
     * Merchant Advice Code (MAC) returned by Mastercard.
     *
     * @return string|null
     */
    public function getMerchantAdviceCode(): ?string
    {
        return $this->merchantAdviceCode;
    }

    /**
     * @param string|null $merchantAdviceCode
     * @return Brand
     */
    public function setMerchantAdviceCode(?string $merchantAdviceCode): Brand
    {
        $this->merchantAdviceCode = $merchantAdviceCode;
        return $this;
    }

    /**
     * Brand TID used to correlate recurring/card-on-file transactions.
     *
     * @return string|null
     */
    public function getBrandTid(): ?string
    {
        return $this->brandTid;
    }

    /**
     * @param string|null $brandTid
     * @return Brand
     */
    public function setBrandTid(?string $brandTid): Brand
    {
        $this->brandTid = $brandTid;
        return $this;
    }

    /**
     * Transaction Link ID (Mastercard, v2) used to correlate recurring/card-on-file
     * transactions. Replaces brandTid for Mastercard in 2026.
     *
     * @return string|null
     */
    public function getTransactionLinkId(): ?string
    {
        return $this->transactionLinkId;
    }

    /**
     * @param string|null $transactionLinkId
     * @return Brand
     */
    public function setTransactionLinkId(?string $transactionLinkId): Brand
    {
        $this->transactionLinkId = $transactionLinkId;
        return $this;
    }
}
