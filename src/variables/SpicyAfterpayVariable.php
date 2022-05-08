<?php
/**
 * Spicy Afterpay plugin for Craft CMS 3.x
 *
 * Afterpay gateway for craft commerce
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
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.spicyAfterpay }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Spicy Web
 * @package   SpicyAfterpay
 * @since     0.1.0
 */
class SpicyAfterpayVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:
     *
     *     {{ craft.spicyAfterpay.exampleVariable }}
     *
     * Or, if your variable requires parameters from Twig:
     *
     *     {{ craft.spicyAfterpay.exampleVariable(twigValue) }}
     */

    /*
     * return the afterpay api status.
     * true = online
     * false = offline
     */
    public function afterpayStatus(): bool
    {
        return SpicyAfterpay::$plugin->spicyAfterpayService->checkAfterpayStatus();
    }

    /**
     * @throws ParsingException
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NetworkException
     */
    public function getNewPaymentToken(Order $order, $url = null)
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
