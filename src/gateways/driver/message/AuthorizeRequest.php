<?php
namespace spicyweb\spicyafterpay\gateways\driver\message;

use spicyweb\spicyafterpay\gateways\driver\message\AbstractRequest;

class AuthorizeRequest extends AbstractRequest{
    
    /**
     * @inheritDoc
     */
    public function getData()
    {
    
    }
    
    protected function getEndpoint()
    {
        return parent::getEndpoint() . 'orders';
    }
}