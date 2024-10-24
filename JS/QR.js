const loginCon = document.querySelector('.login-container');
const registrationCon = document.querySelector('.registration-container');
const registrationForm = document.querySelector('.registration-form');
const qrCodeContainer = document.querySelector('.qr-code-container');
let scanner;

registrationCon.style.display = "none";
qrCodeContainer.style.display = "none";

function showRegistrationForm() {
    registrationCon.style.display = "";
    loginCon.style.display = "none";
    scanner.stop();
}

function startScanner() {
    scanner = new Instascan.Scanner({ video: document.getElementById('interactive') });

    scanner.addListener('scan', function (content) {
        $("#detected-qr-code").val(content);
        scanner.stop();
        document.querySelector(".qr-detected-container").style.display = '';
        document.querySelector(".viewport").style.display = 'none';
    });

    Instascan.Camera.getCameras()
        .then(function (cameras) {
            if (cameras.length > 0) {
                scanner.start(cameras[0]);
            } else {
                console.error('No cameras found.');
                alert('No cameras found.');
            }
        })
        .catch(function (err) {
            console.error('Camera access error:', err);
            alert('Camera access error: ' + err);
        });
}

function generateRandomCode(length) {
    const characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    let randomString = '';

    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * characters.length);
        randomString += characters.charAt(randomIndex);
    }

    return randomString;
}

function generateQrCode() {
    const registrationInputs = document.querySelector('.hide-registration-inputs');
    const h2 = document.querySelector('.registration-form > h2');
    const p = document.querySelector('.registration-form > p');
    const inputs = document.querySelectorAll('.registration input');
    const qrImg = document.getElementById('qrImg');
    const qrBox = document.getElementById('qrBox');

    registrationInputs.style.display = 'none';

    let text = generateRandomCode(10);
    $("#generatedCode").val(text);

    if (text === "") {
        alert("Please enter text to generate a QR code.");
        return;
    } else {
        const apiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(text)}`;

        // Generating image
        qrImg.src = apiUrl;
        qrBox.setAttribute("id", "qrBoxGenerated");
        qrCodeContainer.style.display = "";
        registrationCon.style.display = "";
        h2.style.display = "none";
        p.style.display = "none";
    }
}

// Ensure the scanner starts after the page loads
document.addEventListener('DOMContentLoaded', startScanner);