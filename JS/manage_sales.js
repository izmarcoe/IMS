function openEditModal(sale) {
    document.getElementById('editSalesModal').classList.remove('hidden');
    
    // Populate form fields with current sale data
    document.getElementById('editSaleId').value = sale.id;
    document.getElementById('editProductId').value = sale.product_id;
    document.getElementById('editProductName').value = sale.product_name;
    document.getElementById('editPrice').value = sale.price;
    document.getElementById('editQuantity').value = sale.quantity;
    document.getElementById('editOldQuantity').value = sale.quantity;
    
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
}

// Real-time update functions
function updateTotalSales() {
    const price = parseFloat(document.getElementById('editPrice').value) || 0;
    const quantity = parseInt(document.getElementById('editQuantity').value) || 0;
    const total = (price * quantity).toFixed(2);
    document.getElementById('editTotalSales').value = total;
}

// Add event listeners for real-time updates
document.getElementById('editQuantity').addEventListener('input', updateTotalSales);
document.getElementById('editPrice').addEventListener('input', updateTotalSales);

document.getElementById('editSaleForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const quantity = parseInt(document.getElementById('editQuantity').value);
    const price = parseFloat(document.getElementById('editPrice').value);
    const saleDate = document.getElementById('editSaleDate').value;
    
    if (quantity <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Input',
            text: 'Quantity must be greater than zero!'
        });
        return false;
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

// Remove old delete modal HTML from manage_sales.php if it exists