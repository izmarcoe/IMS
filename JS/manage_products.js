document.addEventListener("DOMContentLoaded", function () {
  let productToDelete = null;
  let productIdToArchive = null;

  // Edit Modal Functions
  function openEditModal(product) {
    document.getElementById('editProductId').value = product.product_id;
    document.getElementById('editProductName').value = product.product_name;
    updateEditCharCount(document.getElementById('editProductName'));
    $("#editCategory").val(product.category_id);
    $("#editPrice").val(product.price);
    $("#editCurrentQuantity").val(product.quantity);
    $("#editAdditionalQuantity").val("");
    $("#newTotalQuantity")
      .text(product.quantity)
      .removeClass("text-green-600 text-red-600")
      .addClass("text-gray-600");
    $("#editProductModal").removeClass("hidden");
  }
  function closeEditModal() {
    document.getElementById("editProductModal").classList.add("hidden");
  }

  // Delete Modal Functions
  function openDeleteModal(productId) {
    productToDelete = productId;
    document.getElementById("deleteModal").classList.remove("hidden");
  }

  function closeDeleteModal() {
    document.getElementById("deleteModal").classList.add("hidden");
    productToDelete = null;
  }

  // Update the confirmDelete function with SweetAlert
  function confirmDelete() {
    if (productToDelete) {
      // Show loading state
      Swal.fire({
        title: "Deleting Product...",
        text: "Please wait",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      const formData = new FormData();
      formData.append("product_id", productToDelete);

      fetch("../endpoint/delete_product.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            // Remove row from table
            const row = document.querySelector(
              `tr[data-product-id="${productToDelete}"]`
            );
            if (row) {
              row.remove();
            }

            // Close modal and show success
            closeDeleteModal();
            Swal.fire({
              icon: "success",
              title: "Success!",
              text: "Product deleted successfully",
              timer: 2000,
              showConfirmButton: false,
            });
          } else {
            throw new Error(data.message || "Failed to delete product");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire({
            icon: "error",
            title: "Error",
            text:
              error.message || "An error occurred while deleting the product",
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
  document
    .getElementById("confirmDelete")
    .addEventListener("click", confirmDelete);

  // Updated Form submission event listener with SweetAlert
  document
    .getElementById("editProductForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();

      const currentQuantity = parseInt(
        document.getElementById("editCurrentQuantity").value
      );
      const additionalQuantity =
        parseInt(document.getElementById("editAdditionalQuantity").value) || 0;

      if (additionalQuantity < 0) {
        Swal.fire({
          icon: "error",
          title: "Invalid Quantity",
          text: "Additional quantity cannot be negative",
          confirmButtonColor: "#EF4444",
        });
        return;
      }

      const formData = new FormData(this);
      formData.set("quantity", currentQuantity + additionalQuantity); // Set total quantity

      fetch("../endpoint/edit_product.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            Swal.fire({
              icon: "success",
              title: "Success!",
              text: "Product updated successfully",
              confirmButtonColor: "#10B981",
            }).then(() => {
              window.location.reload();
            });
          } else {
            throw new Error(data.error || "Failed to update product");
          }
        })
        .catch((error) => {
          Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.message,
            confirmButtonColor: "#EF4444",
          });
        });
    });

  // Archive functionality
  function openArchiveModal(productId) {
    Swal.fire({
      title: "Archive Product?",
      text: "This product will be moved to archives. Continue?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#EF4444",
      cancelButtonColor: "#6B7280",
      confirmButtonText: "Yes, archive it!",
    }).then((result) => {
      if (result.isConfirmed) {
        archiveProduct(productId);
      }
    });
  }

  function archiveProduct(productId) {
    Swal.fire({
      title: "Archiving Product...",
      text: "Please wait",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    fetch("../endpoint/archive-product.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `product_id=${productId}`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "Archived!",
            text: "Product has been moved to archives.",
            confirmButtonColor: "#10B981",
          }).then(() => {
            window.location.reload();
          });
        } else {
          throw new Error(data.error || "Failed to archive product");
        }
      })
      .catch((error) => {
        Swal.fire({
          icon: "error",
          title: "Error!",
          text: error.message,
          confirmButtonColor: "#EF4444",
        });
      });
  }

  // Expose functions globally
  window.openArchiveModal = openArchiveModal;

  // Close modal when clicking outside
  window.onclick = function (event) {
    const modal = document.getElementById("editProductModal");
    if (event.target === modal) {
      closeEditModal();
    }
  };

  // Add this to calculate and show new total quantity
  document
    .getElementById("editAdditionalQuantity")
    .addEventListener("input", function () {
      const currentQty =
        parseInt(document.getElementById("editCurrentQuantity").value) || 0;
      const additionalQty = parseInt(this.value) || 0;
      const newTotal = currentQty + additionalQty;

      if (newTotal > 999) {
        Swal.fire({
          icon: "error",
          title: "Invalid Quantity",
          text: "Total quantity cannot exceed 999",
          confirmButtonColor: "#EF4444",
        });
        this.value = 999 - currentQty;
        document.getElementById("newTotalQuantity").textContent = 999;
      } else {
        document.getElementById("newTotalQuantity").textContent = newTotal;
      }
    });

  const additionalQuantityInput = document.getElementById(
    "editAdditionalQuantity"
  );
  const currentQuantityInput = document.getElementById("editCurrentQuantity");
  const newTotalSpan = document.getElementById("newTotalQuantity");

  additionalQuantityInput.addEventListener("input", function () {
    const currentQty = parseInt(currentQuantityInput.value) || 0;
    const additionalQty = parseInt(this.value) || 0;
    const newTotal = currentQty + additionalQty;

    if (newTotal > 999) {
      Swal.fire({
        icon: "error",
        title: "Invalid Quantity",
        text: "Total quantity cannot exceed 999",
        confirmButtonColor: "#EF4444",
      });
      this.value = 999 - currentQty;
      newTotalSpan.textContent = 999;
      newTotalSpan.className = "font-bold text-red-600";
    } else {
      newTotalSpan.textContent = newTotal;
      newTotalSpan.className = "font-bold text-green-600";
    }
  });

  // Reset total display when modal opens
  window.openEditModal = function (product) {
    $("#editProductId").val(product.product_id);
    $("#editProductName").val(product.product_name);
    updateEditCharCount(document.getElementById('editProductName'));
    $("#editCategory").val(product.category_id);
    $("#editPrice").val(product.price);
    $("#editCurrentQuantity").val(product.quantity);
    $("#editAdditionalQuantity").val("");
    $("#newTotalQuantity")
      .html(product.quantity)
      .removeClass("text-green-600 text-red-600")
      .addClass("text-gray-600");
    $("#editProductModal").removeClass("hidden");
  };

  // Add this to your existing DOMContentLoaded event
  $(document).ready(function () {
    const $additionalQty = $("#editAdditionalQuantity");
    const $currentQty = $("#editCurrentQuantity");
    const $newTotal = $("#newTotalQuantity");

    $additionalQty.on("input", function () {
      let additionalQty = parseInt($(this).val()) || 0;
      let currentQty = parseInt($currentQty.val()) || 0;
      let newTotal = currentQty + additionalQty;

      // Validation
      if (additionalQty < 1) {
        $(this).val("");
        $newTotal
          .html("-")
          .removeClass("text-green-600 text-red-600")
          .addClass("text-gray-600");
        showError("Additional quantity must be at least 1");
        return;
      }

      if (additionalQty > 999) {
        $(this).val(999);
        additionalQty = 999;
        showError("Additional quantity cannot exceed 999");
      }

      if (newTotal > 999) {
        $(this).val(999 - currentQty);
        newTotal = 999;
        showError("Total quantity cannot exceed 999");
      }

      // Update display
      if (newTotal <= 999 && newTotal >= currentQty) {
        $newTotal
          .html(newTotal)
          .removeClass("text-gray-600 text-red-600")
          .addClass("text-green-600");
      }
    });

    function showError(message) {
      Swal.fire({
        icon: "error",
        title: "Invalid Quantity",
        text: message,
        confirmButtonColor: "#EF4444",
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false,
      });
    }
  });
});

