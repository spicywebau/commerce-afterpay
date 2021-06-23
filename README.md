<img src="src/resources/img/logo.svg" width="100">

# Afterpay

Afterpay plugin for Craft Commerce 3.x

## Overview

A Craft CMS plugin adds Afterpay as a gateway for Craft Commerce.

Supports
- Purchase or Manual payment type.
- Refunds, full and partial.

## Requirements

This plugin requires: 
- Craft CMS 3.4.0 or later.
- Craft Commerce 3.0.0 or later.

## Installation

This plugin can be installed from the [Craft Plugin Store](https://plugins.craftcms.com/) or with [Composer](https://packagist.org/).

### Craft Plugin Store
Open your Craft project's control panel, navigate to the Plugin Store, search for Afterpay and click Install.

### Composer
Open your terminal, navigate to your Craft project's root directory and run the following command:
```
composer require spicywebau/commerce-afterpay
```
Then open your project's control panel, navigate to Settings &rarr; Plugins, find Afterpay and click Install.

## Configuration

Within the settings page, you'll be able to set:
- Payment Type (purchase or manual)
- Merchant ID
- Merchant Key
- Country Code 
- Sandbox mode

Both the ID and Key can be set as environment variables in your `.env` file.


## Using the plugin

In your payment area, you can render the payment button using `getPaymentFormHtml`

`{{ cart.gateway.getPaymentFormHtml({}) }}`

The payment process goes from your website > afterpay > back to the site for confirmation.

When redirecting back, the URL will contain 2 params from Afterpay, `status` and `orderToken`.

`status` can either be `CANCELLED` or `SUCCESS`, this can be used to target if it's the confirmation stage.

e.g
```twig
{% set isConfirmation = craft.app.request.getParam('status') == 'SUCCESS' and craft.app.request.getParam('orderToken') %}
```

In the near future, we'll add the ability to redirect to a predefined page.

## Roadmap
- Allow for a custom confirmation page.


---

*Created and maintained by [Spicy Web](https://spicyweb.com.au)*
