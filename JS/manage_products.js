document.addEventListener('DOMContentLoaded', function() {
    let currentEditId;
    let currentDeleteId;

    window.openEditModal = function(id) {
        currentEditId = id;

        // Fetch product details
        fetch(`../endpoint/get_product.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                document.getElementById('edit_product_id').value = data.product_id;
                document.getElementById('edit_product_name').value = data.product_name;
                document.getElementById('edit_category').value = data.category;
                document.getElementById('edit_price').value = data.price;
                document.getElementById('edit_quantity').value = data.quantity;

                var editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
            })
            .catch(error => {
                console.error('Error fetching product details:', error);
            });
    };

    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const formData = new FormData(this); // Collect form data

        // Add product_id to formData
        formData.append('product_id', currentEditId); // This ensures the ID is sent for updating

        // Send an AJAX request to update the product
        fetch('../endpoint/edit_product.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message); // Display success/error message
                if (data.success) {
                    location.reload(); // Reload to show updated product list
                }
            })
            .catch(error => {
                console.error('Error updating product:', error);
                alert('There was an error updating the product. Please try again.'); // Display error message
            });
    });


    window.openDeleteModal = function(id) {
        currentDeleteId = id;
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    };

    document.getElementById('confirmDelete').addEventListener('click', function() {
        fetch(`../endpoint/delete_product.php?delete_id=${currentDeleteId}`)
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                location.reload(); // Reload to refresh the product list
            });
    });
});