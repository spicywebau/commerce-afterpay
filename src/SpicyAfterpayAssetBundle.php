<?php

namespace spicyweb\spicyafterpay;

use craft\web\AssetBundle;

class SpicyAfterpayAssetBundle extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@spicyweb/spicyafterpay/resources';

        $this->js = [
            'js/paymentForm.js',
        ];

        parent::init();
    }
}
