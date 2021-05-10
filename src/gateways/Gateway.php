<?php

namespace spicyweb\spicyafterpay\gateways;

use Craft;
use craft\commerce\Plugin as CommercePlugin;
use craft\commerce\base\Gateway as BaseGateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\Transaction;
use craft\helpers\StringHelper;
use craft\web\Response as WebResponse;
use craft\web\View;

use Afterpay\SDK\MerchantAccount as AfterpayMerchantAccount;

use spicyweb\spicyafterpay\SpicyAfterpay;
use spicyweb\spicyafterpay\models\AfterpayPaymentForm;

//use Throwable;
//use yii\base\Exception;
use yii\base\NotSupportedException;

class Gateway extends BaseGateway
{
    // Public Properties
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
     * @var bool
     */
    public $sandboxMode;
    
    /**
     * @var string
     */
    public $region;
    
    /*
     * Private Properties
     */
    private $_merchant;
    
    // Public Methods
    // =========================================================================
    
    public function init()
    {
        parent::init();
        
        $this->_merchant = $this->_setMerchant();
    }
    
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Afterpay';
    }
    
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('spicy-afterpay/settings', ['gateway' => $this]);
    }
    
    /**
     * @inheritDoc
     */
    public function getPaymentFormHtml(array $params)
    {
        $paymentFormModel = $this->getPaymentFormModel();
        $defaults = [
            'gateway' => $this,
            'currency' => CommercePlugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso(),
            'paymentForm' => $paymentFormModel
        ];
        
        $params = array_merge($defaults, $params);
        $view = Craft::$app->getView();
    
        $previousMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $html = Craft::$app->getView()->renderTemplate('spicy-afterpay/paymentForm', $params);
    
        //if ($checkAfterpay) {
        //    $html = Craft::$app->getView()->renderTemplate('commerce/_components/gateways/_creditCardFields', $params);
        //} else {
        //}
        $view->setTemplateMode($previousMode);
    
        return $html;
    }
    
    /**
     * @inheritDoc
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        // TODO: Implement authorize() method.
    }
    
    /**
     * @inheritDoc
     */
    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        // TODO: Implement capture() method.
    }
    
    /**
     * @inheritDoc
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement completeAuthorize() method.
    }
    
    /**
     * @inheritDoc
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement completePurchase() method.
    }
    
    /**
     * @inheritDoc
     */
    public function createPaymentSource(BasePaymentForm $sourceData, int $userId): PaymentSource
    {
        // TODO: Implement createPaymentSource() method.
    }
    
    /**
     * @inheritDoc
     */
    public function deletePaymentSource($token): bool
    {
        // TODO: Implement deletePaymentSource() method.
    }
    
    /**
     * @inheritDoc
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        // TODO: Implement getPaymentFormModel() method.
        return new AfterpayPaymentForm();
    }
    
    /**
     * @inheritDoc
     */
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        // TODO: Implement purchase() method.
    }
    
    /**
     * @inheritDoc
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement refund() method.
    }
    
    /**
     * @inheritDoc
     */
    public function processWebHook(): WebResponse
    {
        throw new NotSupportedException();
    }
    
    /**
     * @inheritDoc
     */
    public function supportsAuthorize(): bool
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsCapture(): bool
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsCompleteAuthorize(): bool
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsCompletePurchase(): bool
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsPaymentSources(): bool
    {
        return false;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsPurchase(): bool
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsRefund(): bool
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsPartialRefund(): bool
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsWebhooks(): bool
    {
        return false;
    }
    
    /*
     * PRIVATE METHODS
     */
    
    /*
     * sets the merchant for requests
     */
    private function _setMerchant()
    {
        try {
            $apiEnvironment = $this->sandboxMode ? 'sandbox' : 'production';
            $merchant = new AfterpayMerchantAccount();
            $merchant->setApiEnvironment($apiEnvironment);
            
            return $merchant;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Craft::warning("[AFTERPAY] error setting the merchant: {$error}");
            return null;
        }
    }
}