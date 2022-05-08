<?php
/**
 * Afterpay plugin for Craft CMS ^3.4.0
 *
 * Afterpay gateway for Craft Commerce
 *
 * @link      https://github.com/spicywebau
 * @copyright Copyright (c) 2021 Spicy Web
 */

namespace spicyweb\spicyafterpay;

use Craft;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use craft\commerce\services\Gateways;
use craft\events\RegisterComponentTypesEvent;

use spicyweb\spicyafterpay\services\SpicyAfterpayService as SpicyAfterpayServiceService;
use spicyweb\spicyafterpay\variables\SpicyAfterpayVariable;

//use spicyweb\spicyafterpay\twigextensions\SpicyAfterpayTwigExtension;
use spicyweb\spicyafterpay\models\Settings;
use spicyweb\spicyafterpay\gateways\Gateway;

use yii\base\Event;

/**
 * @author    Spicy Web
 * @package   SpicyAfterpay
 * @since     0.1.0
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
     * Static property that is an instance of this plugin class so that it can be accessed via
     * SpicyAfterpay::$plugin
     *
     * @var SpicyAfterpay
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public string $schemaVersion = '0.1.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * SpicyAfterpay::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        //Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
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
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = Gateway::class;
            }
        );
    }
}
