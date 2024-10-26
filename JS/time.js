 // Function to update the date and time every second
 function updateDateTime() {
    const now = new Date(); // Get the current date and time
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric', 
        hour: 'numeric', 
        minute: 'numeric', 
        second: 'numeric', 
        hour12: true 
    };
    
    // Update the content of the span element
    document.getElementById('datetime').innerText = now.toLocaleString('en-US', options);
}

// Call updateDateTime every second
setInterval(updateDateTime, 1000);