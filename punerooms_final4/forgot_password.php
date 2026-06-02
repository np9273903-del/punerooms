<?php
require_once 'db.php';
start_session();
if (is_logged_in()) { header('Location: index.php'); exit; }

$errors   = [];
$success  = '';
$step     = $_GET['step'] ?? 'email'; // email | reset

// ── STEP 1: Verify email and set new password ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn  = db_connect();
    $email = trim($_POST['email'] ?? '');
    $new_pw = $_POST['new_password'] ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    // Validate email
    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate passwords
    if (empty($new_pw)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($new_pw) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($new_pw !== $confirm_pw) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check if user exists
        $result = db_query($conn, "SELECT id, first_name, email FROM pr_users WHERE email = $1 AND is_active = TRUE", [$email]);
        if ($result && pg_num_rows($result) === 1) {
            $user = pg_fetch_assoc($result);
            $hashed = hash_password($new_pw);
            $update = db_query($conn, "UPDATE pr_users SET password = $1 WHERE email = $2", [$hashed, $email]);
            if ($update) {
                $success = 'Password updated successfully! You can now log in with your new password.';
            } else {
                $errors[] = 'Something went wrong. Please try again.';
            }
        } else {
            $errors[] = 'No active account found with this email address.';
        }
    }
}

$page_title = 'Reset Password - PuneRooms';
include 'header.php';
?>
<div class="auth-page">
    <!-- Left image panel -->
    <div class="auth-img-panel">
        <img src="images/building_close.jpg" alt="Modern student housing" class="auth-panel-bg">
        <div class="auth-panel-overlay"></div>
        <div class="auth-panel-content">
            <div class="auth-panel-logo">🏠 PuneRooms</div>
            <h2 class="auth-panel-title">Reset your<br>password easily</h2>
            <p class="auth-panel-sub">Enter your registered email and choose a new password. No email link needed — instant reset.</p>
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
                <div class="auth-logo-block">🔑</div>
                <h2>Reset Password</h2>
                <p>Enter your email and a new password to regain access</p>
            </div>

            <?php if ($success): ?>
                <div class="php-msg success">✅ <?php echo htmlspecialchars($success); ?></div>
                <div style="text-align:center;margin-top:1.5rem;">
                    <a href="login.php" class="auth-btn" style="display:inline-block;text-decoration:none;">Go to Login →</a>
                </div>
            <?php else: ?>

                <?php if (!empty($errors)): ?>
                    <div class="php-msg error">
                        <?php foreach ($errors as $e): ?><p>❌ <?php echo htmlspecialchars($e); ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="forgot_password.php" id="resetForm" novalidate onsubmit="return validateResetForm()">
                    <div class="form-group auth-group">
                        <label class="auth-label" for="resetEmail">Registered Email Address</label>
                        <div class="auth-input-wrap">
                            <span class="auth-icon">📧</span>
                            <input type="email" class="auth-input" name="email" id="resetEmail"
                                   placeholder="your@email.com" required autocomplete="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="field-error" id="emailError"></div>
                    </div>

                    <div class="form-group auth-group">
                        <label class="auth-label" for="newPassword">New Password</label>
                        <div class="auth-input-wrap">
                            <span class="auth-icon">🔒</span>
                            <input type="password" class="auth-input" name="new_password" id="newPassword"
                                   placeholder="Minimum 6 characters" required autocomplete="new-password">
                            <button type="button" class="auth-show-pw" onclick="togglePwVis('newPassword',this)" title="Show/Hide">👁</button>
                        </div>
                        <div class="field-error" id="newPwError"></div>
                    </div>

                    <div class="form-group auth-group">
                        <label class="auth-label" for="confirmPassword">Confirm New Password</label>
                        <div class="auth-input-wrap">
                            <span class="auth-icon">🔒</span>
                            <input type="password" class="auth-input" name="confirm_password" id="confirmPassword"
                                   placeholder="Re-enter new password" required autocomplete="new-password">
                            <button type="button" class="auth-show-pw" onclick="togglePwVis('confirmPassword',this)" title="Show/Hide">👁</button>
                        </div>
                        <div class="field-error" id="confirmPwError"></div>
                    </div>

                    <!-- Password strength indicator -->
                    <div id="pwStrengthWrap" style="display:none;margin-bottom:1rem;">
                        <div style="font-size:.72rem;color:var(--text3);margin-bottom:.3rem;">Password strength</div>
                        <div style="height:5px;background:var(--bg3);border-radius:4px;overflow:hidden;">
                            <div id="pwStrengthBar" style="height:100%;width:0%;border-radius:4px;transition:width .3s,background .3s;"></div>
                        </div>
                        <div id="pwStrengthLabel" style="font-size:.72rem;margin-top:.25rem;"></div>
                    </div>

                    <button type="submit" class="auth-btn" id="resetBtn">
                        <span id="resetBtnText">Update Password →</span>
                        <span id="resetBtnLoad" style="display:none;">⏳ Updating…</span>
                    </button>
                </form>

            <?php endif; ?>

            <div class="auth-switch" style="margin-top:1.2rem;">Remember your password? <a href="login.php">Back to Login</a></div>
        </div>
    </div>
