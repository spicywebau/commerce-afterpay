<?php

namespace spicyweb\spicyafterpay\gateways\responses;

use craft\commerce\base\RequestResponseInterface;

/**
 * Class RefundResponse
 *
 * @package spicyweb\spicyafterpay\gateways\responses
 * @author Spicy Web <plugins@spicyweb.com.au>
 * @since 1.0.0
 */
class RefundResponse implements RequestResponseInterface
{
    protected ?array $data;

    // Public Methods
    // =========================================================================

    /**
     * Construct the response
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function isSuccessful(): bool
    {
        return $this->data['refundId'] ?? false;
    }

    /**
     * @inheritDoc
     */
    public function isProcessing(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isRedirect(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getRedirectMethod(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getRedirectData(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUrl(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getTransactionReference(): string
    {
        return $this->data['refundId'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return $this->data['statusCode'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string
    {
        return $this->data['message'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function redirect(): void
    {
    }
}
