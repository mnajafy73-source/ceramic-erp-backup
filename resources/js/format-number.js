function applyNumberFormatting(input) {
    input.addEventListener('input', function(e) {
        let raw = this.value.replace(/,/g, '').replace(/[^0-9.]/g, '');
        let parts = raw.split('.');
        let integerPart = parts[0];
        let decimalPart = parts.length > 1 ? '.' + parts[1] : '';
        if (integerPart !== '') {
            let formatted = parseInt(integerPart).toLocaleString('en-US');
            this.value = formatted + decimalPart;
        } else {
            this.value = decimalPart;
        }
    });

    input.addEventListener('blur', function() {
        let raw = this.value.replace(/,/g, '').replace(/[^0-9.]/g, '');
        if (raw !== '' && !isNaN(raw)) {
            let parts = raw.split('.');
            let integerPart = parts[0];
            let decimalPart = parts.length > 1 ? '.' + parts[1] : '';
            if (integerPart !== '') {
                let formatted = parseInt(integerPart).toLocaleString('en-US');
                this.value = formatted + decimalPart;
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.format-number').forEach(function(input) {
        applyNumberFormatting(input);
    });
});

window.applyFormatToNewInputs = function(container) {
    container.querySelectorAll('.format-number').forEach(function(input) {
        if (!input.dataset.formatted) {
            applyNumberFormatting(input);
            input.dataset.formatted = 'true';
        }
    });
};