</div>

<style>
.field-error{color:#c0392b;font-size:.78rem;margin-top:.25rem;min-height:1rem;}
.auth-input.invalid{border-color:var(--vermillion)!important;box-shadow:0 0 0 3px rgba(228,61,18,.15)!important;}
.auth-input.valid{border-color:#22c55e!important;}
</style>

<script>
function validateResetForm() {
    let valid = true;
    const email   = document.getElementById('resetEmail');
    const newPw   = document.getElementById('newPassword');
    const confPw  = document.getElementById('confirmPassword');
    const emailErr  = document.getElementById('emailError');
    const newPwErr  = document.getElementById('newPwError');
    const confPwErr = document.getElementById('confirmPwError');

    [email, newPw, confPw].forEach(el => el.classList.remove('invalid','valid'));
    emailErr.textContent = ''; newPwErr.textContent = ''; confPwErr.textContent = '';

    if (!email.value.trim()) {
        emailErr.textContent = 'Email is required.';
        email.classList.add('invalid'); valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
        emailErr.textContent = 'Please enter a valid email.';
        email.classList.add('invalid'); valid = false;
    } else {
        email.classList.add('valid');
    }

    if (!newPw.value) {
        newPwErr.textContent = 'New password is required.';
        newPw.classList.add('invalid'); valid = false;
    } else if (newPw.value.length < 6) {
        newPwErr.textContent = 'Password must be at least 6 characters.';
        newPw.classList.add('invalid'); valid = false;
    } else {
        newPw.classList.add('valid');
    }

    if (!confPw.value) {
        confPwErr.textContent = 'Please confirm your password.';
        confPw.classList.add('invalid'); valid = false;
    } else if (newPw.value !== confPw.value) {
        confPwErr.textContent = 'Passwords do not match.';
        confPw.classList.add('invalid'); valid = false;
    } else {
        confPw.classList.add('valid');
    }

    if (valid) {
        document.getElementById('resetBtnText').style.display = 'none';
        document.getElementById('resetBtnLoad').style.display = 'inline';
        document.getElementById('resetBtn').disabled = true;
    }
    return valid;
}

function togglePwVis(id, btn) {
    const input = document.getElementById(id);
    input.type  = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? '👁' : '🙈';
}

// Password strength meter
document.addEventListener('DOMContentLoaded', function(){
    const pwInput = document.getElementById('newPassword');
    if (pwInput) {
        pwInput.addEventListener('input', function(){
            const val = this.value;
            const wrap = document.getElementById('pwStrengthWrap');
            const bar  = document.getElementById('pwStrengthBar');
            const lbl  = document.getElementById('pwStrengthLabel');
            if (!val) { wrap.style.display='none'; return; }
            wrap.style.display = 'block';
            let score = 0;
            if (val.length >= 6)  score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            const levels = [
                {w:'20%', bg:'#e74c3c', text:'Weak'},
                {w:'40%', bg:'#e67e22', text:'Fair'},
                {w:'60%', bg:'#f1c40f', text:'Good'},
                {w:'80%', bg:'#27ae60', text:'Strong'},
                {w:'100%',bg:'#16a085', text:'Very Strong'},
            ];
            const l = levels[Math.min(score, 4)];
            bar.style.width = l.w;
            bar.style.background = l.bg;
            lbl.textContent = l.text;
            lbl.style.color = l.bg;
        });
    }
});
</script>
<?php include 'footer.php'; ?>
