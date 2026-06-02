<?php
require_once 'db.php';
start_session();
if (is_logged_in()) { header('Location: index.php'); exit; }

$errors = [];
$email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn     = db_connect();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Server-side validation
    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    $email_val = htmlspecialchars($email);

    if (empty($errors)) {
        $hash = hash_password($password); // PHP sha256
        $sql  = "SELECT id, first_name, last_name, user_type, email
                 FROM pr_users
                 WHERE email = $1 AND password = $2 AND is_active = TRUE";
        $result = db_query($conn, $sql, [$email, $hash]);

        if ($result && pg_num_rows($result) === 1) {
            $user = pg_fetch_assoc($result);
            set_user_session($user);
            $dest = ($user['user_type'] === 'admin') ? 'admin.php' : 'index.php';
            header('Location: ' . $dest . '?msg=' . urlencode('Welcome back, ' . $user['first_name'] . '! 👋') . '&mtype=success');
            exit;
        } else {
            // Check if user exists but is inactive
            $check = db_query($conn, "SELECT id, is_active FROM pr_users WHERE email = $1", [$email]);
            if ($check && pg_num_rows($check) === 1) {
                $u = pg_fetch_assoc($check);
                if ($u['is_active'] === 'f' || $u['is_active'] === false || $u['is_active'] === '0') {
                    $errors[] = 'Your account has been deactivated. Please contact support.';
                } else {
                    $errors[] = 'Incorrect password. Please try again.';
                }
            } else {
                $errors[] = 'No account found with this email address.';
            }
        }
    }
}

$success = $_GET['success'] ?? ($_GET['msg'] ?? '');
$page_title = 'Login - PuneRooms';
include 'header.php';
?>
<div class="auth-page">
    <!-- Left image panel -->
    <div class="auth-img-panel">
        <img src="images/building_close.jpg" alt="Modern student housing" class="auth-panel-bg">
        <div class="auth-panel-overlay"></div>
        <div class="auth-panel-content">
            <div class="auth-panel-logo">🏠 PuneRooms</div>
            <h2 class="auth-panel-title">Find your perfect<br>student home in Pune</h2>
            <p class="auth-panel-sub">Thousands of verified rooms, PGs and shared apartments near your college.</p>
            <div class="auth-panel-imgs">
                <img src="images/room_bedroom.jpg" alt="Room 1">
                <img src="images/room_apartment.jpg" alt="Room 2">
                <img src="images/room_1bhk.jpg" alt="Room 3">
            </div>
            <div class="auth-panel-stat">
                <div><strong>500+</strong><span>Listings</span></div>
                <div><strong>15+</strong><span>Areas</span></div>
                <div><strong>Free</strong><span>Always</span></div>
            </div>
        </div>
    </div>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-deco">
                <div class="auth-logo-block">🏠</div>
                <h2>Welcome Back</h2>
                <p>Sign in to your PuneRooms account</p>
            </div>

            <?php if ($success): ?>
                <div class="php-msg success">✅ <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="php-msg error">
                    <?php foreach ($errors as $e): ?><p>❌ <?php echo htmlspecialchars($e); ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm" novalidate onsubmit="return validateLoginForm()">
                <div class="form-group auth-group">
                    <label class="auth-label" for="loginEmail">Email Address</label>
                    <div class="auth-input-wrap">
                        <span class="auth-icon">📧</span>
                        <input type="email" class="auth-input" name="email" id="loginEmail"
                               placeholder="your@email.com" required autocomplete="email"
                               value="<?php echo $email_val; ?>">
                    </div>
                    <div class="field-error" id="emailError"></div>
                </div>
                <div class="form-group auth-group">
                    <label class="auth-label" for="loginPassword">Password</label>
                    <div class="auth-input-wrap">
                        <span class="auth-icon">🔒</span>
                        <input type="password" class="auth-input" name="password" id="loginPassword"
                               placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="auth-show-pw" onclick="togglePwVis('loginPassword',this)" title="Show/Hide">👁</button>
                    </div>
                    <div class="field-error" id="passwordError"></div>
                </div>
                <button type="submit" class="auth-btn" id="loginBtn">
                    <span id="loginBtnText">Sign In →</span>
                    <span id="loginBtnLoad" style="display:none;">⏳ Signing in…</span>
                </button>
            </form>

            <div class="auth-switch" style="margin-top:1rem;">Forgot your password? <a href="forgot_password.php">Reset it here</a></div>
            <div class="auth-switch">Don't have an account? <a href="signup.php">Create one free</a></div>
        </div>
    </div>
</div>

<style>
.field-error{color:#c0392b;font-size:.78rem;margin-top:.25rem;min-height:1rem;}
.auth-input.invalid{border-color:var(--vermillion)!important;box-shadow:0 0 0 3px rgba(228,61,18,.15)!important;}
.auth-input.valid{border-color:#22c55e!important;}
code{background:var(--bg3);padding:.1rem .3rem;border-radius:4px;font-size:.76rem;color:var(--text);}
</style>

<script>
function validateLoginForm() {
    let valid = true;
    const email    = document.getElementById('loginEmail');
    const password = document.getElementById('loginPassword');
    const emailErr = document.getElementById('emailError');
    const pwErr    = document.getElementById('passwordError');

    // Reset
    [email, password].forEach(function(el){ el.classList.remove('invalid','valid'); });
    emailErr.textContent = ''; pwErr.textContent = '';

    // Email
    const emailVal = email.value.trim();
    if (!emailVal) {
        emailErr.textContent = 'Email is required.';
        email.classList.add('invalid'); valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
        emailErr.textContent = 'Please enter a valid email.';
        email.classList.add('invalid'); valid = false;
    } else {
        email.classList.add('valid');
    }

    // Password
    if (!password.value) {
        pwErr.textContent = 'Password is required.';
        password.classList.add('invalid'); valid = false;
    } else {
        password.classList.add('valid');
    }

    if (valid) {
        document.getElementById('loginBtnText').style.display = 'none';
        document.getElementById('loginBtnLoad').style.display = 'inline';
        document.getElementById('loginBtn').disabled = true;
    }
    return valid;
}

function togglePwVis(id, btn) {
    const input = document.getElementById(id);
    input.type  = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? '👁' : '🙈';
}

// Real-time inline validation
document.addEventListener('DOMContentLoaded', function(){
    const emailInput = document.getElementById('loginEmail');
    if (emailInput) {
        emailInput.addEventListener('blur', function(){
            const err = document.getElementById('emailError');
            if (!this.value.trim()) {
                err.textContent = 'Email is required.';
                this.classList.add('invalid'); this.classList.remove('valid');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim())) {
                err.textContent = 'Please enter a valid email.';
                this.classList.add('invalid'); this.classList.remove('valid');
            } else {
                err.textContent = '';
                this.classList.remove('invalid'); this.classList.add('valid');
            }
        });
    }
});
</script>
<?php include 'footer.php'; ?>
