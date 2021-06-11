function initAfterPay() {
  const btn = document.getElementById('spicy-afterpay');

  if (btn) {
    let countryCode = btn.getAttribute('data-sw-afterpay-cc');
    let token = btn.getAttribute('data-sw-afterpay-token');

    btn.addEventListener('click', function(e) {
      e.preventDefault();
      AfterPay.initialize({countryCode: countryCode});
      // AfterPay.open();

      // If you don't already have a checkout token at this point, you can
      // AJAX to your backend to retrieve one here. The spinning animation
      // will continue until `AfterPay.transfer` is called.
      // If you fail to get a token you can call AfterPay.close()
      // AfterPay.onComplete = function(event) {
      //   console.log(event);
      //   if (event.data.status === "SUCCESS") {
      //     // The consumer confirmed the payment schedule.
      //     // The token is now ready to be captured from your server backend.
      //   } else {
      //     // The consumer cancelled the payment or closed the popup window.
      //     AfterPay.close();
      //   }
      // }
      //
      // AfterPay.transfer({token: token});

      AfterPay.redirect({token: token});
    });

    btn.removeAttribute('disabled');
  }
}

window.onload = function() {
  initAfterPay();
}
