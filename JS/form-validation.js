function validateName(name) {
    // Only allow letters, no spaces, numbers or special characters
    const nameRegex = /^[a-zA-Z]+$/;
    name = name.trim(); // Remove any whitespace
    return nameRegex.test(name) && name.length <= 25;
}

function validatePassword(password) {
    const minLength = 8;
    const hasNumber = /\d/.test(password);
    const hasUpper = /[A-Z]/.test(password);
    const hasLower = /[a-z]/.test(password);
    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    
    return password.length >= minLength && hasNumber && hasUpper && hasLower && hasSpecial;
}

async function checkEmailExists(email) {
    try {
        const response = await fetch('../endpoint/check-email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `email=${encodeURIComponent(email)}`
        });
        const data = await response.json();
        return data.exists;
    } catch (error) {
        console.error('Error checking email:', error);
        return false;
    }
}

function validateContactNumber(number) {
    // Ensure exactly 11 digits for Philippine numbers
    const phoneRegex = /^(09|\+639)\d{9}$/;
    return phoneRegex.test(number);
}

document.getElementById('contactNumber').addEventListener('input', function() {
    if (this.value.length > 11) {
        this.value = this.value.slice(0, 11);
    }
});

function validateForm() {
    const firstName = document.getElementById('fname').value.trim();  // Changed from firstName
    const lastName = document.getElementById('lname').value.trim();   // Changed from lastName
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const contactNumber = document.getElementById('contactNumber').value.trim();

    let isValid = true;
    let errorMessage = '';

    // Clear previous error messages
    document.querySelectorAll('.error-message').forEach(el => el.remove());

    // Name validations
    if (!validateName(firstName)) {
        showError('fname', 'First name must contain only letters (no spaces or special characters)');  // Changed from firstName
        isValid = false;
    }

    if (!validateName(lastName)) {
        showError('lname', 'Last name must contain only letters (no spaces or special characters)');  // Changed from lastName
        isValid = false;
    }

    // Contact number validation
    if (!validateContactNumber(contactNumber)) {
        showError('contactNumber', 'Please enter a valid 11-digit phone number starting with 09');
        isValid = false;
    }

    // Password validation
    if (!validatePassword(password)) {
        isValid = false;
    }

    // Password match validation
    if (password !== confirmPassword) {
        showError('confirmPassword', 'Passwords do not match');
        isValid = false;
    }

    // Email validation
    if (email) {
        checkEmailExists(email).then(exists => {
            if (exists) {
                showError('email', 'This email is already registered');
                isValid = false;
            }
            registerButton.disabled = !isValid;
        });
    }

    // Add terms checkbox validation
    const termsCheckbox = document.getElementById('termsCheckbox');
    if (!termsCheckbox.checked) {
        isValid = false;
    }

    // Update register button state based on both form validation and terms
    const registerButton = document.getElementById('registerButton');
    registerButton.disabled = !isValid || !termsCheckbox.checked;

    return isValid;
}

function showError(inputId, message) {
    const input = document.getElementById(inputId);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message text-red-500 text-sm mt-1';
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
}

function setupPasswordValidation() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    
    // Initially disable confirm password
    confirmPasswordInput.disabled = true;
    confirmPasswordInput.classList.add('bg-gray-100');

    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };
        
        const isValid = Object.values(requirements).every(req => req);
        
        // Add or remove border classes
        if (password.length > 0) {
            if (isValid) {
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
                confirmPasswordInput.disabled = false;
                confirmPasswordInput.classList.remove('bg-gray-100');
            } else {
                this.classList.remove('border-green-500');
                this.classList.add('border-red-500');
                confirmPasswordInput.disabled = true;
                confirmPasswordInput.classList.add('bg-gray-100');
                confirmPasswordInput.value = '';
            }
            
            // Update requirements message
            let existingReq = document.getElementById('password-requirements');
            if (!isValid) {
                const missingReqs = [];
                if (!requirements.length) missingReqs.push('at least 8 characters');
                if (!requirements.uppercase) missingReqs.push('1 uppercase letter');
                if (!requirements.lowercase) missingReqs.push('1 lowercase letter');
                if (!requirements.number) missingReqs.push('1 number');
                if (!requirements.special) missingReqs.push('1 special character');
                
                if (!existingReq) {
                    const reqDiv = document.createElement('div');
                    reqDiv.id = 'password-requirements';
                    reqDiv.className = 'text-red-500 text-xs mt-1';
                    reqDiv.textContent = `Missing: ${missingReqs.join(', ')}`;
                    this.parentNode.appendChild(reqDiv);
                } else {
                    existingReq.textContent = `Missing: ${missingReqs.join(', ')}`;
                }
            } else if (existingReq) {
                existingReq.remove();
            }
        } else {
            this.classList.remove('border-red-500', 'border-green-500');
            const existingReq = document.getElementById('password-requirements');
            if (existingReq) existingReq.remove();
        }
    });
}

// Update event listeners
document.addEventListener('DOMContentLoaded', function() {
    const registerButton = document.getElementById('registerButton');
    const formInputs = document.querySelectorAll('#registrationForm input[required]');
    const termsCheckbox = document.getElementById('termsCheckbox');

    setupPasswordValidation();

    // Disable register button initially
    registerButton.disabled = true;

    formInputs.forEach(input => {
        input.addEventListener('input', validateForm);
    });

    // Add terms checkbox listener
    termsCheckbox.addEventListener('change', function() {
        if (this.checked) {
            validateForm(); // Revalidate form when terms are checked
        } else {
            registerButton.disabled = true;
        }
    });

    // Add form submission handler
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        if (!termsCheckbox.checked) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Terms & Conditions Required',
                text: 'Please agree to the terms and conditions before proceeding.',
                confirmButtonColor: '#047857'
            });
        }
    });
});
