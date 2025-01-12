document.addEventListener("DOMContentLoaded", function () {
  console.log("Loading notifications system...");
  const notificationBtn = document.getElementById("notificationBtn");

  if (notificationBtn) {
    notificationBtn.addEventListener("click", function () {
      console.log("Notification button clicked");
      const dropdown = document.getElementById("notificationDropdown");
      dropdown.classList.toggle("hidden");

      if (!dropdown.classList.contains("hidden")) {
        loadPendingRequests();
      }
    });
  }
});

function loadPendingRequests() {
  console.log("Fetching pending requests...");
  fetch("../endpoint/get_pending_requests.php")
    .then((response) => response.json())
    .then((data) => {
      console.log("Received data:", data);
      const container = document.getElementById("notificationContent");

      if (data.requests && data.requests.length > 0) {
        container.innerHTML = data.requests
          .map(
            (request) => `
                    <div class="p-4 border-b hover:bg-gray-50">
                        <p class="font-medium">Product: ${request.old_name}</p>
                        <p class="text-sm text-gray-600">Requested by: ${
                          request.Fname
                        } ${request.Lname}</p>
                        <div class="mt-2">
                            <p class="text-sm font-medium">Changes:</p>
                            ${
                              request.new_name !== request.old_name
                                ? `<p class="text-sm">Name: ${request.new_name}</p>`
                                : ""
                            }
                            ${
                              request.new_price !== request.old_price
                                ? `<p class="text-sm">Price: ₱${request.new_price}</p>`
                                : ""
                            }
                            ${
                              request.new_quantity !== request.old_quantity
                                ? `<p class="text-sm">Quantity: ${request.new_quantity}</p>`
                                : ""
                            }
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button onclick="handleRequest(${
                              request.request_id
                            }, 'approve')"
                                    class="px-3 py-1 bg-green-500 text-white rounded-md text-sm hover:bg-green-600">
                                Approve
                            </button>
                            <button onclick="handleRequest(${
                              request.request_id
                            }, 'decline')"
                                    class="px-3 py-1 bg-red-500 text-white rounded-md text-sm hover:bg-red-600">
                                Decline
                            </button>
                        </div>
                    </div>
                `
          )
          .join("");
      } else {
        container.innerHTML =
          '<div class="p-4 text-center text-gray-500">No pending requests</div>';
      }
    })
    .catch((error) => {
      console.error("Error loading requests:", error);
      document.getElementById("notificationContent").innerHTML =
        '<div class="p-4 text-center text-red-500">Error loading requests</div>';
    });
}

function handleRequest(requestId, action) {
  console.log("Handling request:", requestId, action);

  Swal.fire({
    title: `Confirm ${action}?`,
    text: `Are you sure you want to ${action} this request?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: action === "approve" ? "#4CAF50" : "#f44336",
    cancelButtonColor: "#9e9e9e",
    confirmButtonText:
      action === "approve" ? "Yes, approve it!" : "Yes, decline it!",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("../endpoint/handle_product_request.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `request_id=${requestId}&action=${action}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            Swal.fire({
              icon: "success",
              title: `Request ${action}d!`,
              text: `The modification request has been ${action}d successfully.`,
              timer: 1500,
              showConfirmButton: false,
            }).then(() => {
              // Refresh the notifications list
              loadPendingRequests();

              // Update notification count badge
              const badge = document.querySelector(
                "#notificationBtn .bg-red-500"
              );
              if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 1) {
                  badge.textContent = currentCount - 1;
                } else {
                  badge.remove();
                }
              }
            });
          } else {
            throw new Error(data.message || `Failed to ${action} request`);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire({
            icon: "error",
            title: "Error",
            text: error.message,
          });
        });
    }
  });
}

function createModificationRequest(product) {
  console.log('Creating request for product:', product);
  
  Swal.fire({
      title: 'Request Product Modification',
      html: `
          <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Product Name</label>
              <input type="text" id="newName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" 
                     value="${product.product_name}">
          </div>
          <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Price</label>
              <input type="number" id="newPrice" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" 
                     value="${product.price}" step="0.01" min="0">
          </div>
          <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Quantity</label>
              <input type="number" id="newQuantity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" 
                     value="${product.quantity}" min="0">
          </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Submit Request',
      showLoaderOnConfirm: true,
      preConfirm: () => {
          const formData = new FormData();
          formData.append('product_id', product.product_id);
          formData.append('new_name', document.getElementById('newName').value);
          formData.append('new_price', document.getElementById('newPrice').value);
          formData.append('new_quantity', document.getElementById('newQuantity').value);

          return fetch('../endpoint/create_product_request.php', {
              method: 'POST',
              body: formData
          })
          .then(response => {
              console.log('Raw response:', response);
              return response.json();
          })
          .then(data => {
              console.log('Response data:', data);
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
      console.error('Error:', error);
      Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message
      });
  });
}
