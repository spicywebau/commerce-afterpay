<?php

namespace spicyweb\spicyafterpay\gateways;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\errors\CurrencyException;
use craft\commerce\models\Address;
use craft\commerce\models\LineItem;
use craft\commerce\models\ShippingMethod;
use craft\commerce\Plugin as CommercePlugin;
use craft\commerce\base\Gateway as BaseGateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\Transaction;

//use craft\helpers\App;
//use craft\helpers\StringHelper;
use craft\helpers\Json;
use craft\web\Response as WebResponse;
use craft\web\View;

use Afterpay\SDK\MerchantAccount as AfterpayMerchantAccount;
use Afterpay\SDK\HTTP\Request\CreateCheckout as AfterpayCreateCheckoutRequest;

use spicyweb\spicyafterpay\SpicyAfterpay;
use spicyweb\spicyafterpay\gateways\responses\CheckoutResponse as SAPCheckoutResponse;
use spicyweb\spicyafterpay\gateways\responses\RefundResponse as SAPRefundResponse;
use spicyweb\spicyafterpay\models\AfterpayPaymentForm;

//use Throwable;
//use yii\base\Exception;
use yii\base\InvalidConfigException;
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
        
        $this->_merchant = $this->setMerchant();
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
        return new AfterpayPaymentForm();
    }
    
    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        // TODO: CURRENTLY DOING
        $order = $transaction->order;
        try {
            $requestData = $this->buildCheckoutRequest($transaction);
            $request = new AfterpayCreateCheckoutRequest($requestData);
            $request->setMerchantAccount($this->_merchant);
            
            if ($request->isValid()) {
                $request->send();
                $data = $request->getResponse()->getParsedBody();
                
                return $this->getResponseModel($data);
            } else {
                $errors = $request->getValidationErrors();
                $errors = Json::encode($errors);
                Craft::warning("[Afterpay] invalid {$errors}");
                throw new \Exception('Invalid data');
            }
        } catch (\Exception $e) {
            $order->addError('afterpay', 'Error found when trying to submit purchase request');
            throw new \Exception('Invalid data');
        }
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
    
    public function getResponseModel($data): RequestResponseInterface
    {
        return new SAPCheckoutResponse($data);
    }
    
    /*
     * PRIVATE METHODS
     */
    
    /*
     * sets the merchant for requests
     */
    private function setMerchant()
    {
        try {
            $apiEnvironment = $this->sandboxMode ? 'sandbox' : 'production';
            $merchant = new AfterpayMerchantAccount();
            $merchant->setApiEnvironment($apiEnvironment);
            $merchant->setMerchantId(Craft::parseEnv($this->merchantId));
            $merchant->setSecretKey(Craft::parseEnv($this->merchantKey));
            $merchant->setCountryCode($this->region);
            
            return $merchant;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Craft::warning("[AFTERPAY] error setting the merchant: {$error}");
            return null;
        }
    }
    
    private function buildCheckoutRequest(Transaction $transaction): array
    {
        $order = $transaction->order;
        $shipping = $order->shippingAddress;
        $billing = $order->billingAddress;
        $shippingMethod = $order->getShippingMethod();
        $lineItems = $order->getLineItems();
        
        $checkoutData = [];
        
        $checkoutData['amount'] = $this->buildCheckoutAmount($order);
        $checkoutData['consumer'] = $this->buildCheckoutConsumer($order);
        $checkoutData['billing'] = $this->buildCheckoutAddress($billing);
        $checkoutData['shipping'] = $this->buildCheckoutAddress($shipping);
        
        //if ($shippingMethod) {
        //    $checkoutData['courier'] = $this->buildCheckoutCourier($shippingMethod);
        //}
        
        $checkoutData['merchantReference'] = $order->id;
        $checkoutData['items'] = $this->buildCheckoutItems($order, $lineItems);
        
        return $checkoutData;
    }
    
    private function buildCheckoutAmount(Order $order): array
    {
        return [
            $order->getTotal(),
            $order->getPaymentCurrency()
        ];
    }
    
    private function buildCheckoutConsumer(Order $order): array
    {
        $shipping = $order->getShippingAddress();
        $billing = $order->getBillingAddress();
        return [
            'phoneNumber' => $shipping->phone ?? $billing->phone,
            'givenNames' => $shipping->firstName ?? $billing->firstName,
            'surname' => $shipping->lastName ?? $billing->lastName,
            'email' => $order->email,
        ];
    }
    
    private function buildCheckoutAddress(Address $address): array
    {
        return [
            'name' => $this->buildFullName($address),
            'line1' => $address->address1 ?? '',
            'line2' => $address->address2 ?? '',
            'area1' => $address->city ?? '',
            'region' => $address->getStateText() ?? '',
            'postcode' => $address->zipCode ?? '',
            'countryCode' => $address->countryIso,
            'phoneNumber' => $address->phone ?? ''
        ];
    }
    
    //private function buildShippingMethod(ShippingMethod $shippingMethod): array
    //{
    //    return [
    //        'name' =>
    //    ];
    //}
    
    /**
     * @param Order $order
     * @param LineItem[] $items
     * @return array
     * @throws CurrencyException
     * @throws InvalidConfigException
     */
    private function buildCheckoutItems(Order $order, array $items): array
    {
        $checkoutItems = [];
        
        foreach ($items as $item) {
            $checkoutItems[] += [
                'name' => $item->getDescription(),
                'sku' => $item->getSku(),
                'quantity' => $item->qty,
                'price' => [
                    $item->getTotal(),
                    $order->getPaymentCurrency()
                ]
            ];
        }
        
        return $checkoutItems;
    }
    
    private function buildFullName(Address $address): string
    {
        $name = '';
        
        if ($address->firstName) {
            $name .= $address->firstName;
        }
        
        if ($address->lastName) {
            $name .= ' ' . $address->firstName;
        }
        
        return $name;
    }
}