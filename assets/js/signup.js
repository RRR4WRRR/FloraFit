document.addEventListener("DOMContentLoaded", () => {

const signupForm = document.getElementById("signupForm");
const termsModal = document.getElementById("termsModal");
const openTerms  = document.getElementById("openTerms");
const closeTerms = document.getElementById("closeTerms");

// --------------------
// API URL builder
// --------------------
function buildApiUrl(endpoint) {
    return `http://localhost/florafit/${endpoint}`;
}

// --------------------
// Toggle password visibility
// --------------------
window.togglePassword = function(inputId, icon) {
    const input = document.getElementById(inputId);
    const iconElement = icon.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        iconElement.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        iconElement.classList.replace("fa-eye-slash", "fa-eye");
    }
};

// --------------------
// Modal open / close
// --------------------
openTerms.addEventListener("click", (e) => {
    e.preventDefault();
    termsModal.classList.add("show");
    document.body.style.overflow = "hidden";
});

closeTerms.addEventListener("click", () => {
    termsModal.classList.remove("show");
    document.body.style.overflow = "";
});

termsModal.addEventListener("click", (e) => {
    if (e.target === termsModal) {
        termsModal.classList.remove("show");
        document.body.style.overflow = "";
    }
});

document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && termsModal.classList.contains("show")) {
        termsModal.classList.remove("show");
        document.body.style.overflow = "";
    }
});

// --------------------
// Real-time password validation
// --------------------
const passwordInput = document.getElementById("signupPassword");

if (passwordInput) {
    passwordInput.addEventListener("input", () => {
        const password = passwordInput.value;

        const lengthReq    = document.getElementById("req-length");
        const uppercaseReq = document.getElementById("req-uppercase");
        const numberReq    = document.getElementById("req-number");

        lengthReq.classList.toggle("valid",   password.length >= 8);
        lengthReq.classList.toggle("invalid", password.length < 8);

        uppercaseReq.classList.toggle("valid",   /[A-Z]/.test(password));
        uppercaseReq.classList.toggle("invalid", !/[A-Z]/.test(password));

        numberReq.classList.toggle("valid",   /[0-9]/.test(password));
        numberReq.classList.toggle("invalid", !/[0-9]/.test(password));
    });
}

// --------------------
// Password requirement check
// --------------------
function validatePassword(password) {
    if (password.length < 8)     return "Password must be at least 8 characters long";
    if (!/[A-Z]/.test(password)) return "Password must contain at least 1 uppercase letter";
    if (!/[0-9]/.test(password)) return "Password must contain at least 1 number";
    return null;
}

// --------------------
// Signup Form Submit
// --------------------
signupForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const firstName       = document.getElementById("firstName").value.trim();
    const lastName        = document.getElementById("lastName").value.trim();
    const email           = document.getElementById("signupEmail").value.trim();
    const password        = document.getElementById("signupPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const agreeTerms      = document.getElementById("agreeTerms").checked;

    if (!agreeTerms) {
        showNotification("Please agree to the Terms and Conditions to continue.", "error");
        return;
    }

    const passwordError = validatePassword(password);
    if (passwordError) {
        showNotification(passwordError, "error");
        return;
    }

    if (password !== confirmPassword) {
        showNotification("Passwords do not match!", "error");
        return;
    }

    const submitBtn = signupForm.querySelector("button[type='submit']");
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    submitBtn.disabled  = true;

    try {
        const response = await fetch(buildApiUrl("signup.php"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ firstName, lastName, email, password })
        });

        const data = await response.json();

        if (data.success) {
            localStorage.setItem("pendingVerification", JSON.stringify({
                email, firstName, lastName
            }));

            showNotification("Account created! Check your email for the verification code 🌸", "success");

            setTimeout(() => {
                window.location.href = "http://localhost/florafit/verify_email.html";
            }, 1500);

        } else {
            showNotification(data.message || "Signup failed.", "error");
        }

    } catch (error) {
        console.error("Signup error:", error);
        showNotification("Could not connect to the server. Make sure XAMPP Apache is running.", "error");

    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled  = false;
    }
});

}); // end DOMContentLoaded