<?php
/**
 * Afterpay plugin for Craft CMS 4 / Craft Commerce 4
 *
 * @link      https://github.com/spicywebau
 * @copyright Copyright (c) 2020 Spicy Web
 */

namespace spicyweb\spicyafterpay\variables;

use Afterpay\SDK\Exception\InvalidArgumentException;
use Afterpay\SDK\Exception\NetworkException;
use Afterpay\SDK\Exception\ParsingException;
use Afterpay\SDK\HTTP\Request\CreateCheckout as AfterpayCreateCheckoutRequest;
use Craft;
use craft\commerce\elements\Order;
use spicyweb\spicyafterpay\SpicyAfterpay;
use yii\base\InvalidConfigException;
use yii\helpers\Json;

/**
 * Spicy Afterpay Variable
 *
 * @package spicyweb\spicyafterpay\variables
 * @author Spicy Web <plugins@spicyweb.com.au>
 * @since 1.0.0
 */
class SpicyAfterpayVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Checks the Afterpay API status to see if it's available.
     *
     * @return bool
     */
    public function afterpayStatus(): bool
    {
        return SpicyAfterpay::$plugin->spicyAfterpayService->checkAfterpayStatus();
    }

    /**
     * @param Order $order
     * @param string|null $url
     * @return mixed
     * @throws ParsingException
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NetworkException
     */
    public function getNewPaymentToken(Order $order, ?string $url = null): mixed
    {
        $gateway = $order->getGateway();

        if ($gateway) {
            $merchant = $gateway->getMerchant();

            $requestData = SpicyAfterpay::$plugin->spicyAfterpayService->buildCheckoutRequest($order, $url);

            $request = new AfterpayCreateCheckoutRequest($requestData);
            $request->setMerchantAccount($merchant);

            // check if the data is valid
            if ($request->isValid()) {
                // send the checkout request and get the token
                $request->send();
                $tokenData = $request->getResponse()->getParsedBody();

                return $tokenData->token ?? null;
            }

            // display the errors from Afterpay request
            if (Craft::$app->config->general->devMode) {
                echo '<pre>';
                echo $request->getValidationErrorsAsHtml();
                echo '</pre>';
            } else {
                $encodedErrors = Json::encode($request->getValidationErrors());
                Craft::warning($encodedErrors, 'afterpay');
            }
        }

        return null;
    }
}