// Edit form submission handler
document
  .getElementById("editProductForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const updatedFields = {};

    // Only include fields that have values
    formData.forEach((value, key) => {
      if (value.trim() !== "") {
        updatedFields[key] = value;
      }
    });

    // Always include product_id
    updatedFields.product_id = document.getElementById("editProductId").value;

    // Show loading state
    Swal.fire({
      title: "Updating Product...",
      text: "Please wait",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    // Send AJAX request
    fetch("../endpoint/edit_product.php", {
      method: "POST",
      body: new FormData(this),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "Success!",
            text: "Product updated successfully",
            confirmButtonColor: "#10B981",
          }).then(() => {
            window.location.reload();
          });
        } else {
          throw new Error(data.error || "Failed to update product");
        }
      })
      .catch((error) => {
        Swal.fire({
          icon: "error",
          title: "Error!",
          text: error.message,
          confirmButtonColor: "#EF4444",
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
document.getElementById("editCategoryContainer").innerHTML = categorySelectHTML;

function createModificationRequest(product) {
  Swal.fire({
    title: "Request Product Modification",
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
                        class="mt-1 block w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-600 cursor-not-allowed" 
                        value="${product.price}"
                        readonly>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Quantity</label>
                    <input type="number" id="currentQuantity" 
                        class="mt-1 block w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-600 cursor-not-allowed" 
                        value="${product.quantity}"
                        readonly>
                </div>
              <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Quantity</label>
                        <input type="number" id="additionalQuantity" 
                            class="mt-1 block w-full px-4 py-2 bg-white border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                            min="1"
                            max="999"
                            maxlength="3"
                            onkeydown="return event.keyCode !== 190 && event.keyCode !== 110"
                            oninput="if (this.value.length > 3) this.value = this.value.slice(0, 3); validateAdditionalQuantity(this, ${
                            product.quantity
                            })"
                            placeholder="Enter quantity (1-${999 - product.quantity})"
                            required>
                        <p class="mt-1 text-sm text-gray-500">Maximum additional quantity allowed: ${
                        999 - product.quantity
                        }</p>
                </div>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: "Submit Request",
    showLoaderOnConfirm: true,
    preConfirm: () => {
      const additionalQty = parseInt(
        document.getElementById("additionalQuantity").value
      );
      const currentQty = parseInt(
        document.getElementById("currentQuantity").value
      );

      if (!additionalQty || additionalQty < 1) {
        Swal.showValidationMessage("Additional quantity must be at least 1");
        return false;
      }

      if (currentQty + additionalQty > 999) {
        Swal.showValidationMessage("Total quantity cannot exceed 999");
        return false;
      }

      const formData = new FormData();
      formData.append("product_id", product.product_id);
      formData.append("new_name", document.getElementById("newName").value);
      formData.append("new_price", document.getElementById("newPrice").value);
      formData.append("current_quantity", currentQty);
      formData.append("new_quantity", currentQty + additionalQty);

      return fetch("../endpoint/create_product_request.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (!data.success)
            throw new Error(data.message || "Failed to submit request");
          return data;
        });
    },
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        icon: "success",
        title: "Request Submitted",
        text: "Your modification request has been sent to admin for approval.",
      });
    }
  });
}

