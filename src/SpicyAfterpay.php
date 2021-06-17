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
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;
use craft\commerce\services\Gateways;
use craft\events\RegisterComponentTypesEvent;

use spicyweb\spicyafterpay\services\SpicyAfterpayService as SpicyAfterpayServiceService;
use spicyweb\spicyafterpay\variables\SpicyAfterpayVariable;
//use spicyweb\spicyafterpay\twigextensions\SpicyAfterpayTwigExtension;
use spicyweb\spicyafterpay\models\Settings;
use spicyweb\spicyafterpay\gateways\Gateway;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
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
    public $schemaVersion = '0.1.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = false;

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

        /**
         * Logging in Craft involves using one of the following methods:
         *
         * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
         * Craft::info(): record a message that conveys some useful information.
         * Craft::warning(): record a warning message that indicates something unexpected has happened.
         * Craft::error(): record a fatal error that should be investigated as soon as possible.
         *
         * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
         *
         * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
         * the category to the method (prefixed with the fully qualified class name) where the constant appears.
         *
         * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
         * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
         *
         * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
         */
        Craft::info(
            Craft::t(
                'spicy-afterpay',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );

        Event::on(Gateways::class, Gateways::EVENT_REGISTER_GATEWAY_TYPES,  function(RegisterComponentTypesEvent $event) {
            $event->types[] = Gateway::class;
        });
    }
}
