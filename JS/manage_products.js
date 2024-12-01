document.addEventListener('DOMContentLoaded', function() {
    let productToDelete = null;

    // Edit Modal Functions
    function openEditModal(product) {
        document.getElementById('editProductId').value = product.product_id;
        document.getElementById('editProductName').value = product.product_name;
        document.getElementById('editCategory').value = product.category_id;
        document.getElementById('editPrice').value = product.price;
        document.getElementById('editQuantity').value = product.quantity;
        document.getElementById('editProductModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editProductModal').classList.add('hidden');
    }

    // Delete Modal Functions
    function openDeleteModal(productId) {
        productToDelete = productId;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        productToDelete = null;
    }

    // Update the confirmDelete function with SweetAlert
    function confirmDelete() {
        if (productToDelete) {
            // Show loading state
            Swal.fire({
                title: 'Deleting Product...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('product_id', productToDelete);

            fetch('../endpoint/delete_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Remove row from table
                    const row = document.querySelector(`tr[data-product-id="${productToDelete}"]`);
                    if (row) {
                        row.remove();
                    }
                    
                    // Close modal and show success
                    closeDeleteModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Product deleted successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.message || 'Failed to delete product');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An error occurred while deleting the product'
                });
            });
        }
    }

    // Expose functions globally for inline onclick events
    window.openEditModal = openEditModal;
    window.closeEditModal = closeEditModal;
    window.openDeleteModal = openDeleteModal;
    window.closeDeleteModal = closeDeleteModal;
    window.confirmDelete = confirmDelete;

    // Add event listener for delete confirmation button
    document.getElementById('confirmDelete').addEventListener('click', confirmDelete);

    // Updated Form submission event listener with SweetAlert
    document.getElementById('editProductForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form values for validation
        const quantity = parseInt(document.getElementById('editQuantity').value);
        
        // Validate quantity
        if (isNaN(quantity) || quantity < 0) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Quantity',
                text: 'Please enter a valid positive number for quantity'
            });
            return;
        }

        // Show loading state
        Swal.fire({
            title: 'Updating Product...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const formData = new FormData(this);

        // Debug form data
        console.log('Form data being sent:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Updated fetch request handler
        fetch('../endpoint/edit_product.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Get product ID for targeting the correct row
                const productId = formData.get('product_id');
                
                // Log for debugging
                console.log('Form Data:', Object.fromEntries(formData));
                
                // Update table row immediately with form data
                const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                if (row) {
                    row.querySelector('td:nth-child(1)').textContent = formData.get('product_name');
                    row.querySelector('td:nth-child(2)').textContent = data.category_name; // From initial response
                    row.querySelector('td:nth-child(3)').textContent = formData.get('price');
                    row.querySelector('td:nth-child(4)').textContent = formData.get('quantity');
                    
                    // Highlight updated row briefly
                    row.style.transition = 'background-color 0.5s';
                    row.style.backgroundColor = '#e8f5e9';
                    setTimeout(() => {
                        row.style.backgroundColor = '';
                    }, 2000);
                }
                
                // Close modal and show success
                closeEditModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                throw new Error(data.message || 'Failed to update product');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'An error occurred while updating the product'
            });
        });
    });
});