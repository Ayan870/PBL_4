/* =============================================
   auth.js - PBL Management System
   Login, Register, Logout, Session Check
============================================= */

let currentRole = "student";

/* --- Role tab switching --- */
function selectRole(role, btn) {
    currentRole = role;

    btn.closest(".btn-group").querySelectorAll(".role-btn").forEach(function(b) {
        b.classList.remove("active", "btn-primary", "text-white");
        b.classList.add("btn-outline-primary");
    });
    btn.classList.add("active", "btn-primary", "text-white");
    btn.classList.remove("btn-outline-primary");

    // Login page toggles
    var rollGroup  = document.getElementById("loginRollGroup");
    var emailGroup = document.getElementById("loginEmailGroup");
    if (rollGroup && emailGroup) {
        if (role === "student") {
            rollGroup.classList.remove("d-none");
            emailGroup.classList.add("d-none");
        } else {
            rollGroup.classList.add("d-none");
            emailGroup.classList.remove("d-none");
        }
    }

    // Register page toggles
    var regIdGroup      = document.getElementById("regIdGroup");
    var regProgramGroup = document.getElementById("regProgramGroup");
    var regDeptEl       = document.getElementById("regDept");
    var regDeptGroup    = regDeptEl ? regDeptEl.closest(".mb-3") : null;

    if (role === "student") {
        if (regIdGroup)      regIdGroup.style.display      = "";
        if (regProgramGroup) regProgramGroup.style.display = "";
        if (regDeptGroup)    regDeptGroup.style.display    = "";
    } else {
        if (regIdGroup)      regIdGroup.style.display      = "none";
        if (regProgramGroup) regProgramGroup.style.display = "none";
        if (regDeptGroup)    regDeptGroup.style.display    = "none";
    }
}

/* --- Handle Login --- */
async function handleLogin(e) {
    e.preventDefault();

    var btn     = document.getElementById("loginBtn");
    var btnText = document.getElementById("loginBtnText");
    var spinner = document.getElementById("loginSpinner");

    btn.disabled        = true;
    btnText.textContent = "Signing in...";
    spinner.classList.remove("d-none");

    var body = {
        role:     currentRole,
        password: document.getElementById("loginPassword").value
    };

    if (currentRole === "student") {
        body.roll_number = document.getElementById("loginRollNumber").value.trim().toUpperCase();
    } else {
        body.email = document.getElementById("loginEmail").value.trim().toLowerCase();
    }

    try {
        var response = await fetch("/pbl-management-system/api/auth/login.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify(body)
        });

        var result = await response.json();

        if (result.success) {
            sessionStorage.setItem("pbl_name",  result.name);
            sessionStorage.setItem("pbl_role",  result.role);
            sessionStorage.setItem("pbl_roll",  result.roll  || "");
            sessionStorage.setItem("pbl_email", result.email || "");
            window.location.href = result.redirect;
        } else {
            showError("loginError", result.message);
            btn.disabled        = false;
            btnText.textContent = "Sign In";
            spinner.classList.add("d-none");
        }

    } catch (err) {
        showError("loginError", "Cannot connect to server. Is XAMPP running?");
        btn.disabled        = false;
        btnText.textContent = "Sign In";
        spinner.classList.add("d-none");
    }
}

