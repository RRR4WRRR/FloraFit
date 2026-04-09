// =====================================
// FloraFit Verification
// =====================================

document.addEventListener("DOMContentLoaded", function() {
    setupVerification();
});

function setupVerification() {
    // Get pending data from signup
    const pending = JSON.parse(localStorage.getItem("pendingVerification") || "{}");
    
    if (!pending.email) {
        alert("No signup session. Go back to signup.");
        window.location.href = "signup.html";
        return;
    }

    // Lock email field
    const emailField = document.getElementById("verifyEmail");
    if (emailField) {
        emailField.value = pending.email;
        emailField.readOnly = true;
        emailField.style.background = "#f0f8f0";
    }

    // Focus code input
    const codeField = document.getElementById("verifyCode");
    if (codeField) codeField.focus();

    console.log("Verifying for:", pending.email);
}

// Form submit
document.getElementById("verifyForm")?.addEventListener("submit", async function(e) {
    e.preventDefault();
    
    const email = document.getElementById("verifyEmail").value;
    const code = document.getElementById("verifyCode").value.replace(/\s/g, "");
    
    if (code.length !== 6 || !/^\d{6}$/.test(code)) {
        alert("Enter 6-digit code");
        return;
    }

    const submitBtn = document.querySelector("button[type=submit]");
    submitBtn.disabled = true;
    submitBtn.textContent = "Verifying...";

    try {
        const response = await fetch("api/verify_email.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, code })
        });

        const data = await response.json();

        if (data.success) {
            // Save login session
            localStorage.setItem("isLoggedIn", "true");
            localStorage.setItem("user", JSON.stringify({
                id: data.user_id || null,
                email: pending.email,
                firstName: pending.firstName || "",
                lastName: pending.lastName || "",
                name: [pending.firstName, pending.lastName].filter(Boolean).join(' '),
                verified: true
            }));
            
            // Cleanup
            localStorage.removeItem("pendingVerification");
            
            alert("✅ Verified! Redirecting...");
            setTimeout(() => {
                window.location.href = "index.html"; // or index.html
            }, 1000);
            
        } else {
            alert(data.message || "Wrong code");
            document.getElementById("verifyCode").value = "";
            document.getElementById("verifyCode").focus();
        }

    } catch (error) {
        console.error("Verify error:", error);
        alert("Connection error. Try again.");
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = "Verify Account";
    }
});

// Auto-submit 6 digits
document.getElementById("verifyCode")?.addEventListener("input", function(e) {
    let code = e.target.value.replace(/\s/g, "");
    if (code.length === 6) {
        document.getElementById("verifyForm").dispatchEvent(new Event("submit"));
    }
});

// Resend code (if you have resend_code.php)
window.resendCode = async function() {
    const email = document.getElementById("verifyEmail").value;
    const resendBtn = document.querySelector(".resend-btn");
    
    if (!email) return alert("No email found");
    
    resendBtn.disabled = true;
    resendBtn.textContent = "Sending...";
    
    try {
        const response = await fetch("api/resend_code.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email })
        });
        
        const data = await response.json();
        if (data.success) {
            alert("New code sent!");
            document.getElementById("verifyCode").value = "";
            document.getElementById("verifyCode").focus();
        } else {
            alert(data.message || "Resend failed");
        }
    } catch {
        alert("Resend failed");
    } finally {
        resendBtn.disabled = false;
        resendBtn.textContent = "Resend Code";
    }
};