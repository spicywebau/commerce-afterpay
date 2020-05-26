<?php

namespace spicyweb\spicyafterpay\gateways\driver;

use spicyweb\spicyafterpay\gateways\driver\message\AuthorizeRequest;
use spicyweb\spicyafterpay\gateways\driver\message\CompleteAuthorizeRequest;
use spicyweb\spicyafterpay\gateways\driver\message\CompletePurchaseRequest;
// use spicyweb\spicyafterpay\gateways\driver\message\CaptureRequest;

use Omnipay\Common\AbstractGateway;

class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'After Pay';
    }
    
    public function getDefaultParameters()
    {
        return array(
            'merchantId' => '',
            'merchantKey' => '',
            'sandboxMode' => false,
            'region' => 'AU'
        );
    }
    
    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }
    
    public function setMerchantId($value): Gateway
    {
        return $this->setParameter('merchantId', $value);
    }
    
    public function getMerchantKey()
    {
        return $this->getParameter('merchantKey');
    }
    
    public function setMerchantKey($value): Gateway
    {
        return $this->setParameter('merchantKey', $value);
    }
    
    public function getRegion() {
        return $this->getParameter('region');
    }
    
    public function setRegion($value)
    {
        return $this->setParameter('region', $value);
    }
    
    public function authorize(array $parameters = array())
    {
    }
    
    public function completeAuthorize(array $parameters = array())
    {
    }
    
    public function purchase(array $parameters = array())
    {
    }
    
    public function completePurchase(array $parameters = array())
    {
    }
}