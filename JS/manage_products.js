document.addEventListener('DOMContentLoaded', function() {
    let productToDelete = null;
    let productIdToArchive = null;

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
        
        const formData = new FormData(this);
        
        fetch('../endpoint/edit_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('editProductModal').classList.add('hidden');
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.error || 'Failed to update product');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: error.message,
                confirmButtonColor: '#EF4444'
            });
        });
    });

    // Archive functionality
    function openArchiveModal(productId) {
        Swal.fire({
            title: 'Archive Product?',
            text: 'This product will be moved to archives. Continue?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, archive it!'
        }).then((result) => {
            if (result.isConfirmed) {
                archiveProduct(productId);
            }
        });
    }

    function archiveProduct(productId) {
        Swal.fire({
            title: 'Archiving Product...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('../endpoint/archive-product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Archived!',
                    text: 'Product has been moved to archives.',
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.error || 'Failed to archive product');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: error.message,
                confirmButtonColor: '#EF4444'
            });
        });
    }

    // Expose functions globally
    window.openArchiveModal = openArchiveModal;

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editProductModal');
        if (event.target === modal) {
            closeEditModal();
        }
    };
});

// Edit form submission handler
document.getElementById('editProductForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Get form values for validation
    const quantity = parseInt(document.getElementById('editQuantity').value);
    const price = parseFloat(document.getElementById('editPrice').value);
    const productName = document.getElementById('editProductName').value.trim();
    const categoryId = document.getElementById('editCategory').value;

    // Validate inputs
    if (!productName) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Input',
            text: 'Product name cannot be empty!'
        });
        return;
    }

    if (!categoryId) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Input',
            text: 'Please select a category!'
        });
        return;
    }

    if (isNaN(price) || price <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Price',
            text: 'Price must be greater than 0!'
        });
        return;
    }

    if (isNaN(quantity) || quantity < 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Quantity',
            text: 'Quantity must be greater than or equal to 0!'
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

    fetch('../endpoint/edit_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Get product ID for targeting the correct row
            const productId = formData.get('product_id');
            
            // Update table row immediately with form data
            const row = document.querySelector(`tr[data-product-id="${productId}"]`);
            if (row) {
                row.querySelector('td:nth-child(1)').textContent = formData.get('product_name');
                const categorySelect = document.getElementById('editCategory');
                const selectedCategory = categorySelect.options[categorySelect.selectedIndex].text;
                row.querySelector('td:nth-child(2)').textContent = selectedCategory;
                row.querySelector('td:nth-child(3)').textContent = formData.get('price');
                row.querySelector('td:nth-child(4)').textContent = formData.get('quantity');
            }
            
            // Close modal and show success
            document.getElementById('editProductModal').classList.add('hidden');
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Product updated successfully',
                confirmButtonColor: '#10B981'
            });
        } else {
            throw new Error(data.error || 'Failed to update product');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: error.message,
            confirmButtonColor: '#EF4444'
        });
    });
});

// Add the select element for category
const categorySelectHTML = `
<select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
    id="editCategory" 
    name="category_id" 
    required>
    <option value="" disabled selected>Select a category</option>
    <?php foreach ($categories as $category): ?>
        <option value="<?php echo htmlspecialchars($category['id']); ?>">
            <?php echo htmlspecialchars($category['category_name']); ?>
        </option>
    <?php endforeach; ?>
</select>
`;

// Insert the select element into the DOM
document.getElementById('editCategoryContainer').innerHTML = categorySelectHTML;

// Function to create a modification request
function createModificationRequest(product) {
    Swal.fire({
        title: 'Request Product Modification',
        html: `
            <div class="space-y-6 p-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Name</label>
                    <input type="text" id="newName" 
                        class="mt-1 block w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-600 cursor-not-allowed" 
                        value="${product.product_name}"
                        readonly>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                    <input type="number" id="newPrice" 
                        class="mt-1 block w-full px-4 py-2 bg-white border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                        value="${product.price}"
                        min="0.01"
                        step="0.01">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                    <input type="number" id="newQuantity" 
                        class="mt-1 block w-full px-4 py-2 bg-white border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                        value="${product.quantity}"
                        min="1"
                        max="999">
                    <p class="mt-1 text-sm text-gray-500">Enter a quantity between 1 and 999</p>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit Request',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const newQuantity = parseInt(document.getElementById('newQuantity').value);
            const newPrice = parseFloat(document.getElementById('newPrice').value);

            if (newQuantity < 1 || newQuantity > 999) {
                Swal.showValidationMessage('Quantity must be between 1 and 999');
                return false;
            }

            if (newPrice <= 0) {
                Swal.showValidationMessage('Price must be greater than 0');
                return false;
            }

            const formData = new FormData();
            formData.append('product_id', product.product_id);
            formData.append('new_name', document.getElementById('newName').value);
            formData.append('new_price', document.getElementById('newPrice').value);
            formData.append('new_quantity', document.getElementById('newQuantity').value);

            return fetch('../endpoint/create_product_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to submit request');
                }
                return data;
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Request Submitted',
                text: 'Your modification request has been sent to admin for approval.'
            });
        }
    }).catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    });
}