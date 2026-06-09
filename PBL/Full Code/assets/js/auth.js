/* =============================================
   auth.js - PBL Management System
   Login, Register, Logout, Session Check
============================================= */

window.currentRole = "student";

/* --- Role tab switching --- */
function selectRole(role, btn) {
    window.currentRole = role;

    var container = btn.closest(".btn-group") || btn.closest(".d-flex");
    if (container) {
        container.querySelectorAll(".role-btn").forEach(function(b) {
            b.classList.remove("active", "btn-primary", "text-white");
            b.classList.add("btn-outline-primary");
        });
    }
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
    var regIdGroup       = document.getElementById("regIdGroup");
    var regProgramGroup  = document.getElementById("regProgramGroup");
    var regSemesterGroup = document.getElementById("regSemesterGroup");
    var regDeptEl        = document.getElementById("regDept");
    var regDeptGroup     = regDeptEl ? regDeptEl.closest(".mb-3") : null;

    if (role === "student") {
        if (regIdGroup)       regIdGroup.style.display       = "none"; 
        if (regProgramGroup)  regProgramGroup.style.display  = "";
        if (regSemesterGroup) regSemesterGroup.style.display = "";
        if (regDeptGroup)     regDeptGroup.style.display     = "";
    } else if (role === "supervisor") {
        if (regIdGroup)       regIdGroup.style.display       = "none";
        if (regProgramGroup)  regProgramGroup.style.display  = "";
        if (regSemesterGroup) regSemesterGroup.style.display = "none";
        if (regDeptGroup)     regDeptGroup.style.display     = "";
    } else if (role === "pbl_manager" || role === "manager") {
        if (regIdGroup)       regIdGroup.style.display       = "none";
        if (regProgramGroup)  regProgramGroup.style.display  = "none";
        if (regSemesterGroup) regSemesterGroup.style.display = "none";
        if (regDeptGroup)     regDeptGroup.style.display     = "";
    } else if (role === "evaluator") {
        if (regIdGroup)       regIdGroup.style.display       = "none";
        if (regProgramGroup)  regProgramGroup.style.display  = "none";
        if (regSemesterGroup) regSemesterGroup.style.display = "none";
        if (regDeptGroup)     regDeptGroup.style.display     = "";
    } else if (role === "chairman") {
        if (regIdGroup)       regIdGroup.style.display       = "none";
        if (regProgramGroup)  regProgramGroup.style.display  = "none";
        if (regSemesterGroup) regSemesterGroup.style.display = "none";
        if (regDeptGroup)     regDeptGroup.style.display     = "none";
    } else {
        if (regIdGroup)       regIdGroup.style.display       = "none";
        if (regProgramGroup)  regProgramGroup.style.display  = "none";
        if (regSemesterGroup) regSemesterGroup.style.display = "none";
        if (regDeptGroup)     regDeptGroup.style.display     = "none";
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
        role:     window.currentRole,
        password: document.getElementById("loginPassword").value
    };

    if (window.currentRole === "student") {
        body.roll_number = document.getElementById("loginRollNumber").value.trim().toUpperCase();
    } else {
        body.email = document.getElementById("loginEmail").value.trim().toLowerCase();
    }

    try {
        var response = await fetch("api/auth/login.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify(body)
        });

        var text = await response.text();
        var result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error("Invalid JSON from server:", text);
            throw new Error("Invalid response format");
        }

        if (result.success) {
            sessionStorage.setItem("pbl_name",  result.name);
            sessionStorage.setItem("pbl_role",  result.role);
            sessionStorage.setItem("pbl_roll",  result.roll  || "");
            sessionStorage.setItem("pbl_email", result.email || "");
            var red = String(result.redirect || "");
            if (red && !/^https?:\/\//i.test(red) && red.charAt(0) !== "/") {
                const pathParts = window.location.pathname.split('/').filter(Boolean);
                const projectRoot = pathParts.length > 0 ? '/' + pathParts[0] + '/' : '/';
                red = projectRoot + red.replace(/^\/+/, "");
            }
            window.location.href = red || "index.php";
        } else {
            showError("loginError", result.message);
            btn.disabled        = false;
            btnText.textContent = "Sign In";
            spinner.classList.add("d-none");
        }

    } catch (err) {
        console.error("Auth Error:", err);
        showError("loginError", "Connection or server error. Check console for details.");
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
    var dept = document.getElementById("regDept")    ? document.getElementById("regDept").value    : "";
    var prog = document.getElementById("regProgram") ? document.getElementById("regProgram").value : "";

    if (window.currentRole === "student") {
        if (!dept) { showError("regError", "Please select your department."); return; }
        if (!prog) { showError("regError", "Please select your program.");    return; }
    } else if (window.currentRole === "supervisor") {
        if (!dept) { showError("regError", "Please select your department."); return; }
        if (!prog) { showError("regError", "Please select your program.");    return; }
    } else if (window.currentRole === "pbl_manager") {
        if (!dept) { showError("regError", "Please select your department."); return; }
    } else if (window.currentRole === "evaluator") {
        if (!dept) { showError("regError", "Please select your department."); return; }
    }

    var pw = document.getElementById("regPassword").value;
    if (pw.length < 6) { showError("regError", "Password must be at least 6 characters."); return; }

    if (btn) {
        btn.disabled  = true;
        btn.innerHTML = "<span class='spinner-border spinner-border-sm me-1'></span> Creating...";
    }

    // Determine role from the active button in the UI
    var activeRoleBtn = document.querySelector(".role-btn.active");
    var role = activeRoleBtn ? activeRoleBtn.textContent.trim().toLowerCase() : "student";
    
    // Normalize role naming
    if (role === "pbl manager") role = "pbl_manager";
    if (role === "manager") role = "pbl_manager";

    var body = {
        role:               role,
        first_name:         document.getElementById("regFirst").value.trim(),
        last_name:          document.getElementById("regLast").value.trim(),
        email:              document.getElementById("regEmail")     ? document.getElementById("regEmail").value.trim().toLowerCase()      : "",
        password:           pw,
        program:            document.getElementById("regProgram")   ? document.getElementById("regProgram").value   : "",
        department:         document.getElementById("regDept")      ? document.getElementById("regDept").value      : "",
        semester_id:        document.getElementById("regSemester")  ? document.getElementById("regSemester").value  : "",
        enrollment_year:    document.getElementById("regYear")      ? document.getElementById("regYear").value      : "",
        enrollment_session: document.getElementById("regSession")   ? document.getElementById("regSession").value   : "",
        roll_number:        document.getElementById("regStudentId") ? document.getElementById("regStudentId").value.trim().toUpperCase() : ""
    };

    console.log("Registering with detected role:", role, body);

    try {
        var response = await fetch("../../api/auth/register.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify(body)
        });

        var text = await response.text();
        var result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error("Invalid JSON from server:", text);
            throw new Error("Invalid response format");
        }

        if (result.success) {
            showSuccess("regSuccess", result.message);
            setTimeout(function() {
                var red = String(result.redirect || "");
                if (red && !/^https?:\/\//i.test(red) && red.charAt(0) !== "/") {
                    const pathParts = window.location.pathname.split('/').filter(Boolean);
                    const projectRoot = pathParts.length > 0 ? '/' + pathParts[0] + '/' : '/';
                    red = projectRoot + red.replace(/^\/+/, "");
                }
                window.location.href = red || "../../index.php";
            }, 1500);
        } else {
            showError("regError", result.message);
            if (btn) {
                btn.disabled  = false;
                btn.innerHTML = "<i class='bi bi-person-plus me-1'></i> Create Account";
            }
        }

    } catch (err) {
        console.error("Auth Error:", err);
        showError("regError", "Connection or server error. Check console for details.");
        if (btn) {
            btn.disabled  = false;
            btn.innerHTML = "<i class='bi bi-person-plus me-1'></i> Create Account";
        }
    }
}

