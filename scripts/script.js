document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', (event) => {
            let valid = true;

            const selects = form.querySelectorAll('select');
            selects.forEach(select => {
                if (select.value === '') {
                    valid = false;
                    select.style.borderColor = 'red';
                } else {
                    select.style.borderColor = '';
                }
            });

            if (!valid) {
                event.preventDefault();
                alert('Please complete all fields.');
            }
        });
    });
});
