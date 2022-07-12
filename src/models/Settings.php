<?php
/**
 * Afterpay plugin for Craft CMS 4 / Craft Commerce 4
 *
 * @link      https://github.com/spicywebau
 * @copyright Copyright (c) 2020 Spicy Web
 */

namespace spicyweb\spicyafterpay\models;

use Craft;
use craft\base\Model;
use spicyweb\spicyafterpay\SpicyAfterpay;

/**
 * SpicyAfterpay Settings Model
 *
 * @package spicyweb\spicyafterpay\models
 * @author Spicy Web <plugins@spicyweb.com.au>
 * @since 1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string A confirmation URL.
     */
    public $confirmationUrl = '';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            ['confirmationUrl', 'string'],
        ];
    }
}
