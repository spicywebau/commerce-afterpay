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
    public function __construct($data) {
        $this->data = $data;
    }
    
    /**
     * @inheritDoc
     */
    public function isSuccessful(): bool
    {
        // TODO: Implement isSuccessful() method.
    }
    
    /**
     * @inheritDoc
     */
    public function isProcessing(): bool
    {
        // TODO: Implement isProcessing() method.
    }
    
    /**
     * @inheritDoc
     */
    public function isRedirect(): bool
    {
        // TODO: Implement isRedirect() method.
    }
    
    /**
     * @inheritDoc
     */
    public function getRedirectMethod(): string
    {
        // TODO: Implement getRedirectMethod() method.
    }
    
    /**
     * @inheritDoc
     */
    public function getRedirectData(): array
    {
        // TODO: Implement getRedirectData() method.
    }
    
    /**
     * @inheritDoc
     */
    public function getRedirectUrl(): string
    {
        // TODO: Implement getRedirectUrl() method.
    }
    
    /**
     * @inheritDoc
     */
    public function getTransactionReference(): string
    {
        // TODO: Implement getTransactionReference() method.
    }
    
    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        // TODO: Implement getCode() method.
    }
    
    /**
     * @inheritDoc
     */
    public function getData()
    {
        // TODO: Implement getData() method.
    }
    
    /**
     * @inheritDoc
     */
    public function getMessage(): string
    {
        // TODO: Implement getMessage() method.
    }
    
    /**
     * @inheritDoc
     */
    public function redirect()
    {
        // TODO: Implement redirect() method.
    }
}

