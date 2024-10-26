// Ensure page refresh on navigation
window.onpageshow = function(event) {
if (event.persisted) {
    window.location.reload();
}
};

function setActive(link) {
    // Check if the clicked link is a sidebar link
    if (!link.classList.contains('sidebar-link')) return;

    // Get all sidebar links
    const links = document.querySelectorAll('.sidebar-link');

    // Remove active class from all links except the current one
    links.forEach((item) => {
        if (item !== link) {
            item.classList.remove('active');
            item.classList.remove('bg-warning'); // Remove custom active background
            item.classList.remove('text-dark'); // Restore original text color
            item.style.pointerEvents = 'auto'; // Re-enable all links
        }
    });

    // Toggle active class for the clicked link
    if (link.classList.contains('active')) {
        // If already active, remove active class
        link.classList.remove('active');
        link.classList.remove('bg-warning', 'text-dark'); // Restore original style

        // Collapse the corresponding section
        const collapseSection = link.getAttribute('href');
        const collapseElement = document.querySelector(collapseSection);
        if (collapseElement) {
            collapseElement.classList.remove('show'); // Collapse
        }
    } else {
        // If not active, add active class
        link.classList.add('active'); // Add active class
        link.classList.add('bg-warning', 'text-dark'); // Change to yellow background and dark text

        // Expand the corresponding section
        const collapseSection = link.getAttribute('href');
        const collapseElement = document.querySelector(collapseSection);
        if (collapseElement) {
            collapseElement.classList.add('show'); // Expand
        }
    }
}
