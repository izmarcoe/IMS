   // Hide the notification after 5 seconds
   setTimeout(function() {
    var notification = document.getElementById('notification');
    if (notification) {
        notification.style.transition = 'opacity 0.5s';
        notification.style.opacity = '0';
        setTimeout(() => notification.style.display = 'none', 500);
    }
}, 1500);