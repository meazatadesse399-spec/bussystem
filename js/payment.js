// Payment form handling
document.addEventListener('DOMContentLoaded', function () {
    // Credit card input formatting
    const cardNumber = document.getElementById('card_number');
    if (cardNumber) {
        cardNumber.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})/g, '$1 ').trim();
            e.target.value = value.substring(0, 19);
        });
    }

    // Expiry date formatting
    const expiryDate = document.getElementById('expiry_date');
    if (expiryDate) {
        expiryDate.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value.substring(0, 5);
        });
    }

    // CVV formatting
    const cvv = document.getElementById('cvv');
    if (cvv) {
        cvv.addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
        });
    }

    // Payment method selection
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    paymentMethods.forEach(method => {
        method.addEventListener('change', function () {
            document.querySelectorAll('.payment-method').forEach(pm => {
                pm.classList.remove('selected');
            });
            if (this.checked) {
                this.closest('.payment-method').classList.add('selected');
            }
        });
    });
});

// Mock payment processing
function processPayment(paymentData) {
    return new Promise((resolve, reject) => {
        // Simulate API call
        setTimeout(() => {
            // Mock successful payment 90% of the time
            if (Math.random() < 0.9) {
                resolve({
                    success: true,
                    transactionId: 'TXN' + Date.now(),
                    message: 'Payment successful!'
                });
            } else {
                reject({
                    success: false,
                    message: 'Payment failed. Please try again.'
                });
            }
        }, 1500);
    });
}