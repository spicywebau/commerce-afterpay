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
    
    protected function getBaseData()
    {
        $data = array();
        
        return $data;
    }
    
    public function sendData($data)
    {
        $httpResponse = $this->httpClient->request('POST', $this->getEndpoint(), [], http_build_query($data, '', '&'));
        
        return $this->createResponse($httpResponse->getBody()->getContents());
    }
    
    protected function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }
    
    protected function createResponse($data)
    {
        return $this->response = new Response($this, $data);
    }
}