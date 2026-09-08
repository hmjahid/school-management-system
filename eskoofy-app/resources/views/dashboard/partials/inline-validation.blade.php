<script>
    document.querySelectorAll('input[data-inline-match]').forEach(function (confirm) {
        var primary = document.querySelector(confirm.dataset.inlineMatch);
        var err = document.getElementById(confirm.dataset.errorFor);

        function check() {
            var mismatch = confirm.value.length > 0 && primary && confirm.value !== primary.value;
            confirm.classList.toggle('border-red-500', mismatch);
            confirm.classList.toggle('focus:border-red-500', mismatch);
            if (err) {
                err.classList.toggle('hidden', ! mismatch);
            }
        }
        confirm.addEventListener('input', check);
        if (primary) {
            primary.addEventListener('input', check);
        }
    });
</script>