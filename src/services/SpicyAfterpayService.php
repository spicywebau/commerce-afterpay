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
                    
                    Craft::warning("[AFTERPAY]: Received unexpected HTTP {$responseCode} {$contentType} response from Afterpay with errorCode: {$errorCode}; errorId: {$errorId}; message: {$message}\n");
                } else {
                    $cfRayId = $pingResponse->getParsedHeaders()['cf-ray'];
                    
                    Craft::warning("[AFTERPAY]: Received unexpected HTTP {$responseCode} {$contentType} response from Afterpay with CF-Ray ID: {$cfRayId}\n");
                }
                
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $code = $e->getCode();
            $error = $e->getMessage();
            
            Craft::warning("[AFTERPAY] {$code} ");
            Craft::warning("[AFTERPAY] {$error} ");
            return false;
        }
    }
}