function validateAdditionalQuantity(input, currentQty) {
  const additionalQty = parseInt(input.value) || 0;
  const totalQty = currentQty + additionalQty;

  if (input.value.length > 3) {
    input.value = input.value.slice(0, 3);
  }

  if (totalQty > 999) {
    input.value = 999 - currentQty;
    Swal.fire({
      icon: "error",
      title: "Invalid Quantity",
      text: "Total quantity cannot exceed 999",
      timer: 2000,
      showConfirmButton: false,
    });
  }
}

function validatePrice(input) {
  const price = parseFloat(input.value);
  const errorDiv = document.getElementById("priceError");

  if (price < 1 || price > 99999) {
    errorDiv.textContent = "Price must be between ₱1 and ₱99,999";
    errorDiv.classList.remove("hidden");
    input.classList.add("border-red-500");
    return false;
  } else {
    errorDiv.classList.add("hidden");
    input.classList.remove("border-red-500");
    return true;
  }
}

// Add to form submit handler
document
  .getElementById("editProductForm")
  .addEventListener("submit", function (e) {
    const price = document.getElementById("editPrice");
    if (!validatePrice(price)) {
      e.preventDefault();
    }
  });

// Add this script at the end of file

$(document).ready(function () {
  $("#editAdditionalQuantity").on("input", function () {
    const additionalQty = parseInt($(this).val()) || 0;
    const currentQty = parseInt($("#editCurrentQuantity").val()) || 0;
    const $totalSpan = $("#newTotalQuantity");

    // Clear display if input is empty
    if (!$(this).val()) {
      $totalSpan
        .html("-")
        .removeClass("text-green-600 text-red-600")
        .addClass("text-gray-600");
      return;
    }

    // Validate input range
    if (additionalQty < 1) {
      $(this).val("");
      $totalSpan
        .html("-")
        .removeClass("text-green-600 text-red-600")
        .addClass("text-gray-600");
      showError("Additional quantity must be at least 1");
      return;
    }

    // Calculate new total
    const newTotal = currentQty + additionalQty;

    // Validate total
    if (newTotal > 999) {
      $(this).val(999 - currentQty);
      $totalSpan
        .html("999")
        .removeClass("text-gray-600 text-green-600")
        .addClass("text-red-600");
      showError("Total quantity cannot exceed 999");
      return;
    }

    // Update display with valid total
    $totalSpan
      .html(newTotal)
      .removeClass("text-gray-600 text-red-600")
      .addClass("text-green-600");
  });

  function showError(message) {
    Swal.fire({
      icon: "error",
      title: "Invalid Quantity",
      text: message,
      confirmButtonColor: "#EF4444",
      timer: 2000,
      timerProgressBar: true,
      showConfirmButton: false,
    });
  }
});

