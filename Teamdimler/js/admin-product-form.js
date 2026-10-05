const categoryField = document.querySelector('#categoria');
const stockField = document.querySelector('#stock');
const stockHelp = document.querySelector('#stock-ayuda');
const madeToOrderAvailability = document.querySelector('#disponibilidad-manual');
const availabilityField = document.querySelector('#disponible');
const productIdField = document.querySelector('input[name="producto_id"]');

if (
    categoryField instanceof HTMLSelectElement
    && stockField instanceof HTMLInputElement
    && stockHelp instanceof HTMLElement
    && madeToOrderAvailability instanceof HTMLElement
    && availabilityField instanceof HTMLSelectElement
    && productIdField instanceof HTMLInputElement
) {
    const updateInventoryFields = () => {
        const isMadeToOrder = ['crochet', 'tejidos'].includes(categoryField.value);
        stockField.disabled = false;
        stockField.required = !isMadeToOrder && productIdField.value === '';
        availabilityField.disabled = !isMadeToOrder;
        madeToOrderAvailability.hidden = !isMadeToOrder;

        if (isMadeToOrder) {
            stockHelp.textContent = 'Podés registrar la cantidad para administración; la disponibilidad para clientes se controla aparte.';
        } else {
            stockHelp.textContent = 'Ingresá cuántas unidades tenés. Al llegar a 0, el producto queda agotado.';
        }
    };

    categoryField.addEventListener('change', updateInventoryFields);
    updateInventoryFields();
}
