export function setupSaleCart(form) {
    if (!form) return;
    const rows = form.querySelector('#cart-rows');
    const add = form.querySelector('#add-cart-row');
    const template = document.querySelector('#cart-row-template');
    const status = form.querySelector('#cart-status');
    const key = form.elements.request_key;

    const changed = () => {
        key.value = crypto.randomUUID();
    };
    const renumber = () => {
        [...rows.children].forEach((row, index) => {
            row.querySelectorAll('[name]').forEach(control => {
                control.name = control.name.replace(/items\[[^\]]*\]/, `items[${index}]`);
            });
            row.querySelector('.remove-cart-row').disabled = rows.children.length === 1;
        });
        add.disabled = rows.children.length >= 100;
        status.textContent = `${rows.children.length} of 100 cart rows`;
    };

    form.addEventListener('input', changed);
    form.addEventListener('change', event => {
        if (event.target.matches('.product-choice')) {
            event.target.closest('.cart-row').querySelector('.expected-price').value = event.target.selectedOptions[0]?.dataset.price || '';
        }
        changed();
    });
    add.addEventListener('click', () => {
        if (rows.children.length >= 100) return;
        rows.append(template.content.cloneNode(true));
        renumber();
        changed();
        rows.lastElementChild.querySelector('select').focus();
    });
    rows.addEventListener('click', event => {
        const button = event.target.closest('.remove-cart-row');
        if (!button || rows.children.length === 1) return;
        const row = button.closest('.cart-row');
        const next = row.nextElementSibling || row.previousElementSibling;
        row.remove();
        renumber();
        changed();
        next.querySelector('select').focus();
    });
    form.querySelector('#refresh-cart-prices').addEventListener('click', () => {
        rows.querySelectorAll('.product-choice').forEach(select => {
            select.closest('.cart-row').querySelector('.expected-price').value = select.selectedOptions[0]?.dataset.price || '';
        });
        changed();
        status.textContent = 'Displayed prices selected. Submit this cart as a new request.';
    });
    form.addEventListener('submit', event => {
        renumber();
        if (event.submitter) event.submitter.disabled = true;
        status.textContent = 'Recording sale…';
    });
    window.addEventListener('pageshow', () => {
        form.querySelector('[type="submit"]').disabled = false;
    });
    renumber();
}

setupSaleCart(document.querySelector('#sale-form'));