// Add this single event listener for the additional quantity input
$(document).ready(function () {
  $("#editAdditionalQuantity").on("input", function () {
    // Get input values
    const currentQty = parseInt($("#editCurrentQuantity").val()) || 0;
    const additionalQty = parseInt($(this).val()) || 0;
    const $totalSpan = $("#newTotalQuantity");

    // Clear the new total if input is empty
    if (!$(this).val()) {
      $totalSpan
        .text(currentQty)
        .removeClass("text-green-600 text-red-600")
        .addClass("text-gray-600");
      return;
    }

    // Calculate new total
    const newTotal = currentQty + additionalQty;

    // Validate and display new total
    if (additionalQty < 1) {
      $totalSpan.text(currentQty);
      $(this).val("");
      showError("Additional quantity must be at least 1");
    } else if (newTotal > 999) {
      const maxAdditional = 999 - currentQty;
      $(this).val(maxAdditional);
      $totalSpan
        .text("999")
        .removeClass("text-gray-600 text-green-600")
        .addClass("text-red-600");
      showError("Total quantity cannot exceed 999");
    } else {
      $totalSpan
        .text(newTotal)
        .removeClass("text-gray-600 text-red-600")
        .addClass("text-green-600");
    }
  });

  function showError(message) {
    Swal.fire({
      icon: "error",
      title: "Invalid Quantity",
      text: message,
      confirmButtonColor: "#EF4444",
      timer: 2000,
      timerProgressBar: true,
      showConfirmButton: false,
    });
  }
});

// Update openEditModal to set initial total
function openEditModal(product) {
  document.getElementById('editProductId').value = product.product_id;
  document.getElementById('editProductName').value = product.product_name;
  updateEditCharCount(document.getElementById('editProductName'));
  $("#editCategory").val(product.category_id);
  $("#editPrice").val(product.price);
  $("#editCurrentQuantity").val(product.quantity);
  $("#editAdditionalQuantity").val("");
  // Set initial total to current quantity
  $("#newTotalQuantity")
    .text(product.quantity)
    .removeClass("text-green-600 text-red-600")
    .addClass("text-gray-600");
  $("#editProductModal").removeClass("hidden");
}

$(document).ready(function () {
  $("#editAdditionalQuantity").on("input", calculateNewTotal);

  function calculateNewTotal() {
    const currentQty = parseInt($("#editCurrentQuantity").val()) || 0;
    const additionalQty = parseInt($("#editAdditionalQuantity").val()) || 0;
    const $totalSpan = $("#newTotalQuantity");

    if (!$("#editAdditionalQuantity").val()) {
      $totalSpan
        .text(currentQty)
        .removeClass("text-green-600 text-red-600")
        .addClass("text-gray-600");
      return;
    }

    const newTotal = currentQty + additionalQty;

    if (additionalQty < 1) {
      $("#editAdditionalQuantity").val("");
      $totalSpan.text(currentQty);
      showError("Additional quantity must be at least 1");
      return;
    }

    if (newTotal > 999) {
      const maxAdd = 999 - currentQty;
      $("#editAdditionalQuantity").val(maxAdd);
      $totalSpan
        .text("999")
        .removeClass("text-gray-600 text-green-600")
        .addClass("text-red-600");
      showError("Total quantity cannot exceed 999");
      return;
    }

    $totalSpan
      .text(newTotal)
      .removeClass("text-gray-600 text-red-600")
      .addClass("text-green-600");
  }

  function showError(message) {
    Swal.fire({
      icon: "error",
      title: "Invalid Quantity",
      text: message,
      timer: 2000,
      showConfirmButton: false,
    });
  }
});

