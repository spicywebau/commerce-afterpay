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


use spicyweb\spicyafterpay\SpicyAfterpay;

use Craft;
use craft\web\Controller;
use yii\db\Exception;

/**
 * @author    Spicy Web
 * @package   SpicyAfterpay
 * @since     0.1.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================
    protected $allowAnonymous = ['get-token'];

    // Public Methods
    // =========================================================================

    public function actionGetToken()
    {
        return true;
    }
}
