   // Function to set the active link
   function setActive(element) {
    // Remove active class from all sidebar links
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
    });
    // Add active class to the clicked link
    element.classList.add('active');
}

// Ensure the collapse state is reset when navigating to a new page
document.addEventListener('DOMContentLoaded', function () {
    var productsCollapse = new bootstrap.Collapse(document.getElementById('productsCollapse'), {
        toggle: false
    });
    var salesCollapse = new bootstrap.Collapse(document.getElementById('salesCollapse'), {
        toggle: false
    });

    // Reset collapse state when navigating to a new page
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', function () {
            productsCollapse.hide();
            salesCollapse.hide();
        });
    });
});