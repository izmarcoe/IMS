let originalQuantity = 0;
let availableStock = 0;

async function checkStock(productId, requestedQuantity, originalQty) {
    try {
        const response = await fetch('../endpoint/check_stock.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${requestedQuantity}&original_quantity=${originalQty}`
        });
        return await response.json();
    } catch (error) {
        console.error('Error checking stock:', error);
        return { success: false, message: 'Error checking stock availability' };
    }
}

// Add new function to fetch products
async function fetchProducts(searchTerm) {
    try {
        const response = await fetch(`../endpoint/search_products.php?term=${encodeURIComponent(searchTerm)}`);
        const data = await response.json();
        const datalist = document.getElementById('productsList');
        datalist.innerHTML = '';
        
        data.products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.product_name;
            option.dataset.id = product.product_id;
            option.dataset.price = product.price;
            option.dataset.stock = product.quantity;
            datalist.appendChild(option);
        });
    } catch (error) {
        console.error('Error fetching products:', error);
    }
}

function openEditModal(sale) {
    originalQuantity = parseInt(sale.quantity); // Changed this line

    document.getElementById('editSalesModal').classList.remove('hidden');
    
    // Set initial form values
    document.getElementById('editSaleId').value = sale.id;
    document.getElementById('editProductId').value = sale.product_id;
    document.getElementById('editProductName').value = sale.product_name;
    document.getElementById('editPrice').value = sale.price;
    document.getElementById('editQuantity').value = sale.quantity;
    document.getElementById('editOldQuantity').value = sale.quantity;
    
    // Get current stock from products table
    fetch(`../endpoint/get_product_stock.php?id=${sale.product_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add current sale quantity to available stock
                const totalAvailableStock = parseInt(data.stock) + originalQuantity;
                document.getElementById('stockInfo').textContent = 
                    `Available Stock: ${totalAvailableStock}`;
                document.getElementById('editQuantity').max = totalAvailableStock;
            }
        });
    
    // Calculate and set total sales
    const totalSales = (parseFloat(sale.price) * parseInt(sale.quantity)).toFixed(2);
    document.getElementById('editTotalSales').value = totalSales;
    
    // Format and set date
    const saleDate = new Date(sale.sale_date).toISOString().split('T')[0];
    document.getElementById('editSaleDate').value = saleDate;
    document.getElementById('editSaleDate').max = new Date().toISOString().split('T')[0];
}

function closeEditModal() {
    document.getElementById('editSalesModal').classList.add('hidden');
    window.location.reload();
}

// Real-time update functions
function updateTotalSales() {
    const price = parseFloat(document.getElementById('editPrice').value) || 0;
    const quantity = parseInt(document.getElementById('editQuantity').value) || 0;
    const total = (price * quantity).toFixed(2);
    document.getElementById('editTotalSales').value = total;
}

// Add event listeners for real-time updates
document.getElementById('editQuantity').addEventListener('input', async function() {
    const quantity = parseInt(this.value) || 0;
    const productId = document.getElementById('editProductId').value;
    const errorDiv = document.getElementById('quantityError');
    
    if (quantity <= 0) {
        errorDiv.textContent = 'Quantity must be greater than 0';
        errorDiv.classList.remove('hidden');
        return;
    }

    const stockCheck = await checkStock(productId, quantity, originalQuantity);
    
    // Only show error if quantity is strictly greater than available stock
    if (!stockCheck.success || (stockCheck.availableStock < quantity)) {
        errorDiv.textContent = 'Exceeded available stock';
        errorDiv.classList.remove('hidden');
        this.classList.add('border-red-500');
    } else {
        errorDiv.classList.add('hidden');
        this.classList.remove('border-red-500');
        updateTotalSales();
    }
});

document.getElementById('editPrice').addEventListener('input', updateTotalSales);

document.getElementById('editSaleForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const quantity = parseInt(document.getElementById('editQuantity').value);
    const price = parseFloat(document.getElementById('editPrice').value);
    const saleDate = document.getElementById('editSaleDate').value;
    const errorDiv = document.getElementById('quantityError');
    
    if (quantity <= 0) {
        errorDiv.textContent = 'Quantity must be greater than 0';
        errorDiv.classList.remove('hidden');
        return;
    }

    const stockCheck = await checkStock(
        document.getElementById('editProductId').value,
        quantity,
        originalQuantity
    );

    if (!stockCheck.success || !stockCheck.isAvailable) {
        errorDiv.textContent = 'Exceeded available stock';
        errorDiv.classList.remove('hidden');
        return;
    }

    const formData = new FormData(this);
    
    try {
        const response = await fetch('../endpoint/update_sale.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            const saleId = formData.get('sale_id');
            const row = document.querySelector(`tr[data-sale-id="${saleId}"]`);
            
            if (row) {
                // Update table row
                row.querySelector('.quantity').textContent = quantity;
                row.querySelector('.total-sales').textContent = (price * quantity).toFixed(2);
                row.querySelector('.sale-date').textContent = new Date(saleDate)
                    .toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });

                // Update the sale object for next edit
                const updatedSale = {
                    ...data.sale,
                    quantity: quantity,
                    price: price,
                    sale_date: saleDate,
                    total_sales: (price * quantity).toFixed(2)
                };

                // Update click handler with new data
                row.querySelector('button[onclick*="openEditModal"]')
                    .setAttribute('onclick', `openEditModal(${JSON.stringify(updatedSale)})`);
            }

            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Sale updated successfully',
                timer: 1500
            });

            closeEditModal();
        } else {
            throw new Error(data.message || 'Failed to update sale');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'An error occurred while updating the sale'
        });
    }
});

let saleToDelete = null;

function deleteSale(id) {
    Swal.fire({
        title: 'Delete Sale Record',
        text: 'Are you sure you want to delete this sale?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `manage_sales.php?delete_id=${id}`;
        }
    });
}

// Add product input event listener
if (document.getElementById('editProductName')) {
    document.getElementById('editProductName').addEventListener('input', function(e) {
        if (this.readOnly) return;
        fetchProducts(this.value);
    });

    // Handle product selection
    document.getElementById('editProductName').addEventListener('change', function() {
        const datalist = document.getElementById('editProductsList');
        const options = datalist.getElementsByTagName('option');
        
        for(let option of options) {
            if(option.value === this.value) {
                const productId = option.dataset.id;
                const price = option.dataset.price;
                const stock = option.dataset.stock;
                
                document.getElementById('editProductId').value = productId;
                document.getElementById('editPrice').value = price;
                document.getElementById('stockInfo').textContent = `Available Stock: ${stock}`;
                
                // Reset quantity to 1 when product changes
                document.getElementById('editQuantity').value = 1;
                updateTotalSales();
                break;
            }
        }
    });
}

// Remove old delete modal HTML from manage_sales.php if it exists

// Add these functions after existing code

function initializeProductDropdown() {
    const productSelect = document.getElementById('editProductSelect');
    const productInput = document.getElementById('editProductName');
    const dropdownContainer = document.getElementById('productDropdownContainer');

    // Show/hide dropdown on input focus
    productInput.addEventListener('focus', () => {
        if (!productInput.readOnly) {
            dropdownContainer.classList.remove('hidden');
        }
    });

    // Handle clicking outside
    document.addEventListener('click', (e) => {
        if (!dropdownContainer.contains(e.target) && !productInput.contains(e.target)) {
            dropdownContainer.classList.add('hidden');
        }
    });

    // Filter products on input
    productInput.addEventListener('input', function() {
        if (this.readOnly) return;
        
        const searchTerm = this.value.toLowerCase();
        const options = productSelect.getElementsByTagName('option');
        
        Array.from(options).forEach(option => {
            const text = option.textContent.toLowerCase();
            option.style.display = text.includes(searchTerm) ? '' : 'none';
        });
        
        dropdownContainer.classList.remove('hidden');
    });

    // Handle product selection
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        productInput.value = selectedOption.textContent;
        
        document.getElementById('editProductId').value = selectedOption.dataset.id;
        document.getElementById('editPrice').value = selectedOption.dataset.price;
        document.getElementById('stockInfo').textContent = `Available Stock: ${selectedOption.dataset.stock}`;
        
        document.getElementById('editQuantity').value = 1;
        updateTotalSales();
        
        dropdownContainer.classList.add('hidden');
    });
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', initializeProductDropdown);