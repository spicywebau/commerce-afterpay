function initAfterPay() {
  const btn = document.getElementById('spicy-afterpay');

  if (btn) {
    btn.addEventListener('click', function() {
      let countryCode = btn.getAttribute('data-sw-afterpay-cc');
      let token = btn.getAttribute('data-sw-afterpay-token');
      AfterPay.initialize({countryCode: countryCode});
      AfterPay.redirect({token: token});
    });

    btn.removeAttribute('disabled');
  }
}

window.onload = function() {
  initAfterPay();
}
