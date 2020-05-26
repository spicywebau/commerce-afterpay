<?php

namespace spicyweb\spicyafterpay\gateways;

use craft\commerce\models\PaymentSource;
use craft\commerce\omnipay\base\RequestResponse;
use craft\web\Response as WebResponse;
use spicyweb\spicyafterpay\gateways\driver\Gateway;

use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\elements\Order;
use craft\commerce\errors\PaymentException;
use craft\commerce\models\LineItem;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use craft\commerce\base\Gateway as BaseGateway;
use craft\commerce\models\OrderAdjustment;
use craft\helpers\UrlHelper;
use craft\web\View;

use Omnipay\Common\AbstractGateway;
use Omnipay\Omnipay;

use GuzzleHttp\Client;

use spicyweb\spicyafterpay\SpicyAfterpay;
use spicyweb\spicyafterpay\models\PaymentForm;
use spicyweb\spicyafterpay\SpicyAfterpayAssetBundle;
use spicyweb\spicyafterpay\gateways\responses\PurchaseResponse;
use spicyweb\spicyafterpay\gateways\responses\CompletePurchaseResponse;

use Throwable;
use yii\base\Exception;

class Afterpay extends BaseGateway
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
    
    /**
     * @var string
     */
    public $region;
    
    /**
     * @var string
     */
    public $buttonText;
    
    // Public Methods
    // =========================================================================
    
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce', 'Afterpay');
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
        $url = $this->sandboxMode ? 'https://portal.sandbox.afterpay.com' : 'https://portal.afterpay.com';
        $url .= '/afterpay.js';
        
        $defaults = [
            'gateway' => $this
        ];
        
        $params = array_merge($defaults, $params);
        
        $view = Craft::$app->getView();
        
        $previousMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        
        $view->registerJsFile($url);
        $view->registerAssetBundle(SpicyAfterpayAssetBundle::class);
        
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
    
    /**
     * @inheritDoc
     */
    public function supportsAuthorize(): bool
    {
        return false;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsCapture(): bool
    {
        return false;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsCompleteAuthorize(): bool
    {
        return false;
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
        return false;
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
    public function supportsRefund(): bool
    {
        return false;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsPartialRefund(): bool
    {
        return false;
    }
    
    /**
     * @inheritDoc
     */
    public function supportsWebhooks(): bool
    {
        return false;
    }
    
    public function getResponse($endpoint, $data) {
        $headers = [
            'Authorization' => $this->_buildAuthorizationHeader(),
            'Content-type' => 'application/json',
            'Accept' => 'application/json'
        ];
        
        $client = new Client();
        
        return $client->request(
            'POST',
            $endpoint,
            [
                'headers' => $headers,
                'json' => $data,
            ]
        );
    }
    
    public function getEndpoint() {
        $isAUOrNZ = $this->region === 'AU' || $this->region === 'NZ';
        
        if ($isAUOrNZ) {
            $url = $this->sandboxMode ? 'https://api-sandbox.afterpay.com' : 'https://api.afterpay.com';
        } else {
            $url = $this->sandboxMode ? 'https://api.us-sandbox.afterpay.com' : 'https://api.us.afterpay.com';
        }
        
        return $url . '/v1/';
    }
    
    /**
     * @inheritDoc
     */
    public function processWebHook(): WebResponse
    {
    }
    
    /**
     * @inheritDoc
     */
    public function createPaymentSource(BasePaymentForm $sourceData, int $userId): PaymentSource
    {
    }
    
    /**
     * @inheritDoc
     */
    public function deletePaymentSource($token): bool
    {
        return false;
    }
    
    /**
     * @inheritDoc
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new PaymentForm();
    }
    
    /**
     * @inheritDoc
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {
    }
    
    /**
     * @inheritDoc
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
    }
    
    /**
     * @inheritDoc
     */
    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
    }
    
    /**
     * @inheritDoc
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
    }
    
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        return $this->_prepareTransactionOrderToken($transaction);
    }
    
    /**
     * @inheritdoc
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        $data = [
            'token' => Craft::$app->getRequest()->getQueryParam('orderToken'),
            'merchantReference' => Craft::$app->getRequest()->getQueryParam('commerceTransactionHash'),
        ];
        
        $endpoint = $this->getEndpoint() . 'payments/capture';
        
        return new CompletePurchaseResponse($this->getResponse($endpoint, $data));
    }
    
    // Private Methods
    // =========================================================================
    
    private function _prepareTransactionOrderToken(Transaction $transaction) {
    
        /** @var Order $order */
        $order = $transaction->getOrder();
    
        $data = [
            'merchant' => [
                'redirectConfirmUrl' => UrlHelper::actionUrl('commerce/payments/complete-payment', [
                    'commerceTransactionId' => $transaction->id,
                    'commerceTransactionHash' => $transaction->hash,
                ]),
                'redirectCancelUrl' => UrlHelper::siteUrl($order->cancelUrl),
            ],
            'merchantReference' => $transaction->hash,
            'totalAmount' => [
                'amount' => (float)$order->totalPrice,
                'currency' => $order->currency,
            ],
            'consumer' => [
                'phoneNumber' => $order->billingAddress->phone,
                'givenNames' => $order->billingAddress->firstName,
                'surname' => $order->billingAddress->lastName,
                'email' => $order->email,
            ],
            'taxAmount' => [
                'amount' => $order->getTotalTax(),
                'currency' => $order->currency,
            ],
            'shippingAmount' => [
                'amount' => $order->getTotalShippingCost(),
                'currency' => $order->currency,
            ],
            'items' => array_map(function (LineItem $lineItem) use ($order) {
                return [
                    'quantity' => (int)$lineItem->qty,
                    'name' => $lineItem->description,
                    'sku' => $lineItem->sku,
                    'price' => [
                        'amount' => (float)$lineItem->salePrice,
                        'currency' => $order->currency,
                    ],
                ];
            }, $order->lineItems),
        ];
    
        if($order->billingAddress) {
            $data['billing'] = [
                'name' => $order->billingAddress->fullName,
                'line1' => $order->billingAddress->address1,
                'line2' => $order->billingAddress->address2,
                'suburb' => $order->billingAddress->city,
                'state' => $order->billingAddress->stateValue,
                'postcode' => $order->billingAddress->zipCode,
                'countryCode' => $order->billingAddress->country->iso,
                'phoneNumber' => $order->billingAddress->phone,
            ];
        }
    
        if($order->shippingAddress) {
            $data['shipping'] = [
                'name' => $order->shippingAddress->fullName,
                'line1' => $order->shippingAddress->address1,
                'line2' => $order->shippingAddress->address2,
                'suburb' => $order->shippingAddress->city,
                'state' => $order->shippingAddress->stateValue,
                'postcode' => $order->shippingAddress->zipCode,
                'countryCode' => $order->shippingAddress->country->iso,
                'phoneNumber' => $order->shippingAddress->phone,
            ];
        }
    
        $endpoint = $this->getEndpoint() . 'orders';
        
        return new PurchaseResponse($this->getResponse($endpoint, $data));
    }
    
    private function _buildAuthorizationHeader()
    {
        $merchantId = $this->merchantId;
        $merchantSecret = $this->merchantKey;
        
        return 'Basic ' . base64_encode($merchantId . ':' . $merchantSecret);
    }
}