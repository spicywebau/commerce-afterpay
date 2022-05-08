<?php
/**
 * Spicy Afterpay plugin for Craft CMS 3.x
 *
 * Afterpay gateway for craft commerce
 *
 * @link      https://github.com/spicywebau
 * @copyright Copyright (c) 2020 Spicy Web
 */

namespace spicyweb\spicyafterpay\controllers;

use craft\web\Controller;

use spicyweb\spicyafterpay\SpicyAfterpay;

/**
 * @author    Spicy Web
 * @package   SpicyAfterpay
 * @since     0.1.0
 */
class DefaultController extends Controller
{
    // Protected Properties
    // =========================================================================
    protected array|int|bool $allowAnonymous = ['get-token'];

    // Public Methods
    // =========================================================================

    public function actionGetToken()
    {
        return true;
    }
}
