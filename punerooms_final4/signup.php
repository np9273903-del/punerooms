<?php
require_once 'db.php';
start_session();
if (is_logged_in()) { header('Location: index.php'); exit; }

$errors   = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $email      = trim($_POST['email']      ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $password   = $_POST['password'] ?? '';
    $user_type  = in_array($_POST['user_type'] ?? '', ['student','owner']) ? $_POST['user_type'] : 'student';
    $college    = trim($_POST['college']    ?? '');

    // Save for repopulation
    $formData = compact('first_name','last_name','email','phone','user_type','college');

    // Validation
    if (empty($first_name))
        $errors[] = 'First name is required.';
    elseif (strlen($first_name) < 2)
        $errors[] = 'First name must be at least 2 characters.';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Please enter a valid email address.';

    if (!preg_match('/^[0-9]{10}$/', $phone))
        $errors[] = 'Phone number must be exactly 10 digits.';

    if (strlen($password) < 6)
        $errors[] = 'Password must be at least 6 characters.';
    elseif (strlen($password) > 128)
        $errors[] = 'Password is too long.';

    if (empty($errors)) {
        // Check duplicate email
        $check = db_query($conn, "SELECT id FROM pr_users WHERE email = $1", [$email]);
        if ($check && pg_num_rows($check) > 0) {
            $errors[] = 'This email is already registered. <a href="login.php" style="color:var(--vermillion)">Sign in instead?</a>';
        } else {
            $hash   = hash_password($password);
            $sql    = "INSERT INTO pr_users (first_name, last_name, email, phone, password, user_type, college)
                       VALUES ($1,$2,$3,$4,$5,$6,$7) RETURNING id";
            $result = db_query($conn, $sql, [
                $first_name, $last_name, $email, $phone, $hash, $user_type, $college
            ]);
            if ($result) {
                header('Location: login.php?success=' . urlencode('Account created successfully! Please sign in. 🎉'));
                exit;
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

$page_title = 'Sign Up - PuneRooms';
include 'header.php';
?>
<div class="auth-page">
    <!-- Left image panel -->
    <div class="auth-img-panel">
        <img src="images/room_rustic.jpg" alt="Cozy student room" class="auth-panel-bg">
        <div class="auth-panel-overlay"></div>
        <div class="auth-panel-content">
            <div class="auth-panel-logo">🏠 PuneRooms</div>
            <h2 class="auth-panel-title">Join thousands of<br>students finding rooms</h2>
            <p class="auth-panel-sub">List your room or browse verified listings. Free, fast, trusted.</p>
            <div class="auth-panel-imgs">
                <img src="images/room_living1.jpg" alt="Living room">
                <img src="images/room_premium.jpg" alt="Premium room">
                <img src="images/room_studio.jpg" alt="Studio">
            </div>
            <div class="auth-panel-stat">
                <div><strong>100%</strong><span>Free</span></div>
                <div><strong>Verified</strong><span>Listings</span></div>
                <div><strong>Pune</strong><span>City-wide</span></div>
            </div>
        </div>
    </div>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-deco">
                <div class="auth-logo-block">✨</div>
                <h2>Create Account</h2>
                <p>Join thousands of students on PuneRooms</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="php-msg error">
                    <?php foreach ($errors as $e): ?><p>❌ <?php echo $e; ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="signup.php" id="signupForm" novalidate onsubmit="return validateSignupForm()">
                <!-- Role selection -->
                <div style="margin-bottom:1.25rem;">
                    <label class="auth-label">I am a…</label>
                    <div class="role-selector">
                        <label class="role-card <?php echo ($formData['user_type']??'student')==='student'?'selected':''; ?>" id="rc-student">
                            <input type="radio" name="user_type" value="student"
                                   <?php echo ($formData['user_type']??'student')==='student'?'checked':''; ?>
                                   onchange="selectRole('student')">
                            <div class="role-icon">🎓</div>
                            <div class="role-name">Student</div>
                            <div class="role-desc">Looking for a room</div>
                        </label>
                        <label class="role-card <?php echo ($formData['user_type']??'')==='owner'?'selected':''; ?>" id="rc-owner">
                            <input type="radio" name="user_type" value="owner"
                                   <?php echo ($formData['user_type']??'')==='owner'?'checked':''; ?>
                                   onchange="selectRole('owner')">
                            <div class="role-icon">🏠</div>
                            <div class="role-name">Room Owner</div>
                            <div class="role-desc">Listing rooms</div>
                        </label>
                    </div>
                </div>

                <div class="form-row auth-form-row">
                    <div class="form-group auth-group">
                        <label class="auth-label" for="firstName">First Name *</label>
                        <div class="auth-input-wrap">
                            <span class="auth-icon">👤</span>
                            <input type="text" class="auth-input" name="first_name" id="firstName"
                                   placeholder="First name" required minlength="2"
                                   value="<?php echo htmlspecialchars($formData['first_name']??''); ?>">
                        </div>
                        <div class="field-error" id="firstNameError"></div>
                    </div>
                    <div class="form-group auth-group">
                        <label class="auth-label" for="lastName">Last Name</label>
                        <div class="auth-input-wrap">
                            <span class="auth-icon">👤</span>
                            <input type="text" class="auth-input" name="last_name" id="lastName"
                                   placeholder="Last name"
                                   value="<?php echo htmlspecialchars($formData['last_name']??''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group auth-group">
                    <label class="auth-label" for="signupEmail">Email Address *</label>
                    <div class="auth-input-wrap">
                        <span class="auth-icon">📧</span>
                        <input type="email" class="auth-input" name="email" id="signupEmail"
                               placeholder="your@email.com" required autocomplete="email"
                               value="<?php echo htmlspecialchars($formData['email']??''); ?>">
                    </div>
                    <div class="field-error" id="signupEmailError"></div>
                </div>

                <div class="form-group auth-group">
                    <label class="auth-label" for="signupPhone">Phone Number * (10 digits)</label>
                    <div class="auth-input-wrap">
                        <span class="auth-icon">📱</span>
                        <input type="tel" class="auth-input" name="phone" id="signupPhone"
                               placeholder="9876543210" pattern="[0-9]{10}" maxlength="10" required
                               value="<?php echo htmlspecialchars($formData['phone']??''); ?>">
                    </div>
                    <div class="field-error" id="phoneError"></div>
                </div>

                <div class="form-group auth-group">
                    <label class="auth-label" for="college">College / University</label>
                    <div class="auth-input-wrap">
                        <span class="auth-icon">🏫</span>
                        <input type="text" class="auth-input" name="college" id="college"
                               placeholder="e.g., COEP, MIT, FC College"
                               value="<?php echo htmlspecialchars($formData['college']??''); ?>">
                    </div>
                </div>

                <div class="form-group auth-group">
                    <label class="auth-label" for="signupPassword">Password * (min 6 characters)</label>
                    <div class="auth-input-wrap">
                        <span class="auth-icon">🔒</span>
                        <input type="password" class="auth-input" name="password" id="signupPassword"
                               placeholder="••••••" minlength="6" required autocomplete="new-password">
                        <button type="button" class="auth-show-pw" onclick="togglePwVis('signupPassword',this)" title="Show/Hide">👁</button>
                    </div>
                    <div class="field-error" id="passwordSignupError"></div>
                    <!-- Password strength -->
                    <div class="pw-strength" id="pwStrengthBar" style="display:flex;gap:3px;margin-top:.4rem;">
                        <div class="auth-sb" id="sb1"></div>
                        <div class="auth-sb" id="sb2"></div>
                        <div class="auth-sb" id="sb3"></div>
                        <div class="auth-sb" id="sb4"></div>
                    </div>
                    <div style="font-size:.72rem;color:var(--text3);margin-top:.2rem;" id="pwStrengthLabel"></div>
                </div>

                <button type="submit" class="auth-btn" id="signupBtn">
                    <span id="signupBtnText">Create Account →</span>
                    <span id="signupBtnLoad" style="display:none;">⏳ Creating account…</span>
                </button>
            </form>
            <div class="auth-switch">Already have an account? <a href="login.php">Sign in</a></div>
        </div>
    </div>
</div>

<style>
.field-error{color:#c0392b;font-size:.78rem;margin-top:.25rem;min-height:1rem;}
.auth-input.invalid{border-color:var(--vermillion)!important;box-shadow:0 0 0 3px rgba(228,61,18,.15)!important;}
.auth-input.valid{border-color:#22c55e!important;}
.auth-sb{height:4px;flex:1;border-radius:4px;background:var(--border);transition:background .3s;}
.auth-sb.w{background:var(--vermillion);}
.auth-sb.f{background:var(--rose);}
.auth-sb.g{background:var(--marigold);}
.auth-sb.s{background:#22c55e;}
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){ selectRole('<?php echo htmlspecialchars($formData['user_type']??'student'); ?>'); });

function selectRole(v) {
    document.getElementById('rc-student').classList.toggle('selected', v === 'student');
    document.getElementById('rc-owner').classList.toggle('selected', v === 'owner');
}

function validateSignupForm() {
    let valid = true;
    const fields = {
        firstName:     { id:'firstName',    err:'firstNameError',       req:true,  min:2,  label:'First name' },
        email:         { id:'signupEmail',  err:'signupEmailError',     req:true,  email:true, label:'Email' },
        phone:         { id:'signupPhone',  err:'phoneError',           req:true,  phone:true, label:'Phone' },
        password:      { id:'signupPassword',err:'passwordSignupError', req:true,  min:6,  label:'Password' },
    };

    for (const key in fields) {
        const f    = fields[key];
        const el   = document.getElementById(f.id);
        const err  = document.getElementById(f.err);
        const val  = el ? el.value.trim() : '';
        el.classList.remove('invalid','valid');
        if (err) err.textContent = '';

        if (f.req && !val) {
            if (err) err.textContent = f.label + ' is required.';
            el.classList.add('invalid'); valid = false;
        } else if (f.email && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            if (err) err.textContent = 'Please enter a valid email address.';
            el.classList.add('invalid'); valid = false;
        } else if (f.phone && val && !/^[0-9]{10}$/.test(val)) {
            if (err) err.textContent = 'Phone must be exactly 10 digits.';
            el.classList.add('invalid'); valid = false;
        } else if (f.min && val && val.length < f.min) {
            if (err) err.textContent = f.label + ' must be at least ' + f.min + ' characters.';
            el.classList.add('invalid'); valid = false;
        } else if (val) {
            el.classList.add('valid');
        }
    }

    if (valid) {
        document.getElementById('signupBtnText').style.display = 'none';
        document.getElementById('signupBtnLoad').style.display = 'inline';
        document.getElementById('signupBtn').disabled = true;
    }
    return valid;
}

function togglePwVis(id, btn) {
    const input = document.getElementById(id);
    input.type  = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? '👁' : '🙈';
}

function checkPwStrength(pw) {
    const bars   = ['sb1','sb2','sb3','sb4'].map(function(id){ return document.getElementById(id); });
    const labels = ['Too short','Weak','Fair','Strong','Very Strong'];
    bars.forEach(function(b){ if(b){ b.className='auth-sb'; } });

    let s = 0;
    if (pw.length >= 6)             s++;
    if (/[A-Z]/.test(pw))           s++;
    if (/[0-9]/.test(pw))           s++;
    if (/[^A-Za-z0-9]/.test(pw))    s++;

    const cls = ['w','f','g','s'];
    for (let i = 0; i < s; i++) { if(bars[i]) bars[i].classList.add(cls[Math.min(s-1,3)]); }
    const lbl = document.getElementById('pwStrengthLabel');
    if (lbl) lbl.textContent = pw.length ? labels[Math.min(s,4)] : '';
}

document.getElementById('signupPassword')?.addEventListener('input', function(){ checkPwStrength(this.value); });

// Real-time phone filter (numbers only)
document.getElementById('signupPhone')?.addEventListener('input', function(){
    this.value = this.value.replace(/[^0-9]/g,'');
});
</script>
<?php include 'footer.php'; ?>
