<?php

namespace spicyweb\spicyafterpay\gateways;

use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\errors\PaymentException;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use craft\commerce\omnipay\base\OffsiteGateway;
use craft\web\View;
use Omnipay\Common\AbstractGateway;
use Omnipay\Omnipay;

use spicyweb\spicyafterpay\SpicyAfterpay;
use spicyweb\spicyafterpay\SpicyAfterpayAssetBundle;
use yii\base\Exception;

class Afterpay extends OffsiteGateway
{
    // Properties
    // =========================================================================
    
    /**
     * @var string
     */
    public $merchantId;
    
    /**
     * @var string
     */
    public $merchantKey;
    
    /**
     * @var string
     */
    public $sandboxMode;
    
    // Public Methods
    // =========================================================================
    
    /**
     * @inheritdoc
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
        $request = $this->_prepareOffsiteTransactionConfirmationRequest($transaction);
        $completeRequest = $this->prepareCompleteAuthorizeRequest($request);
        
        return $this->performRequest($completeRequest, $transaction);
    }
    
    /**
     * @inheritdoc
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        $request = $this->_prepareOffsiteTransactionConfirmationRequest($transaction);
        $completeRequest = $this->prepareCompletePurchaseRequest($request);
        
        return $this->performRequest($completeRequest, $transaction);
    }
    
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce', 'Zip Pay');
    }
    
    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('spicy-afterpay/settings', ['gateway' => $this]);
    }
    
    /**
     * @inheritdoc
     */
    public function getPaymentFormHtml(array $params)
    {
        $url = '';
        $defaults = [
            'gateway' => $this
        ];
        
        $params = array_merge($defaults, $params);
        
        $view = Craft::$app->getView();
        
        $previousMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        
        $view->registerJsFile($url);
        $view->registerAssetBundle(SpicyZipAssetBundle::class);
        
        $html = Craft::$app->getView()->renderTemplate('spicy-afterpay/paymentForm', $params);
        $view->setTemplateMode($previousMode);
        
        return $html;
    }
    
    /**
     * @inheritdoc
     */
    public function populateRequest(array &$request, BasePaymentForm $paymentForm = null)
    {
        unset($request['card']);
    }
    
    // Protected Methods
    // =========================================================================
    
    /**
     * @inheritdoc
     */
    protected function createGateway(): AbstractGateway
    {
        /** @var Gateway $gateway */
        $gateway = static::createOmnipayGateway($this->getGatewayClassName());
        
        $gateway->setMerchantId(Craft::parseEnv($this->merchantId));
        $gateway->setMerchantKey(Craft::parseEnv($this->merchantKey));
        $gateway->setTestMode($this->sandboxMode);
        
        return $gateway;
    }
    
    /**
     * @inheritdoc
     */
    protected function getGatewayClassName()
    {
        return '\\'.Gateway::class;
    }
    
    /**
     * @inheritdoc
     * will probably need to create our own item bag class
     */
    // protected function getItemBagClassName(): string
    // {
    //     return ::class;
    // }
    
    // Private Methods
    // =========================================================================
    
    /**
     * Prepare the confirmation request for completeAuthorize and completePurchase requests.
     *
     * @param Transaction $transaction
     * @return array
     * @throws PaymentException if missing parameters
     * @throws Exception
     */
    private function _prepareOffsiteTransactionConfirmationRequest(Transaction $transaction): array
    {
        $request = $this->createRequest($transaction);
        
        $token = Craft::$app->getRequest()->getParam('token');
        $payerId = Craft::$app->getRequest()->getParam('PayerID');
        
        if (!$token) {
            throw new PaymentException('Missing token');
        }
        
        $request['token'] = $token;
        
        if (!$payerId) {
            throw new PaymentException('Missing payer ID');
        }
        $request['PayerID'] = $payerId;
        
        return $request;
    }
}