// Add this after document.ready
$(document).ready(function () {
  // Real-time quantity calculation
  $("#editAdditionalQuantity").on("input", function () {
    const currentQty = parseInt($("#editCurrentQuantity").val()) || 0;
    const additionalQty = parseInt($(this).val()) || 0;

    $.ajax({
      url: "../endpoint/calculate_total.php",
      method: "POST",
      data: {
        current_quantity: currentQty,
        additional_quantity: additionalQty,
      },
      success: function (response) {
        if (response.success) {
          const newTotal = response.total;
          const $totalSpan = $("#newTotalQuantity");

          // Clear display if input is empty
          if (!additionalQty) {
            $totalSpan
              .text(currentQty)
              .removeClass("text-green-600 text-red-600")
              .addClass("text-gray-600");
            return;
          }

          // Validate total
          if (newTotal > 999) {
            const maxAdd = 999 - currentQty;
            $("#editAdditionalQuantity").val(maxAdd);
            $totalSpan
              .text("999")
              .removeClass("text-gray-600 text-green-600")
              .addClass("text-red-600");
            showError("Total quantity cannot exceed 999");
          } else {
            $totalSpan
              .text(newTotal)
              .removeClass("text-gray-600 text-red-600")
              .addClass("text-green-600");
          }
        }
      },
      error: function () {
        showError("Error calculating total");
      },
    });
  });

  function showError(message) {
    Swal.fire({
      icon: "error",
      title: "Invalid Quantity",
      text: message,
      timer: 2000,
      showConfirmButton: false,
    });
  }
});

// Add this after DOMContentLoaded event listener
$(document).ready(function () {
  // Real-time quantity update
  $("#editAdditionalQuantity").on("input", function () {
    const currentQty = parseInt($("#editCurrentQuantity").val()) || 0;
    const additionalQty = parseInt($(this).val()) || 0;

    $.ajax({
      url: "../endpoint/calculate_total.php",
      method: "POST",
      data: {
        current_quantity: currentQty,
        additional_quantity: additionalQty,
      },
      success: function (response) {
        const $totalSpan = $("#newTotalQuantity");

        if (!additionalQty) {
          $totalSpan
            .text(currentQty)
            .removeClass("text-green-600 text-red-600")
            .addClass("text-gray-600");
          return;
        }

        if (additionalQty < 1) {
          $("#editAdditionalQuantity").val("");
          $totalSpan.text(currentQty);
          showSweetAlert("Additional quantity must be at least 1", "error");
          return;
        }

        const newTotal = currentQty + additionalQty;

        if (newTotal > 999) {
          const maxAdd = 999 - currentQty;
          $("#editAdditionalQuantity").val(maxAdd);
          $totalSpan
            .text("999")
            .removeClass("text-gray-600 text-green-600")
            .addClass("text-red-600");
          showSweetAlert("Total quantity cannot exceed 999", "error");
        } else {
          $totalSpan
            .text(newTotal)
            .removeClass("text-gray-600 text-red-600")
            .addClass("text-green-600");
        }
      },
      error: function () {
        showSweetAlert("Error calculating total", "error");
      },
    });
  });

  function showSweetAlert(message, icon) {
    Swal.fire({
      icon: icon,
      title: "Quantity Update",
      text: message,
      timer: 2000,
      showConfirmButton: false,
    });
  }
});

// Update openEditModal function
function openEditModal(product) {
  document.getElementById('editProductId').value = product.product_id;
  document.getElementById('editProductName').value = product.product_name;
  updateEditCharCount(document.getElementById('editProductName'));
  // ...existing code...
  $("#editCurrentQuantity").val(product.quantity);
  $("#editAdditionalQuantity").val("");
  $("#newTotalQuantity")
    .text(product.quantity)
    .removeClass("text-green-600 text-red-600")
    .addClass("text-gray-600");
  $("#editProductModal").removeClass("hidden");
}

