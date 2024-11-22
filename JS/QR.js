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
  if (scanner) {
    scanner.stop();
  }
}
document.addEventListener("DOMContentLoaded", function () {
  const videoElement = document.getElementById("interactive");
  const qrDetectedContainer = document.querySelector(".qr-detected-container");
  const qrInput = document.getElementById("detected-qr-code");

  // Initialize Instascan scanner
  let scanner = new Instascan.Scanner({ video: videoElement });
  
  // On successful scan, handle the QR code
  scanner.addListener("scan", function (content) {
      console.log("QR Code Scanned: ", content);
      qrInput.value = content; // Set the hidden input value
      qrDetectedContainer.style.display = "block"; // Show detected container
  });

  // Find available cameras
  Instascan.Camera.getCameras().then(function (cameras) {
      if (cameras.length > 0) {
          // Use the first camera by default
          scanner.start(cameras[0]);
      } else {
          console.error("No cameras found.");
      }
  }).catch(function (error) {
      console.error("Error accessing cameras: ", error);
  });
});


function encryptData(data, secretKey) {
  return CryptoJS.AES.encrypt(data, secretKey).toString();
}

function decryptData(data, secretKey) {
  const bytes = CryptoJS.AES.decrypt(data, secretKey);
  return bytes.toString(CryptoJS.enc.Utf8);
}

function generateRandomCode(length) {
  const characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
  let randomString = "";

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
  const qrImg = document.getElementById('qrImg');
  const qrBox = document.getElementById('qrBox');

  registrationInputs.style.display = 'none';

  let text = generateRandomCode(10);
  const secretKey = 'your-secret-key'; // Use a secure key
  const encryptedText = encryptData(text, secretKey); // Encrypt the random string
  document.getElementById("generatedCode").value = encryptedText;

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