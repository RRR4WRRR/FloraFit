// -----------------------------
// FloraFit Forgot Password System
// -----------------------------

const LOCAL_APP_BASE = "http://localhost/FloraFit";

function buildEndpointCandidates(paths) {
    const absolute = paths.map(path => `${LOCAL_APP_BASE}/${path}`);
    const relative = paths;

    if (window.location.origin === "null" || !/localhost|127\.0\.0\.1/i.test(window.location.hostname)) {
        return [...absolute, ...relative];
    }

    return [...relative, ...absolute];
}

const SEND_CODE_ENDPOINTS = buildEndpointCandidates([
    "forgot_password.php",
    "send_reset_code.php"
]);

const RESET_PASSWORD_ENDPOINTS = buildEndpointCandidates([
    "reset_password.php"
]);

// -----------------------------
// Toggle password visibility
// -----------------------------
function togglePassword(inputId, iconElement) {
    const input = document.getElementById(inputId);
    const icon = iconElement?.querySelector("i");
    if (!input || !icon) return;

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// -----------------------------
// Password validation
// -----------------------------
function validatePassword(password) {
    if (password.length < 8) return "Password must be at least 8 characters long";
    if (!/[A-Z]/.test(password)) return "Password must contain at least 1 uppercase letter";
    if (!/[0-9]/.test(password)) return "Password must contain at least 1 number";
    return null;
}

// -----------------------------
// Update password requirement UI
// -----------------------------
function updatePasswordRequirements(password) {
    const lengthReq = document.getElementById("req-length");
    const upperReq = document.getElementById("req-uppercase");
    const numberReq = document.getElementById("req-number");

    if (lengthReq) lengthReq.classList.toggle("valid", password.length >= 8);
    if (upperReq) upperReq.classList.toggle("valid", /[A-Z]/.test(password));
    if (numberReq) numberReq.classList.toggle("valid", /[0-9]/.test(password));
}

function getOtpBoxes() {
    return Array.from(document.querySelectorAll(".otp-box"));
}

function syncResetCodeFromBoxes() {
    const codeInput = document.getElementById("resetCode");
    if (!codeInput) return;

    const code = getOtpBoxes().map(box => box.value).join("");
    codeInput.value = code;
}

function fillOtpBoxes(code = "") {
    const digits = String(code).replace(/\D/g, "").slice(0, 6).split("");
    const boxes = getOtpBoxes();

    boxes.forEach((box, index) => {
        box.value = digits[index] || "";
    });

    syncResetCodeFromBoxes();
}

function setupOtpBoxes() {
    const boxes = getOtpBoxes();
    if (!boxes.length) return;

    boxes.forEach((box, index) => {
        box.addEventListener("input", e => {
            const digit = e.target.value.replace(/\D/g, "").slice(0, 1);
            e.target.value = digit;
            syncResetCodeFromBoxes();

            if (digit && index < boxes.length - 1) {
                boxes[index + 1].focus();
            }
        });

        box.addEventListener("keydown", e => {
            if (e.key === "Backspace" && !box.value && index > 0) {
                boxes[index - 1].focus();
            }
        });

        box.addEventListener("paste", e => {
            e.preventDefault();
            const pasted = (e.clipboardData.getData("text") || "").replace(/\D/g, "").slice(0, 6);
            fillOtpBoxes(pasted);

            const focusIndex = Math.min(pasted.length, boxes.length - 1);
            boxes[focusIndex].focus();
        });
    });
}

async function postJsonWithFallback(urls, payload) {
    let lastError;

    for (const url of urls) {
        try {
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                lastError = new Error(`Request failed (${response.status}) at ${url}`);
                continue;
            }

            const text = await response.text();
            try {
                const data = JSON.parse(text);
                return { data, url };
            } catch {
                lastError = new Error(`Invalid JSON response from ${url}`);
            }
        } catch (err) {
            lastError = err;
        }
    }

    throw lastError || new Error("No available endpoint");
}

// -----------------------------
// Send verification code
// -----------------------------
async function sendVerificationCode() {
    const email = document.getElementById("forgotEmail")?.value.trim();
    if (!email) return showNotification("Please enter your email.", "error");

    try {
        const { data, url } = await postJsonWithFallback(SEND_CODE_ENDPOINTS, { email });
        console.log("Forgot Password Server Response from", url, data);

        if (data.success) {
            showNotification("Verification code sent to your email.", "success");
            const resetForm = document.getElementById("afterSend");
            if (resetForm) {
                resetForm.style.display = "block";
            } else {
                console.error("Could not find #afterSend — check your HTML file has this div");
            }
            getOtpBoxes()[0]?.focus();

            // auto-fill debug code for local testing
            if (data.debug?.code) {
                fillOtpBoxes(data.debug.code);
            }
        } else {
            showNotification(data.message || "Failed to send code.", "error");
        }

    } catch (err) {
        console.error(err);
        if (window.location.protocol === "file:") {
            showNotification("Open this page using XAMPP URL (http://localhost/FloraFit/forgot.html), not as a local file.", "error");
            return;
        }
        showNotification("Could not connect to the server. Make sure XAMPP Apache is running and localhost is accessible.", "error");
    }
}

// -----------------------------
// Reset password
// -----------------------------
async function resetPassword() {
    const email = document.getElementById("forgotEmail")?.value.trim();
    const code = document.getElementById("resetCode")?.value.trim();
    const newPassword = document.getElementById("newPassword")?.value;
    const confirm = document.getElementById("confirmNewPassword")?.value;

    if (!email || !code) return showNotification("Missing email or verification code.", "error");

    const error = validatePassword(newPassword);
    if (error) return showNotification(error, "error");
    if (newPassword !== confirm) return showNotification("Passwords do not match.", "error");

    try {
        const { data, url } = await postJsonWithFallback(RESET_PASSWORD_ENDPOINTS, {
            email,
            code,
            new_password: newPassword
        });
        console.log("Reset Password Server Response from", url, data);

        if (data.success) {
            showNotification("Password reset successful. Redirecting to login...", "success");
            setTimeout(() => window.location.href = "login.html", 1500);
        } else {
            showNotification(data.message || "Password reset failed.", "error");
        }

    } catch (err) {
        console.error(err);
        showNotification("Could not connect to reset endpoint. Open via http://localhost/FloraFit/forgot.html and ensure Apache is running.", "error");
    }
}

// -----------------------------
// Event listeners
// -----------------------------
document.getElementById("forgotForm")?.addEventListener("submit", e => {
    e.preventDefault();
    sendVerificationCode();
});

document.getElementById("resetBtn")?.addEventListener("click", e => {
    e.preventDefault();
    resetPassword();
});

document.getElementById("clearBtn")?.addEventListener("click", () => {
    fillOtpBoxes("");
    const newPassword = document.getElementById("newPassword");
    const confirmPassword = document.getElementById("confirmNewPassword");
    if (newPassword) newPassword.value = "";
    if (confirmPassword) confirmPassword.value = "";
    updatePasswordRequirements("");
    getOtpBoxes()[0]?.focus();
});

document.getElementById("newPassword")?.addEventListener("input", e => {
    updatePasswordRequirements(e.target.value);
});

setupOtpBoxes();