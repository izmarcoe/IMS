document.addEventListener('DOMContentLoaded', function() {
    var actionModal = new bootstrap.Modal(document.getElementById('actionModal'));
    var confirmButton = document.getElementById('confirmAction');
    var editProductModal = new bootstrap.Modal(document.getElementById('editProductModal'));
    var editProductForm = document.getElementById('editProductForm');

    document.querySelectorAll('.delete-btn').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            var deleteForm = this.nextElementSibling;
            actionModal.show();

            confirmButton.onclick = function() {
                deleteForm.submit();
            };
        });
    });

    document.querySelectorAll('.edit-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            var product = JSON.parse(this.getAttribute('data-product'));
            document.getElementById('editProductId').value = product.product_id;
            document.getElementById('editProductName').value = product.product_name;
            document.getElementById('editCategory').value = product.category_id;
            document.getElementById('editPrice').value = product.price;
            document.getElementById('editQuantity').value = product.quantity;
            editProductModal.show();
        });
    });

    editProductForm.addEventListener('submit', function(event) {
        var price = document.getElementById('editPrice').value;
        var quantity = document.getElementById('editQuantity').value;

        if (price <= 0) {
            alert('Price must be at least 0.');
            event.preventDefault();
        }

        if (quantity <= 0) {
            alert('Quantity must be at least 0.');
            event.preventDefault();
        }
    });
});