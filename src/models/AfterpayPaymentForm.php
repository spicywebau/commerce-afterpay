<?php
namespace spicyweb\spicyafterpay\models;

use craft\commerce\models\PaymentSource;
use craft\commerce\models\payments\CreditCardPaymentForm;

class AfterpayPaymentForm extends CreditCardPaymentForm
{
    /**
     * @param PaymentSource $paymentSource
     */
    public function populateFromPaymentSource(PaymentSource $paymentSource)
    {
        $this->token = $paymentSource->id;
    }
    
    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();
        
        if ($this->token) {
            return []; //No validation of form if using a token
        }
        
        return $rules;
    }
}