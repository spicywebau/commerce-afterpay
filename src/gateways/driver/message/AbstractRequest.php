<?php

namespace spicyweb\spicyafterpay\gateways\driver\message;

use spicyweb\spicyafterpay\gateways\driver\message\Response;

use Omnipay\Common\Message\AbstractRequest as OmniAbstractRequest;

abstract class AbstractRequest extends OmniAbstractRequest
{
    protected $liveAUEndpoint = 'https://api.afterpay.com';
    protected $liveUSEndpoint = 'https://api.us.afterpay.com';
    protected $testAUEndpoint = 'https://api-sandbox.afterpay.com';
    protected $testUSEndpoint = 'https://api.us-sandbox.afterpay.com';
    
    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }
    
    public function setMerchantId($value)
    {
        return $this->setParameter('merchantId', $value);
    }
    
    public function getMerchantKey()
    {
        return $this->getParameter('merchantKey');
    }
    
    public function setMerchantKey($value)
    {
        return $this->setParameter('merchantKey', $value);
    }
    
    public function getRegion()
    {
        return $this->getParameter('region');
    }
    
    public function setRegion($value)
    {
        return $this->setParameter('region', $value);
    }
    
    public function sendData($data)
    {
        $endpoint = $this->getEndpoint();
        
        $headers = [
            'Authorization' => $this->buildAuthorizationHeader(),
            'Content-type' => 'application/json',
            'Accept' => 'application/json'
        ];
        
        $httpResponse = $this->httpClient->request('POST', $endpoint, $headers, http_build_query($data, '', '&'));
        
        return $this->createResponse($httpResponse->getBody()->getContents());
    }
    
    protected function getEndpoint()
    {
        $isAUOrNZ = $this->getRegion() === 'AU' || $this->getRegion() === 'NZ';
        
        if ($isAUOrNZ) {
            $url = $this->getTestMode() ? $this->testAUEndpoint : $this->liveAUEndpoint;
        } else {
            $url = $this->getTestMode() ? $this->testUSEndpoint : $this->liveUSEndpoint;
        }
        
        return $url . '/v1/';
    }
    
    protected function buildAuthorizationHeader()
    {
        $merchantId = $this->getMerchantId();
        $merchantSecret = $this->getMerchantKey();
        
        return 'Basic ' . base64_encode($merchantId . ':' . $merchantSecret);
    }
    
    protected function createResponse($data)
    {
        return $this->response = new Response($this, $data);
    }
}