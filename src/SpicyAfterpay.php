<?php
/**
 * Afterpay plugin for Craft CMS 4 / Craft Commerce 4
 *
 * @link      https://github.com/spicywebau
 * @copyright Copyright (c) 2021 Spicy Web
 */

namespace spicyweb\spicyafterpay;

use Craft;
use craft\base\Plugin;
use craft\commerce\services\Gateways;
use craft\events\RegisterComponentTypesEvent;
use craft\web\twig\variables\CraftVariable;
use spicyweb\spicyafterpay\gateways\Gateway;
use spicyweb\spicyafterpay\models\Settings;
use spicyweb\spicyafterpay\services\SpicyAfterpayService as SpicyAfterpayServiceService;
use spicyweb\spicyafterpay\variables\SpicyAfterpayVariable;
use yii\base\Event;

/**
 * Class SpicyAfterpay
 *
 * @package spicyweb\spicyafterpay
 * @author Spicy Web <plugins@spicyweb.com.au>
 * @since 1.0.0
 *
 * @property  SpicyAfterpayServiceService $spicyAfterpayService
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class SpicyAfterpay extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var SpicyAfterpay The instance of this plugin
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '0.1.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        //Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('spicyAfterpay', SpicyAfterpayVariable::class);
            }
        );

        Craft::info(
            Craft::t(
                'spicy-afterpay',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );

        Event::on(
            Gateways::class,
            Gateways::EVENT_REGISTER_GATEWAY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = Gateway::class;
            }
        );
    }
}
