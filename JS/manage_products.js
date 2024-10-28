document.addEventListener('DOMContentLoaded', function() {
    let currentEditId;
    let currentDeleteId;

    window.openEditModal = function(id) {
        currentEditId = id;

        // Add loading state
        const editBtn = document.querySelector(`button[onclick="openEditModal(${id})"]`);
        if (editBtn) {
            editBtn.disabled = true;
            editBtn.innerHTML = 'Loading...';
        }

        // Fetch product details
        fetch(`../endpoint/get_product.php?id=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }

                // Get the product data (adjusted for new response structure)
                const product = data.product || data;

                // Log the received data for debugging
                console.log('Received product data:', product);

                // Update form fields with product data
                document.getElementById('edit_product_id').value = product.product_id;
                document.getElementById('edit_product_name').value = product.product_name;
                document.getElementById('edit_category').value = product.category_id;
                document.getElementById('edit_price').value = product.price;
                document.getElementById('edit_quantity').value = product.quantity;

                // Show the modal
                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
            })
            .catch(error => {
                console.error('Error details:', error);
                alert('Error fetching product details: ' + error.message);
            })
            .finally(() => {
                // Reset button state
                if (editBtn) {
                    editBtn.disabled = false;
                    editBtn.innerHTML = 'Edit';
                }
            });
    };

    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('product_id', currentEditId);

        // Convert FormData to an object that matches the PHP expectations
        const productData = {
            product_id: currentEditId,
            product_name: formData.get('product_name'),
            category: formData.get('category_id'), // Convert category_id to category
            price: formData.get('price'),
            quantity: formData.get('quantity')
        };

        // Send update request
        fetch('../endpoint/edit_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(productData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message || 'Error updating product');
            }
        })
        .catch(error => {
            console.error('Error updating product:', error);
            alert('There was an error updating the product. Please try again.');
        });
    });

    window.openDeleteModal = function(id) {
        currentDeleteId = id;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    };

    document.getElementById('confirmDelete').addEventListener('click', function() {
        fetch(`../endpoint/delete_product.php?delete_id=${currentDeleteId}`)
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                location.reload();
            });
    });
});