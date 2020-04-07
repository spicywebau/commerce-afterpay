<?php
/**
 * Spicy Afterpay plugin for Craft CMS 3.x
 *
 * Afterpay gateway for craft commerce
 *
 * @link      https://github.com/spicywebau
 * @copyright Copyright (c) 2020 Spicy Web
 */

namespace spicyweb\spicyafterpay\models;

use spicyweb\spicyafterpay\SpicyAfterpay;

use Craft;
use craft\base\Model;

/**
 * SpicyAfterpayModel Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Spicy Web
 * @package   SpicyAfterpay
 * @since     0.1.0
 */
class SpicyAfterpayModel extends Model
{
    // Public Properties
    // =========================================================================
    
    /**
     * Some model attribute
     *
     * @var string
     */
    public $someAttribute = 'Some Default';
    
    // Public Methods
    // =========================================================================
    
    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['someAttribute', 'string'],
            ['someAttribute', 'default', 'value' => 'Some Default'],
        ];
    }
}
