<?php

namespace spicyweb\spicyafterpay\gateways;

use Craft;
use craft\commerce\errors\PaymentException;
use craft\commerce\Plugin as CommercePlugin;
use craft\commerce\base\Gateway as BaseGateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\Transaction;

use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\Response as WebResponse;
use craft\web\View;

use Afterpay\SDK\MerchantAccount as AfterpayMerchantAccount;
use Afterpay\SDK\HTTP\Request\ImmediatePaymentCapture as AfterpayImmediatePaymentCaptureRequest;
use Afterpay\SDK\HTTP\Request\DeferredPaymentAuth as AfterpayDeferredPaymentAuthRequest;
use Afterpay\SDK\Helper\StringHelper as AfterpayStringHelper;

use Afterpay\SDK\HTTP\Request\DeferredPaymentCapture as AfterpayDeferredPaymentCaptureRequest;
use Afterpay\SDK\HTTP\Request\CreateRefund as AfterpayCreateRefundRequest;

// use spicyweb\spicyafterpay\SpicyAfterpay;
use spicyweb\spicyafterpay\SpicyAfterpayAssetBundle;
use spicyweb\spicyafterpay\gateways\responses\CheckoutResponse as SAPCheckoutResponse;
use spicyweb\spicyafterpay\gateways\responses\RefundResponse as SAPRefundResponse;

use craft\commerce\models\payments\OffsitePaymentForm;

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

    public $regionDollar;

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

        switch ($this->region) {
            case 'AU':
                $this->regionDollar = 'AUD';
                break;
            case 'NZ':
                $this->regionDollar = 'NZD';
                break;
            case 'US':
                $this->regionDollar = 'USD';
                break;
        }
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
        $url = $this->sandboxMode ? 'https://portal.sandbox.afterpay.com/afterpay.js' : 'https://portal.afterpay.com/afterpay.js';
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

        $view->registerJsFile($url);
        $view->registerAssetBundle(SpicyAfterpayAssetBundle::class);

        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $html = Craft::$app->getView()->renderTemplate('spicy-afterpay/paymentForm', $params);

        $view->setTemplateMode($previousMode);

        return Template::raw($html);
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

        try {
            $capturePaymentRequest->setMerchantAccount($this->_merchant);

            if ($capturePaymentRequest->send()) {
                $paymentEvent = Json::decode($capturePaymentRequest->getResponse()->getRawBody());

                return $this->getResponseModel($paymentEvent);
            }

            $error = Json::decode($capturePaymentRequest->getResponse()->getRawBody());

            throw new \Exception($error);
        } catch (\Exception $e) {
            return $this->getResponseModel(
                [
                    'message' => $e
                ]
            );
        }
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
        return new OffsitePaymentForm();
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
        $parentTransaction = $transaction->getParent();
        if (!$parentTransaction) {
            Craft::error('Cannot retrieve parent transaction', __METHOD__);
        }

        $refundRequest = new AfterpayCreateRefundRequest(
            [
                'amount' => [
                    'amount' => $transaction->paymentAmount,
                    'currency' => $this->regionDollar
                ]
            ]
        );

        try {
            $refundRequest->setMerchantAccount($this->_merchant);
            $refundRequest->setOrderId($parentTransaction->reference);

            if ($refundRequest->send()) {
                $refund = Json::decode($refundRequest->getResponse()->getRawBody());
                $refund['statusCode'] = $refundRequest->getResponse()->getHttpStatusCode();
                return $this->getRefundResponseModel($refund);
            }

            $error = ['Can\'t create a refund for a declined order.'];
            throw new \Exception($error);
        } catch (\Exception $e) {
            return $this->getRefundResponseModel(
                [
                    'message' => $e
                ]
            );
        }
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

    public function getRefundResponseModel($data): RequestResponseInterface
    {
        return new SAPRefundResponse($data);
    }

    public function getMerchant()
    {
        return $this->_merchant;
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
            $token = Craft::$app->getRequest()->getBodyParam('orderToken');
            $status = Craft::$app->getRequest()->getBodyParam('status');

            if ($status !== 'SUCCESS') {
                throw new PaymentException('Missing payer ID');
            }

            // immediately capture payment or authorize with the token
            if ($requestType === 'authorize') {
                $paymentRequest = new AfterpayDeferredPaymentAuthRequest(
                    ['token' => $token]
                );
            } else {
                $paymentRequest = new AfterpayImmediatePaymentCaptureRequest(
                    ['token' => $token]
                );
            }

            // set the merchant account
            $paymentRequest->setMerchantAccount($this->_merchant);

            // send the request
            if ($paymentRequest->send()) {
                // get the returned response
                $paymentResponse = $paymentRequest->getResponse();

                if ($paymentRequest->getResponse()->isApproved()) {
                    $data = Json::decode($paymentResponse->getRawBody());
                    $statusCode = $paymentResponse->getHttpStatusCode();
                    $data['statusCode'] = $statusCode;

                    // if not successful then throw
                    if (!$paymentResponse->isSuccessful()) {
                        $error = $data;
                        Craft::warning("[Afterpay] invalid {$error}");
                        throw new PaymentException($error);
                    }

                    // else return the response model with the data
                    return $this->getResponseModel($data);
                }

                return $this->getResponseModel(
                    [
                        'message' => 'Payment Unsuccessful. Please try again (and make sure all the details is correct).'
                    ]
                );
            }

            throw new PaymentException('Error sending request');
        } catch (\Exception $e) {
            $order->addError('afterpay', 'Error found when trying to submit purchase request');

            return $this->getResponseModel(
                [
                    'message' => $e
                ]
            );
        }
    }
}
