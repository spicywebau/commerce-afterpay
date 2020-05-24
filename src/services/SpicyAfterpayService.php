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

use spicyweb\spicyafterpay\SpicyAfterpay;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;

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
     * @return mixed
     */
    
    public function getAfterpayToken($cartID)
    {
        $cart = Order::find()->id($cartID)->one();
        
        
        
        return $cartID;
    }
}
