 // Ensure page refresh on navigation
 window.onpageshow = function(event) {
    if (event.persisted) {
        window.location.reload();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // Restore the state of the collapsible sections and active link
    const reportCollapse = document.getElementById('reportCollapse');
    const productsCollapse = document.getElementById('productsCollapse');
    const salesCollapse = document.getElementById('salesCollapse');
    const activeLink = localStorage.getItem('activeLink');
    if (activeLink) {
        const link = document.querySelector(`a[href="${activeLink}"]`);
        if (link) {
            link.classList.add('active', 'text-dark');
            if (reportCollapse.contains(link)) {
                reportCollapse.classList.add('show');
            }
            if (productsCollapse.contains(link)) {
                productsCollapse.classList.add('show');
            }
            if (salesCollapse.contains(link)) {
                salesCollapse.classList.add('show');
            }
        }
    }

    // Add event listener to save the state of the collapsible sections
    reportCollapse.addEventListener('shown.bs.collapse', function() {
        localStorage.setItem('reportCollapse', 'true');
    });
    reportCollapse.addEventListener('hidden.bs.collapse', function() {
        localStorage.setItem('reportCollapse', 'false');
    });
    productsCollapse.addEventListener('shown.bs.collapse', function() {
        localStorage.setItem('productsCollapse', 'true');
    });
    productsCollapse.addEventListener('hidden.bs.collapse', function() {
        localStorage.setItem('productsCollapse', 'false');
    });
    salesCollapse.addEventListener('shown.bs.collapse', function() {
        localStorage.setItem('salesCollapse', 'true');
    });
    salesCollapse.addEventListener('hidden.bs.collapse', function() {
        localStorage.setItem('salesCollapse', 'false');
    });
});

function setActive(link, event) {
    // Prevent collapse from closing
    if (event) {
        event.stopPropagation();
    }

    // Check if the clicked link is a sidebar link
    if (!link.classList.contains('sidebar-link')) return;

    // Get all sidebar links
    const links = document.querySelectorAll('.sidebar-link');

    // Remove active class from all links except the current one
    links.forEach((item) => {
        if (item !== link) {
            item.classList.remove('active', 'text-dark');
        }
    });

    // Toggle active class for the clicked link
    if (!link.classList.contains('active')) {
        link.classList.add('active', 'text-dark');
    }

    // Save the active link to local storage
    localStorage.setItem('activeLink', link.getAttribute('href'));

    // Ensure the collapsible sections remain open if any of their children are active
    const reportCollapse = document.getElementById('reportCollapse');
    const productsCollapse = document.getElementById('productsCollapse');
    const salesCollapse = document.getElementById('salesCollapse');
    if (reportCollapse.contains(link)) {
        reportCollapse.classList.add('show');
        localStorage.setItem('reportCollapse', 'true');
    }
    if (productsCollapse.contains(link)) {
        productsCollapse.classList.add('show');
        localStorage.setItem('productsCollapse', 'true');
    }
    if (salesCollapse.contains(link)) {
        salesCollapse.classList.add('show');
        localStorage.setItem('salesCollapse', 'true');
    }
};