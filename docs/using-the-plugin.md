# Using the plugin

In your payment area, you can render the payment button using `getPaymentFormHtml`

`{{ cart.gateway.getPaymentFormHtml({}) }}`

The payment process goes from your website > afterpay > back to the site for confirmation.

When redirecting back, the URL will contain 2 params from Afterpay, `status` and `orderToken`.

`status` can either be `CANCELLED` or `SUCCESS`, this can be used to target if it's the confirmation stage.

e.g
```twig
{% set isConfirmation = craft.app.request.getParam('status') == 'SUCCESS' and craft.app.request.getParam('orderToken') %}
```

In the future, we'll add the ability to redirect to a predefined page.
