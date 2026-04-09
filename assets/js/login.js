const loginForm = document.getElementById("loginForm");

function buildApiUrl(endpoint) {
    return `/FloraFit/${endpoint}`;

}

function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    const iconElement = icon.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        iconElement.classList.remove('fa-eye');
        iconElement.classList.add('fa-eye-slash');
    } else {
        input.type = "password";
        iconElement.classList.remove('fa-eye-slash');
        iconElement.classList.add('fa-eye');
    }
}

loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email    = document.getElementById("loginEmail").value.trim();
    const password = document.getElementById("loginPassword").value;

    console.log("Attempting login with:", { email });

    try {
        const response = await fetch(buildApiUrl("api/login.php"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password })
        });

        console.log("Response status:", response.status);
        const text = await response.text();
        console.log("Response body:", text);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = JSON.parse(text);
        console.log("Parsed data:", data);

        if (data.success) {
            localStorage.setItem("isLoggedIn", "true");
            localStorage.setItem("user", JSON.stringify(data.user));

            if (data.user.role === 'admin') {
                localStorage.setItem("isAdmin", "true");
            } else {
                localStorage.removeItem("isAdmin");
            }

            showNotification("Login successful! 🌷", 'success');

            setTimeout(() => {
                const role         = data.user.role;
                const isFirstLogin = data.user.is_first_login;
                const token        = data.token;

                console.log("Redirecting - role:", role, "isFirstLogin:", isFirstLogin, "token:", token);

                if (role === 'admin') {
                    window.location.href = "admin/dashboard.php";
                } else if (role === 'florist' && isFirstLogin == 1 && token) {
                    window.location.href = "change_password.php?token=" + token;
                } else if (role === 'florist') {
                    window.location.href = "florist/dashboard.php";
                } else {
                    window.location.href = "index.html";
                }
            }, 1000);

        } else {
            showNotification(data.message, 'error');
        }

    } catch (error) {
        console.error("Error:", error);
        showNotification("Could not connect to the server. Make sure XAMPP Apache is running.", 'error');
    }
});