$(document).ready(function () {
  // Real-time quantity calculation
  function updateTotalQuantity(currentQty) {
    $("#editAdditionalQuantity")
      .off("input")
      .on("input", function () {
        const additionalQty = parseInt($(this).val()) || 0;
        const newTotal = currentQty + additionalQty;
        const $totalSpan = $("#newTotalQuantity");

        if (!$(this).val()) {
          $totalSpan
            .text(currentQty)
            .removeClass("text-green-600 text-red-600")
            .addClass("text-gray-600");
          return;
        }

        if (newTotal > 999) {
          $(this).val(999 - currentQty);
          $totalSpan.text("999").addClass("text-red-600");
          showError("Total quantity cannot exceed 999");
        } else {
          $totalSpan
            .text(newTotal)
            .removeClass("text-red-600")
            .addClass("text-green-600");
        }
      });
  }

  // Form submission
  $("#editProductForm").on("submit", function (e) {
    e.preventDefault();
    $.ajax({
      url: "../endpoint/update-product.php",
      method: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function (response) {
        if (response.success) {
          Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.message,
          }).then(() => location.reload());
        } else {
          showError(response.message);
        }
      },
      error: function () {
        showError("Network error occurred");
      },
    });
  });

  // Open modal function
  window.openEditModal = function (product) {
    $("#editProductId").val(product.product_id);
    $("#editProductName").val(product.product_name);
    updateEditCharCount(document.getElementById('editProductName'));
    $("#editCategory").val(product.category_id);
    $("#editPrice").val(product.price);
    $("#editCurrentQuantity").val(product.quantity);
    $("#editAdditionalQuantity").val("");
    $("#newTotalQuantity")
      .text(product.quantity)
      .removeClass("text-green-600 text-red-600")
      .addClass("text-gray-600");

    updateTotalQuantity(parseInt(product.quantity));
    $("#editProductModal").removeClass("hidden");
  };

  function showError(message) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: message,
      timer: 2000,
      showConfirmButton: false,
    });
  }
});

$(document).ready(function () {
  // Real-time quantity validation and calculation
  $("#editAdditionalQuantity").on("input", function () {
    // Get values
    const currentQty = parseInt($("#editCurrentQuantity").val()) || 0;
    const additionalQty = parseInt($(this).val()) || 0;
    const $totalSpan = $("#newTotalQuantity");

    // Clear if empty input
    if (!$(this).val()) {
      $totalSpan
        .text(currentQty)
        .removeClass("text-green-600 text-red-600")
        .addClass("text-gray-600");
      return;
    }

    // Validate input
    if (additionalQty < 1) {
      $(this).val("");
      $totalSpan
        .text(currentQty)
        .removeClass("text-green-600 text-red-600")
        .addClass("text-gray-600");
      showError("Additional quantity must be at least 1");
      return;
    }

    // Calculate new total
    const newTotal = currentQty + additionalQty;

    // Validate total
    if (newTotal > 999) {
      const maxAddition = 999 - currentQty;
      $(this).val(maxAddition);
      $totalSpan
        .text("999")
        .removeClass("text-gray-600 text-green-600")
        .addClass("text-red-600");
      showError("Total quantity cannot exceed 999");
      return;
    }

    // Update display with valid total
    $totalSpan
      .text(newTotal)
      .removeClass("text-gray-600 text-red-600")
      .addClass("text-green-600");
  });
});

function showError(message) {
  Swal.fire({
    icon: "error",
    title: "Invalid Quantity",
    text: message,
    timer: 2000,
    showConfirmButton: false,
  });
}

