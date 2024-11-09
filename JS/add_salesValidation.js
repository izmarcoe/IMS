$(document).ready(function() {
    $('#product').change(function() {
        const selected = $(this).find(':selected');
        const price = selected.data('price');
        const stock = selected.data('stock');
        const categoryName = selected.data('category-name');

        $('#price').val(price);
        $('#stockInfo').text(`Available stock: ${stock}`);
        $('#category').val(categoryName);
        updateTotal();
    });

    $('#price, #quantity').on('input', updateTotal);

    function updateTotal() {
        const price = parseFloat($('#price').val()) || 0;
        const quantity = parseInt($('#quantity').val()) || 0;
        const total = (price * quantity).toFixed(2);
        $('#totalAmount').text(total);
    }

    // Set max attribute for sales_date input to current date
    const today = new Date().toISOString().split('T')[0];
    $('#sales_date').attr('max', today);

    // Form validation
    $('#saleForm').submit(function(e) {
        const selected = $('#product').find(':selected');
        const stock = selected.data('stock');
        const quantity = parseInt($('#quantity').val());
        const price = parseFloat($('#price').val());
        const salesDate = $('#sales_date').val();

        if (quantity > stock) {
            e.preventDefault();
            alert('Quantity exceeds available stock!');
            return false;
        }

        if (quantity <= 0) {
            e.preventDefault();
            alert('Quantity must be greater than zero!');
            return false;
        }

        if (price <= 0) {
            e.preventDefault();
            alert('Price must be greater than zero!');
            return false;
        }

        if (salesDate > today) {
            e.preventDefault();
            alert('Sales date cannot be in the future!');
            return false;
        }
    });
});