/* --- Handle Register --- */
async function handleRegister(e) {
    e.preventDefault();

    var btn = document.querySelector("#regForm button[type='submit']");

    // Validate before sending
    if (currentRole === "student") {
        var dept = document.getElementById("regDept")    ? document.getElementById("regDept").value    : "";
        var prog = document.getElementById("regProgram") ? document.getElementById("regProgram").value : "";
        if (!dept) { showError("regError", "Please select a department."); return; }
        if (!prog) { showError("regError", "Please select a program.");    return; }
    }

    var pw = document.getElementById("regPassword").value;
    if (pw.length < 6) { showError("regError", "Password must be at least 6 characters."); return; }

    if (btn) {
        btn.disabled  = true;
        btn.innerHTML = "<span class='spinner-border spinner-border-sm me-1'></span> Creating...";
    }

    var body = {
        role:        currentRole,
        first_name:  document.getElementById("regFirst").value.trim(),
        last_name:   document.getElementById("regLast").value.trim(),
        email:       document.getElementById("regEmail")     ? document.getElementById("regEmail").value.trim().toLowerCase()      : "",
        password:    pw,
        program:     document.getElementById("regProgram")   ? document.getElementById("regProgram").value   : "",
        department:  document.getElementById("regDept")      ? document.getElementById("regDept").value      : "",
        roll_number: document.getElementById("regStudentId") ? document.getElementById("regStudentId").value.trim().toUpperCase() : ""
    };

    try {
        var response = await fetch("/pbl-management-system/api/auth/register.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify(body)
        });

        var result = await response.json();

        if (result.success) {
            showSuccess("regSuccess", result.message);
            setTimeout(function() {
                window.location.href = result.redirect;
            }, 1500);
        } else {
            showError("regError", result.message);
            if (btn) {
                btn.disabled  = false;
                btn.innerHTML = "<i class='bi bi-person-plus me-1'></i> Create Account";
            }
        }

    } catch (err) {
        showError("regError", "Cannot connect to server. Is XAMPP running?");
        if (btn) {
            btn.disabled  = false;
            btn.innerHTML = "<i class='bi bi-person-plus me-1'></i> Create Account";
        }
    }
}

/* --- Logout --- */
async function logout() {
    try {
        await fetch("/pbl-management-system/api/auth/logout.php", { method: "POST" });
    } catch(e) {}
    sessionStorage.clear();
    window.location.href = "/pbl-management-system/index.html";
}

/* --- Check session on dashboard pages --- */
async function requireAuth(expectedRole) {
    try {
        var response = await fetch("/pbl-management-system/api/auth/check_session.php");
        var result   = await response.json();

        if (!result.logged_in) {
            window.location.href = "/pbl-management-system/index.html";
            return;
        }

        if (expectedRole && result.role !== expectedRole) {
            window.location.href = "/pbl-management-system/index.html";
            return;
        }

        // Fill sidebar user info
        var nameEl   = document.getElementById("userName");
        var greetEl  = document.getElementById("greetName");
        var avatarEl = document.getElementById("userAvatar");
        var roleEl   = document.getElementById("userRoleLabel");

        if (nameEl)   nameEl.textContent   = result.name;
        if (greetEl)  greetEl.textContent  = result.name.split(" ")[0];
        if (avatarEl) avatarEl.textContent = result.name.split(" ").map(function(p){ return p[0]; }).join("").substring(0,2).toUpperCase();
        if (roleEl) {
            var labels = {
                student:     "Student",
                supervisor:  "Supervisor",
                pbl_manager: "PBL Manager",
                evaluator:   "Evaluator"
            };
            roleEl.textContent = labels[result.role] || result.role;
        }

    } catch(err) {
        window.location.href = "/pbl-management-system/index.html";
    }
}

/* --- Show error message --- */
function showError(id, message) {
    var el = document.getElementById(id);
    if (!el) {
        el = document.createElement("div");
        el.id        = id;
        el.className = "alert alert-danger mt-3 py-2 small";
        var form = document.querySelector("form");
        if (form) form.insertAdjacentElement("afterend", el);
    }
    el.style.display = "";
    el.textContent   = message;
}

/* --- Show success message --- */
function showSuccess(id, message) {
    var el = document.getElementById(id);
    if (!el) {
        el = document.createElement("div");
        el.id        = id;
        el.className = "alert alert-success mt-3 py-2 small";
        var form = document.querySelector("form");
        if (form) form.insertAdjacentElement("afterend", el);
    }
    el.style.display = "";
    el.textContent   = message;
}