// Replace all existing quantity-related event listeners with this single implementation
$(document).ready(function () {
  // Single event listener for real-time calculation
  $("#editAdditionalQuantity").on("input", function () {
    const currentQty = parseInt($("#editCurrentQuantity").val()) || 0;
    const additionalQty = parseInt($(this).val()) || 0;
    const $totalSpan = $("#newTotalQuantity");

    // If input is empty, show current quantity
    if (!$(this).val()) {
      $totalSpan
        .text(currentQty)
        .removeClass("text-green-600 text-red-600")
        .addClass("text-gray-600");
      return;
    }

    // Calculate new total
    const newTotal = currentQty + additionalQty;

    // Validate and update display
    if (additionalQty < 1) {
      $(this).val("");
      $totalSpan
        .text(currentQty)
        .removeClass("text-green-600 text-red-600")
        .addClass("text-gray-600");
      showError("Additional quantity must be at least 1");
      return;
    }

    if (newTotal > 999) {
      const maxAdd = 999 - currentQty;
      $(this).val(maxAdd);
      $totalSpan
        .text("999")
        .removeClass("text-gray-600 text-green-600")
        .addClass("text-red-600");
      showError("Total quantity cannot exceed 999");
    } else {
      $totalSpan
        .text(newTotal)
        .removeClass("text-gray-600 text-red-600")
        .addClass("text-green-600");
    }
  });
});

// Keep this error function
function showError(message) {
  Swal.fire({
    icon: "error",
    title: "Invalid Quantity",
    text: message,
    timer: 2000,
    showConfirmButton: false,
  });
}

// Update modal open function
function openEditModal(product) {
  document.getElementById('editProductId').value = product.product_id;
  document.getElementById('editProductName').value = product.product_name;
  updateEditCharCount(document.getElementById('editProductName'));
  $("#editCategory").val(product.category_id);
  $("#editPrice").val(product.price);
  $("#editCurrentQuantity").val(product.quantity);
  $("#editAdditionalQuantity").val("");
  $("#newTotalQuantity")
    .text(product.quantity)
    .removeClass("text-green-600 text-red-600")
    .addClass("text-gray-600");
  $("#editProductModal").removeClass("hidden");
}

$(document).ready(function () {
  // Real-time calculation with AJAX
  $("#editAdditionalQuantity").on("keyup input", function () {
    const currentQty = parseInt($("#editCurrentQuantity").val()) || 0;
    const additionalQty = parseInt($(this).val()) || 0;
    const $totalSpan = $("#newTotalQuantity");

    // Show loading state
    $totalSpan.html('<i class="fas fa-spinner fa-spin"></i>');

    $.ajax({
      url: "../endpoint/calculate_total.php",
      method: "POST",
      dataType: "json",
      data: {
        current_quantity: currentQty,
        additional_quantity: additionalQty,
      },
      success: function (response) {
        if (!additionalQty) {
          $totalSpan
            .text(currentQty)
            .removeClass("text-green-600 text-red-600")
            .addClass("text-gray-600");
          return;
        }

        if (response.success) {
          $totalSpan
            .text(response.total)
            .removeClass("text-gray-600 text-red-600")
            .addClass("text-green-600");
        } else {
          $totalSpan
            .text("Error")
            .removeClass("text-gray-600 text-green-600")
            .addClass("text-red-600");
          showError(response.error || "Invalid quantity");
        }
      },
      error: function () {
        $totalSpan
          .text("Error")
          .removeClass("text-gray-600 text-green-600")
          .addClass("text-red-600");
        showError("Failed to calculate total");
      },
    });
  });
});

$(document).ready(function () {
  $("#editAdditionalQuantity").on("input", function () {
    // Get input value as string to check length
    let inputValue = $(this).val();

    // Check for more than 3 digits
    if (inputValue.length > 3) {
      $(this).val(inputValue.slice(0, 3));
      showError("Maximum 3 digits allowed");
      return;
    }

    const currentQty = parseInt($("#editCurrentQuantity").val()) || 0;
    const additionalQty = parseInt(inputValue) || 0;
    const newTotal = currentQty + additionalQty;

    if (additionalQty < 1) {
      $(this).val("");
      showError("Additional quantity must be at least 1");
      return;
    }

    if (newTotal > 999) {
      const maxAdd = 999 - currentQty;
      $(this).val(maxAdd);
      showError("Total quantity cannot exceed 999");
    }
  });

  function showError(message) {
    Swal.fire({
      icon: "error",
      title: "Invalid Quantity",
      text: message,
      timer: 2000,
      showConfirmButton: false,
    });
  }
});

