const loginCon = document.querySelector(".login-container");
const registrationCon = document.querySelector(".registration-container");
const registrationForm = document.querySelector(".registration-form");
const qrCodeContainer = document.querySelector(".qr-code-container");
let scanner;

registrationCon.style.display = "none";
qrCodeContainer.style.display = "none";

function showRegistrationForm() {
  registrationCon.style.display = "";
  loginCon.style.display = "none";
  scanner.stop();
}

function startScanner() {
  scanner = new Instascan.Scanner({
    video: document.getElementById("interactive"),
  });

  scanner.addListener("scan", function (content) {
    $("#detected-qr-code").val(content);
    scanner.stop();
    document.querySelector(".qr-detected-container").style.display = "";
    document.querySelector(".viewport").style.display = "none";
  });

  Instascan.Camera.getCameras()
    .then(function (cameras) {
      if (cameras.length > 0) {
        scanner.start(cameras[0]);
      } else {
        console.error("No cameras found.");
        alert("No cameras found.");
      }
    })
    .catch(function (err) {
      console.error("Camera access error:", err);
      alert("Camera access error: " + err);
    });
}
// Add these functions at the beginning of your file
function encryptData(data) {
  return btoa(data); // Base64 encoding
}

function decryptData(data) {
  return atob(data); // Base64 decoding
}
function generateRandomCode(length) {
  const characters =
    "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
  let randomString = "";

  for (let i = 0; i < length; i++) {
    const randomIndex = Math.floor(Math.random() * characters.length);
    randomString += characters.charAt(randomIndex);
  }

  return randomString;
}
// AES Encryption
function encryptData(data, secretKey) {
    return CryptoJS.AES.encrypt(data, secretKey).toString();
}

// AES Decryption
function decryptData(data, secretKey) {
    const bytes = CryptoJS.AES.decrypt(data, secretKey);
    return bytes.toString(CryptoJS.enc.Utf8);
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
    const secretKey = 'your-secret-key'; // Use a secure key
    const encryptedText = encryptData(text, secretKey); // Encrypt the random string
    $("#generatedCode").val(encryptedText);

    if (text === "") {
        alert("Please enter text to generate a QR code.");
        return;
    } else {
        const apiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(encryptedText)}`;

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

// Ensure the scanner starts after the page loads
document.addEventListener('DOMContentLoaded', startScanner);
