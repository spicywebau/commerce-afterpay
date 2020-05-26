function initAfterpayCheckout() {

	if (typeof AfterPay === "undefined") {
		setTimeout(initAfterpayCheckout, 200);
	} else {
		const $wrapper = $('.spicy-afterpay');
		const $form = $wrapper.closest('form');
		const paymentUrl = $wrapper.data('prepare');
		const $button = $wrapper.find('#afterpay');
		const region = $wrapper.data('local');

		$form.on('submit', function(e) {
			e.preventDefault();
			AfterPay.initialize({countryCode: region});

			let postData = {};
			let $formElements = $form.find('input[type=hidden]');

			for (let i = 0; i < $formElements.length; i++) {
				if ($formElements[i].name === 'action') {
					continue;
				}
				postData[$formElements[i].name] = $formElements.get(i).value;
			}

			postData['cartId'] = $wrapper.data('cartid');

			$.post(paymentUrl, postData)
				.done(function(data) {
					console.log(data);
					if (data.success) {
						AfterPay.redirect({token: data.token});
					} else {
						AfterPay.close();
					}
				});
		});

	}
}

initAfterpayCheckout();