/* --- Logout --- */
async function logout() {
    try {
        await fetch("../../api/auth/logout.php", { method: "POST" });
    } catch(e) {}
    sessionStorage.clear();
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    const projectRoot = pathParts.length > 0 ? '/' + pathParts[0] + '/' : '/';
    window.location.href = projectRoot + "index.php";
}

/* --- Check session on dashboard pages --- */
async function requireAuth(expectedRole) {
    try {
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        const projectRoot = pathParts.length > 0 ? '/' + pathParts[0] + '/' : '/';
        var response = await fetch(projectRoot + "api/auth/check_session.php");
        var result   = await response.json();

        if (!result.logged_in) {
            window.location.href = projectRoot + "index.php";
            return;
        }

        if (expectedRole && result.role !== expectedRole) {
            window.location.href = projectRoot + "index.php";
            return;
        }

        // Fill sidebar user info
        var nameEl   = document.getElementById("userName");
        var greetEl  = document.getElementById("greetName");
        var avatarEl = document.getElementById("userAvatar");
        var roleEl   = document.getElementById("userRoleLabel");
        var rollEl   = document.getElementById("userRoll");

        if (nameEl)   nameEl.textContent   = result.name;
        if (greetEl)  greetEl.textContent  = result.name.split(" ")[0];
        if (avatarEl) avatarEl.textContent = result.name.split(" ").map(function(p){ return p[0]; }).join("").substring(0,2).toUpperCase();
        
        if (rollEl) {
            rollEl.textContent = result.roll || (result.role === 'pbl_manager' ? 'PBL Manager' : (result.role === 'supervisor' ? 'Supervisor' : 'N/A'));
        }

        if (roleEl) {
            var labels = {
                student:     "Student",
                supervisor:  "Supervisor",
                pbl_manager: "PBL Manager",
                evaluator:   "Evaluator",
                chairman:    "Chairman"
            };
            roleEl.textContent = labels[result.role] || result.role;
        }

    } catch(err) {
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        const projectRoot = pathParts.length > 0 ? '/' + pathParts[0] + '/' : '/';
        window.location.href = projectRoot + "index.php";
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
