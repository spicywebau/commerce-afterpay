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
use Afterpay\SDK\HTTP\Request\ImmediatePaymentCapture as AfterpayImmediatePaymentCaptureRequest;
use Afterpay\SDK\HTTP\Request\DeferredPaymentAuth as AfterpayDeferredPaymentAuthRequest;
use Afterpay\SDK\Helper\StringHelper as AfterpayStringHelper;
use Afterpay\SDK\Model\Payment as AfterpayPayment;
use Afterpay\SDK\HTTP\Request\DeferredPaymentCapture as AfterpayDeferredPaymentCaptureRequest;

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
        return Craft::$app->getView()->renderTemplate(
            'spicy-afterpay/settings',
            ['gateway' => $this]
        );
    }

    /**
     * @inheritDoc
     */
    public function getPaymentFormHtml(array $params)
    {
        $paymentFormModel = $this->getPaymentFormModel();
        $defaults = [
            'gateway' => $this,
            'currency' => CommercePlugin::getInstance()->getPaymentCurrencies(
            )->getPrimaryPaymentCurrencyIso(),
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
     * @throws \Exception
     */
    public function authorize(
        Transaction $transaction,
        BasePaymentForm $form
    ): RequestResponseInterface {
        return $this->checkout($transaction, 'authorize');
    }

    /**
     * @inheritDoc
     */
    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        $parentTransaction = $transaction->getParent();

        if (!$parentTransaction) {
            Craft::error('Cannot retrieve parent transaction', __METHOD__);
        }

        $response = Json::decode($parentTransaction->response, true);
        $authorizationId = $response['id'] ?? null;

        if (!$authorizationId) {
            Craft::error('An Authorization ID is required to capture', __METHOD__);
        }

        $capturePaymentRequest = new AfterpayDeferredPaymentCaptureRequest(
            [
                'requestId' => AfterpayStringHelper::generateUuid(),
                'amount' => [
                    $response['openToCaptureAmount']['amount'],
                    $response['openToCaptureAmount']['currency']
                ]
            ]
        );

        $capturePaymentRequest->setMerchantAccount($this->_merchant);

        if ($capturePaymentRequest->send()) {
            // $order = new AfterpayPayment($capturePaymentRequest->getResponse()->getParsedBody());
            $paymentEvent = $capturePaymentRequest->getResponse()->getPaymentEvent();

            return $this->getResponseModel($paymentEvent);
        }

        $error = $capturePaymentRequest->getResponse()->getParsedBody();

        return $this->getResponseModel(null);
    }

    /**
     * @inheritDoc
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
    }

    /**
     * @inheritDoc
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
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
    public function purchase(
        Transaction $transaction,
        BasePaymentForm $form
    ): RequestResponseInterface {
        return $this->checkout($transaction, 'purchase');
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
        return false;
    }

    /**
     * @inheritDoc
     */
    public function supportsCompletePurchase(): bool
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
    private function setMerchant(): ?AfterpayMerchantAccount
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

    private function checkout(Transaction $transaction, $requestType): RequestResponseInterface
    {
        $order = $transaction->order;
        try {
            // build checkout data with the transaction
            $requestData = $this->buildCheckoutRequest($transaction);

            // set the checkout data and merchant
            $request = new AfterpayCreateCheckoutRequest($requestData);
            $request->setMerchantAccount($this->_merchant);

            // check if the data is valid
            if ($request->isValid()) {
                // send the checkout request and get the token
                $request->send();
                $tokenData = $request->getResponse()->getParsedBody();

                // immediately capture payment or authorize with the token
                if ($requestType === 'authorize') {
                    $paymentRequest = new AfterpayDeferredPaymentAuthRequest(
                        ['token' => $tokenData['token']]
                    );
                } else {
                    $paymentRequest = new AfterpayImmediatePaymentCaptureRequest(
                        ['token' => $tokenData['token']]
                    );
                }

                // set the merchant account
                $paymentRequest->setMerchantAccount($this->_merchant);

                // send the request
                if ($paymentRequest->send()) {
                    // get the returned response
                    $paymentResponse = $paymentRequest->getResponse();
                    $data = $paymentResponse->getParsedBody();
                    $statusCode = $paymentResponse->getHttpStatusCode();
                    $data['statusCode'] = $statusCode;

                    // if not successful then throw
                    if (!$paymentResponse->isSuccessful()) {
                        $error = $data;
                        Craft::warning("[Afterpay] invalid {$error}");
                        throw new \Exception('Invalid data');
                    }

                    // else return the response model with the data
                    return $this->getResponseModel($data);
                }
            }

            // error when validating data
            $errors = $request->getValidationErrors();
            $errors = Json::encode($errors);
            Craft::warning("[Afterpay] invalid {$errors}");
            throw new \Exception('Invalid data');
        } catch (\Exception $e) {
            $order->addError('afterpay', 'Error found when trying to submit purchase request');

            return $this->getResponseModel(null);
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
