<?php

namespace spicyweb\spicyafterpay\gateways\driver\message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

class Response extends AbstractResponse
{
    public function __construct(RequestInterface $request, $data)
    {
        $this->request = $request;
        parse_str($data, $this->data);
        
        parent::__construct($request, $data);
    }
    
    public function isSuccessful()
    {
        return $this->getCode() === 201;
    }
}