$(document).ready(function () {
  // Remove previous event listeners
  $("#editAdditionalQuantity").off("input");

  // Add real-time calculation
  $("#editAdditionalQuantity").on("input", function () {
    const currentQty = parseInt($("#editCurrentQuantity").val()) || 0;
    const additionalQty = parseInt($(this).val()) || 0;
    const $totalSpan = $("#newTotalQuantity");

    // Show loading state
    $totalSpan.html('<i class="fas fa-spinner fa-spin"></i>');

    // Validate input length
    if ($(this).val().length > 3) {
      $(this).val($(this).val().slice(0, 3));
      return;
    }

    // If empty, show current quantity
    if (!$(this).val()) {
      $totalSpan
        .text(currentQty)
        .removeClass("text-green-600 text-red-600")
        .addClass("text-gray-600");
      return;
    }

    // AJAX call for calculation
    $.ajax({
      url: "../endpoint/calculate_total.php",
      method: "POST",
      data: {
        current_quantity: currentQty,
        additional_quantity: additionalQty,
      },
      success: function (response) {
        if (response.success) {
          $totalSpan
            .text(response.total)
            .removeClass("text-gray-600 text-red-600")
            .addClass("text-green-600");
        } else {
          $totalSpan
            .text(currentQty)
            .removeClass("text-green-600 text-gray-600")
            .addClass("text-red-600");
          showError(response.error);
        }
      },
      error: function () {
        $totalSpan
          .text("Error")
          .removeClass("text-green-600 text-gray-600")
          .addClass("text-red-600");
        showError("Failed to calculate total");
      },
    });
  });
});

// Remove all previous event listeners first
$(document).ready(function () {
  $("#editAdditionalQuantity").off();

  $(document).ready(function () {
    // Clear existing listeners
    $("#editAdditionalQuantity").off("input");
    $("#editProductForm").off("submit");

    // Real-time quantity calculation
    $("#editAdditionalQuantity").on("input", function () {
      const currentQuantity = parseInt($("#editCurrentQuantity").val()) || 0;
      const additionalQuantity = parseInt($(this).val()) || 0;
      const $totalSpan = $("#newTotalQuantity");

      // Validate empty input
      if (!$(this).val()) {
        $totalSpan
          .text(currentQuantity)
          .removeClass("text-green-600 text-red-600")
          .addClass("text-gray-600");
        return;
      }

      // Validate quantity limits
      if (additionalQuantity < 0 || additionalQuantity > 999) {
        $(this).val("");
        $totalSpan
          .text(currentQuantity)
          .removeClass("text-green-600 text-red-600")
          .addClass("text-gray-600");
        showError("Invalid quantity (1-999)");
        return;
      }

      // Calculate and display new total
      const newTotal = currentQuantity + additionalQuantity;
      if (newTotal > 999) {
        $(this).val(999 - currentQuantity);
        $totalSpan
          .text("999")
          .removeClass("text-gray-600 text-green-600")
          .addClass("text-red-600");
        showError("Total quantity cannot exceed 999");
      } else {
        $totalSpan
          .text(newTotal)
          .removeClass("text-gray-600 text-red-600")
          .addClass("text-green-600");
      }
    });

    // Form submission handler
    $("#editProductForm").on("submit", function (e) {
      e.preventDefault();

      $.ajax({
        url: "../endpoint/update-product.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (response) {
          if (response.success) {
            Swal.fire({
              icon: "success",
              title: "Success!",
              text: response.message,
              confirmButtonColor: "#3085d6",
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Update Failed",
              text: response.message,
              confirmButtonColor: "#d33",
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "An unexpected error occurred",
            confirmButtonColor: "#d33",
          });
        },
      });
    });

    function showError(message) {
      Swal.fire({
        icon: "error",
        title: "Invalid Quantity",
        text: message,
        timer: 2000,
        showConfirmButton: false,
      });
    }
  });
});

function showError(message) {
  Swal.fire({
    icon: "error",
    title: "Invalid Quantity",
    text: message,
    timer: 2000,
    showConfirmButton: false,
  });
}

function updateEditCharCount(input) {
    const maxLength = 25;
    const currentLength = input.value.length;
    document.getElementById('editCharCount').textContent = 
        `(${currentLength}/${maxLength})`;
    
    if (currentLength === maxLength) {
        Swal.fire({
            icon: 'warning',
            title: 'Character Limit Reached',
            text: 'Product name cannot exceed 25 characters',
            timer: 2000,
            showConfirmButton: false
        });
    }
}
