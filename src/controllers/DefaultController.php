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
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Spicy Web
 * @package   SpicyAfterpay
 * @since     0.1.0
 */
class DefaultController extends Controller
{
    
    // Protected Properties
    // =========================================================================
    
    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['get-token'];
    
    // Public Methods
    // =========================================================================
    
    // actions/spicy-afterpay/default/get-token
    public function actionGetToken()
    {
        //$returnData = [
        //    'cartID' => null,
        //    'success' => false
        //];
        //if (isset($_POST['cartId'])) {
        //    $cartID = $_POST['cartId'];
        //    $cancelUrl = Craft::$app->request->getValidatedBodyParam('cancelUrl');
        //    $redirectUrl = Craft::$app->request->getValidatedBodyParam('redirect');
        //    $gatewayId = Craft::$app->request->getBodyParam('gatewayId');
        //
        //    $response = SpicyAfterpay::$plugin->spicyAfterpayService->getAfterpayToken($cartID, $cancelUrl, $redirectUrl, $gatewayId);
        //
        //    if ($response && $response->getStatusCode() === 201) {
        //        $data = json_decode($response->getBody(), true);
        //        $token = isset($data['token']) && !empty($data['token']) ? $data['token'] : null;
        //
        //        if ($token) {
        //            $returnData = [
        //                'cartID' => $cartID,
        //                'token' => $token,
        //                'success' => true
        //            ];
        //        }
        //    }
        //}
        //
        //return $this->asJson($returnData);
        return 'hello';
    }
}
