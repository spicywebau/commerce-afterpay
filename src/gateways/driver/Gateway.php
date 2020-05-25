<?php

namespace spicyweb\spicyafterpay\gateways\driver;

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
        return $this->createRequest('\Omnipay\PayPal\Message\ExpressAuthorizeRequest', $parameters);
    }
    
    public function completeAuthorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\PayPal\Message\ExpressCompleteAuthorizeRequest', $parameters);
    }
    
    public function purchase(array $parameters = array())
    {
        return $this->authorize($parameters);
    }
    
    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\PayPal\Message\ExpressCompletePurchaseRequest', $parameters);
    }
    
    public function capture(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\PayPal\Message\CaptureRequest', $parameters);
    }
    
    // public function refund(array $parameters = array())
    // {
    //     return $this->createRequest('', $parameters);
    // }
    
    // public function void(array $parameters = array())
    // {
    //     return $this->createRequest('\Omnipay\PayPal\Message\ExpressVoidRequest', $parameters);
    // }
    
}