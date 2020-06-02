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

use craft\commerce\models\LineItem;
use craft\helpers\UrlHelper;
use spicyweb\spicyafterpay\SpicyAfterpay;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;

/**
 * SpicyAfterpayService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Spicy Web
 * @package   SpicyAfterpay
 * @since     0.1.0
 */
class SpicyAfterpayService extends Component
{
    // Public Methods
    // =========================================================================
    
    /**
     * From any other plugin file, call it like this:
     *
     *     SpicyAfterpay::$plugin->spicyAfterpayService->getAfterpayToken()
     *
     * @param $cartID
     * @param $cancelUrl
     * @param $redirect
     * @param $gatewayId
     * @return mixed
     * @throws \craft\commerce\errors\TransactionException
     * @throws \yii\base\Exception
     * @throws \Throwable
     */
    
    public function getAfterpayToken($cartID, $cancelUrl, $redirect, $gatewayId)
    {
        $order = Order::find()->id($cartID)->one();
        
        if ($redirect && !$order->returnUrl) {
            $order->returnUrl = $redirect;
            Craft::$app->getElements()->saveElement($order, false);
        }
        
        $commerceTransactions = Commerce::getInstance()->getTransactions();
        $commerceGateways = Commerce::getInstance()->getGateways();
        
        $transaction = $order->getLastTransaction() ?? $commerceTransactions->createTransaction($order);
        $gateway = $commerceGateways->getGatewayById($gatewayId);
        
        $transaction->type = $gateway->paymentType;
        $transaction->status = 'redirect';
        
        $data = [
            'merchant' => [
                'redirectConfirmUrl' => UrlHelper::actionUrl('commerce/payments/complete-payment', [
                    'commerceTransactionId' => $transaction->id,
                    'commerceTransactionHash' => $transaction->hash,
                ]),
                'redirectCancelUrl' => UrlHelper::siteUrl($cancelUrl),
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
        
        if ($order->billingAddress) {
            $data['billing'] = [
                'name' => $this->_getFullName($order->billingAddress),
                'line1' => $order->billingAddress->address1,
                'line2' => $order->billingAddress->address2,
                'suburb' => $order->billingAddress->city,
                'state' => $order->billingAddress->stateValue,
                'postcode' => $order->billingAddress->zipCode,
                'countryCode' => $order->billingAddress->country->iso,
                'phoneNumber' => $order->billingAddress->phone,
            ];
        }
        
        if ($order->shippingAddress) {
            $data['shipping'] = [
                'name' => $this->_getFullName($order->shippingAddress),
                'line1' => $order->shippingAddress->address1,
                'line2' => $order->shippingAddress->address2,
                'suburb' => $order->shippingAddress->city,
                'state' => $order->shippingAddress->stateValue,
                'postcode' => $order->shippingAddress->zipCode,
                'countryCode' => $order->shippingAddress->country->iso,
                'phoneNumber' => $order->shippingAddress->phone,
            ];
        }
    
        $endpoint = $gateway->getEndpoint() . 'orders';
        
        $response = $gateway->getResponse($endpoint, $data);
        
        if ($response->getStatusCode() === 201) {
            if (empty($transaction->id) || $transaction->id === null) {
                $commerceTransactions->saveTransaction($transaction);
            }
            
            return $gateway->getResponse($endpoint, $data);
        }
        
        return false;
    }
    
    private function _getFullName($address)
    {
        if (empty($address->fullName)) {
            $fullName = $address->firstName . ' ' . $address->lastName;
        } else {
            $fullName = $address->fullName;
        }
        
        return $fullName;
    }
}
