<?php

namespace spicyweb\spicyafterpay\gateways\responses;

use craft\commerce\base\RequestResponseInterface;

class CheckoutResponse implements RequestResponseInterface
{
    protected $data;

    // Public Methods
    // =========================================================================

    /**
     * Construct the response
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function isSuccessful(): bool
    {
        return $this->data['id'] ?? false;
    }

    /**
     * @inheritDoc
     */
    public function isProcessing(): bool
    {
        $data = $this->data;

        if ($data === null) {
            return false;
        }

        return (isset($data['id'], $data['paymentState']) && ($data['paymentState'] === 'AUTH_APPROVED' || $data['paymentState'] === 'PARTIALLY_CAPTURED')) ?? false;
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
        return $this->data['id'] ?? '';
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
    public function getData()
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
    public function redirect()
    {
        // TODO: Implement redirect() method.
    }
}
