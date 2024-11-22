function showQRCodeLogin() {
    const passwordLoginForm = document.getElementById('passwordLoginForm');
    const qrCodeScanner = document.getElementById('interactive');
    const qrCodeLoginLink = document.getElementById('qrCodeLoginLink');
    const passwordLoginLink = document.getElementById('passwordLoginLink');

    // Show QR code scanner and hide password login form
    qrCodeScanner.style.display = "block";
    passwordLoginForm.style.display = "none";
    // Hide QR code login link and show password login link
    qrCodeLoginLink.style.display = "none";
    passwordLoginLink.style.display = "block";
}

function showPasswordLogin() {
    const passwordLoginForm = document.getElementById('passwordLoginForm');
    const qrCodeScanner = document.getElementById('interactive');
    const qrCodeLoginLink = document.getElementById('qrCodeLoginLink');
    const passwordLoginLink = document.getElementById('passwordLoginLink');

    // Show password login form and hide QR code scanner
    passwordLoginForm.style.display = "block";
    qrCodeScanner.style.display = "none";
    // Show QR code login link and hide password login link
    qrCodeLoginLink.style.display = "block";
    passwordLoginLink.style.display = "none";
}

// Event listeners for the links
document.getElementById('qrCodeLoginLink').addEventListener('click', showQRCodeLogin);
document.getElementById('passwordLoginLink').addEventListener('click', showPasswordLogin);