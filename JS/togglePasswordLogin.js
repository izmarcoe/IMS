function togglePasswordLogin() {
    const passwordLoginForm = document.getElementById('passwordLoginForm');
    const qrCodeScanner = document.getElementById('interactive'); // Assuming this is the ID of your QR code video element
    
    // Toggle visibility of password login form
    if (passwordLoginForm.style.display === "none" || passwordLoginForm.style.display === "") {
        passwordLoginForm.style.display = "block";
        qrCodeScanner.style.display = "none"; // Hide QR code scanner
    } else {
        passwordLoginForm.style.display = "none";
        qrCodeScanner.style.display = "block"; // Show QR code scanner
    }
}