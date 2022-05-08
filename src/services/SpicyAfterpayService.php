<?php
/**
 * Spicy Afterpay plugin for Craft CMS 3.x
 *
 * Afterpay gateway for craft commerce
 *
 * @link      https://github.com/spicywebau
 * @copyright Copyright (c) 2020 Spicy Web
 */

namespace spicyweb\spicyafterpay\services;

use Afterpay\SDK\HTTP\Request\Ping as AfterpayPingRequest;
use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\models\Address;

use craft\commerce\models\LineItem;

use yii\base\InvalidConfigException;

/**
 * @author    Spicy Web
 * @package   SpicyAfterpay
 * @since     0.1.0
 */
class SpicyAfterpayService extends Component
{
    public $regionDollar;

    // Public Methods
    // =========================================================================

    /*
     * check if the Afterpay API status and see if it's available.
     */
    public function checkAfterpayStatus(): bool
    {
        try {
            $pingRequest = new AfterpayPingRequest();

            if (!$pingRequest->send()) {
                $pingResponse = $pingRequest->getResponse();
                $responseCode = $pingResponse->getHttpStatusCode();
                $contentType = $pingResponse->getContentTypeSimplified();

                if (is_object($body = $pingResponse->getParsedBody())) {
                    $errorCode = $body->errorCode;
                    $errorId = $body->errorId;
                    $message = $body->message;

                    Craft::warning(
                        "[AFTERPAY]: Received unexpected HTTP {$responseCode} {$contentType} response from Afterpay with errorCode: {$errorCode}; errorId: {$errorId}; message: {$message}\n"
                    );
                } else {
                    $cfRayId = $pingResponse->getParsedHeaders()['cf-ray'];

                    Craft::warning(
                        "[AFTERPAY]: Received unexpected HTTP {$responseCode} {$contentType} response from Afterpay with CF-Ray ID: {$cfRayId}\n"
                    );
                }

                return false;
            }

            return true;
        } catch (\Exception $e) {
            $code = $e->getCode();
            $error = $e->getMessage();

            Craft::warning("[AFTERPAY] {$code} ", 'afterpay');
            Craft::warning("[AFTERPAY] {$error} ", 'afterpay');
            return false;
        }
    }

    /**
     * @throws InvalidConfigException
     */
    public function buildCheckoutRequest(Order $order, $redirectUrl = null): array
    {
        $this->regionDollar = $order->getGateway()->regionDollar;

        $shipping = $order->shippingAddress;
        $billing = $order->billingAddress;
        $shippingCost = $order->getTotalShippingCost();
        $lineItems = $order->getLineItems();
        $discounts = $order->getTotalDiscount();

        $checkoutData = [];

        $checkoutData['amount'] = $this->buildCheckoutAmount($order);
        $checkoutData['consumer'] = $this->buildCheckoutConsumer($order);
        $checkoutData['billing'] = $this->buildCheckoutAddress($billing);
        $checkoutData['shipping'] = $this->buildCheckoutAddress($shipping);

        if ($shippingCost > 0) {
            $checkoutData['shippingAmount'] = [
                'amount' => $shippingCost,
                'currency' => $this->regionDollar,
            ];
        }

        if ($discounts > 0) {
            $checkoutData['shippingAmount'] = [
                'displayName' => 'Total Discount Amount',
                'amount' => [
                    'amount' => $shippingCost,
                    'currency' => $this->regionDollar,
                ],
            ];
        }

        if ($redirectUrl) {
            $checkoutData['merchant']['redirectConfirmUrl'] = $redirectUrl;
            $checkoutData['merchant']['redirectCancelUrl'] = $redirectUrl;
        }

        $checkoutData['merchantReference'] = $order->shortNumber;
        $checkoutData['items'] = $this->buildCheckoutItems($order, $lineItems);

        return $checkoutData;
    }

    private function buildCheckoutAmount(Order $order): array
    {
        return [
            'amount' => $order->getTotal(),
            'currency' => $this->regionDollar,
        ];
    }

    private function buildCheckoutConsumer(Order $order): array
    {
        $shipping = $order->getShippingAddress();
        $billing = $order->getBillingAddress();
        return [
            'phoneNumber' => $shipping->phone ?? $billing->phone ?? '',
            'givenNames' => $shipping->firstName ?? $billing->firstName ?? '',
            'surname' => $shipping->lastName ?? $billing->lastName ?? '',
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
            'phoneNumber' => $address->phone ?? '',
        ];
    }

    /**
     * @param Order $order
     * @param LineItem[] $items
     * @return array
     */
    private function buildCheckoutItems(Order $order, array $items): array
    {
        $checkoutItems = [];

        foreach ($items as $item) {
            $checkoutItems[] = [
                'name' => $item->getDescription(),
                'sku' => $item->getSku(),
                'quantity' => $item->qty,
                'price' => [
                    $item->getTotal(),
                    $this->regionDollar,
                ],
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
