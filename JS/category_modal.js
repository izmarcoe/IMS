document.addEventListener('DOMContentLoaded', function() {
    // Add Category Form Submission
    document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add');

        fetch('../endpoint/process_category.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                addNewCategoryToTable(data.category);
                closeAddModal();
                this.reset();
                }
            });
        });
    });

   // Edit Category Form Submission
document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('../endpoint/process_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Update row in table
            const row = document.querySelector(`#category-${data.category.id}`);
            if (row) {
                // Update the row content
                row.innerHTML = `
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                        <div class="text-sm md:text-base text-gray-900">${data.category.category_name}</div>
                    </td>
                    <td class="px-4 md:px-6 py-4">
                        <div class="text-sm md:text-base text-gray-900 break-words">${data.category.description}</div>
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm md:text-base">
                        <div class="flex space-x-2">
                            <button onclick='openEditModal(${JSON.stringify(data.category)})' 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-2 md:px-3 py-1 rounded-md text-sm">
                                Edit
                            </button>
                            <button onclick="openDeleteModal(${data.category.id})" 
                                    class="bg-red-500 hover:bg-red-600 text-white px-2 md:px-3 py-1 rounded-md text-sm">
                                Delete
                            </button>
                        </div>
                    </td>
                `;
                
                // Close modal
                closeEditModal();

                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Category updated successfully',
                    confirmButtonColor: '#16a34a',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the category.');
    });
});

let categoryToDelete = null;

function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(category) {
    document.getElementById('edit_id').value = category.id;
    document.getElementById('edit_category_name').value = category.category_name;
    document.getElementById('edit_description').value = category.description;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function openDeleteModal(id) {
    categoryToDelete = id;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    categoryToDelete = null;
}

function confirmDelete() {
    if (categoryToDelete) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', categoryToDelete);

        // Remove nested tr tag that's causing the ID mismatch
        const newRow = document.createElement('tr');
        newRow.id = `category-${categoryToDelete}`;
        
        fetch('../endpoint/process_category.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Find and remove row correctly
                const rowToDelete = document.getElementById(`category-${categoryToDelete}`);
                if (rowToDelete) {
                    rowToDelete.remove();
                }
                
                closeDeleteModal();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Category deleted successfully',
                    confirmButtonColor: '#16a34a',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to delete category',
                confirmButtonColor: '#16a34a'
            });
        });
    }
}

function addNewCategoryToTable(category) {
    const tableBody = document.querySelector('tbody');
    const rows = Array.from(tableBody.children);
    const newRow = document.createElement('tr');
    
    newRow.id = `category-${category.id}`;
    newRow.className = 'hover:bg-gray-50';
    newRow.innerHTML = `
            <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                <div class="text-sm md:text-base text-gray-900">${category.category_name}</div>
            </td>
            <td class="px-4 md:px-6 py-4">
                <div class="text-sm md:text-base text-gray-900 break-words">${category.description}</div>
            </td>
            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm md:text-base">
                <div class="flex space-x-2">
                    <button onclick='openEditModal(${JSON.stringify(category)})' 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-2 md:px-3 py-1 rounded-md text-sm">
                        Edit
                    </button>
                    <button onclick="openDeleteModal(${category.id})" 
                            class="bg-red-500 hover:bg-red-600 text-white px-2 md:px-3 py-1 rounded-md text-sm">
                        Delete
                    </button>
                </div>
            </td>
    `;

    // Find correct position for new category
    let insertIndex = rows.findIndex(row => {
        const categoryName = row.querySelector('td:first-child div').textContent;
        return categoryName.localeCompare(category.category_name) > 0;
    });

    if (insertIndex === -1) {
        // Add to end if no categories come after it alphabetically
        tableBody.appendChild(newRow);
    } else {
        // Insert before the found position
        rows[insertIndex].parentNode.insertBefore(newRow, rows[insertIndex]);
    }
}