<?php
require_once 'db.php';
start_session();
require_login();

if (!is_owner()) {
    header('Location: index.php?msg=' . urlencode('Only room owners can list rooms. Please create an Owner account.') . '&mtype=error');
    exit;
}

$errors  = [];
$success = '';
$post    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();

    $title            = trim($_POST['title']            ?? '');
    $description      = trim($_POST['description']      ?? '');
    $monthly_rent     = (int)($_POST['monthly_rent']    ?? 0);
    $security_deposit = (int)($_POST['security_deposit']?? 0);
    $area             = trim($_POST['area']             ?? '');
    $nearby_college   = trim($_POST['nearby_college']   ?? '');
    $room_type        = trim($_POST['room_type']        ?? '');
    $gender_pref      = trim($_POST['gender_preference']?? '');
    $occupancy        = (int)($_POST['occupancy']       ?? 1);
    $contact          = trim($_POST['contact']          ?? '');

    $post = $_POST; // for repopulation

    // Validation
    if (empty($title))             $errors[] = 'Room title is required.';
    elseif (strlen($title) < 5)    $errors[] = 'Title must be at least 5 characters.';
    if ($monthly_rent <= 0)        $errors[] = 'Please enter a valid monthly rent (must be > 0).';
    elseif ($monthly_rent < 500)   $errors[] = 'Rent seems too low. Minimum ₹500.';
    elseif ($monthly_rent > 200000) $errors[] = 'Rent seems too high. Maximum ₹2,00,000.';
    if (empty($area))              $errors[] = 'Please select an area.';
    if (empty($room_type))         $errors[] = 'Please select a room type.';
    if (empty($gender_pref))       $errors[] = 'Please select gender preference.';
    if (!preg_match('/^[0-9]{10}$/', $contact)) $errors[] = 'Contact number must be exactly 10 digits.';
    if ($security_deposit < 0)     $errors[] = 'Security deposit cannot be negative.';

    if (empty($errors)) {
        $bv = function($name) { return isset($_POST[$name]) ? 't' : 'f'; };

        $sql = "INSERT INTO pr_rooms (
                    user_id, title, description, monthly_rent, security_deposit,
                    area, nearby_college, room_type, gender_preference, occupancy, contact, status,
                    has_wifi, has_ac, has_meals, has_parking, has_laundry, has_security,
                    has_furnished, has_power_backup, has_balcony, has_cctv, has_pets, has_water
                ) VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,'Available',$12,$13,$14,$15,$16,$17,$18,$19,$20,$21,$22,$23)";

        $params = [
            get_user_id(), $title, $description, $monthly_rent, $security_deposit,
            $area, $nearby_college, $room_type, $gender_pref, $occupancy, $contact,
            $bv('has_wifi'), $bv('has_ac'), $bv('has_meals'), $bv('has_parking'),
            $bv('has_laundry'), $bv('has_security'), $bv('has_furnished'),
            $bv('has_power_backup'), $bv('has_balcony'), $bv('has_cctv'), $bv('has_pets'), $bv('has_water')
        ];

        $result = db_query($conn, $sql, $params);
        if ($result) {
            header('Location: index.php?msg=' . urlencode('Room listed successfully! 🎉') . '&mtype=success');
            exit;
        } else {
            $errors[] = 'Database error. Please try again.';
        }
    }
}

$page_title = 'List a Room - PuneRooms';
include 'header.php';
?>
<div class="page active">
    <div class="container">
        <div class="page-header">
            <img src="images/building_close.jpg" alt="" class="page-header-bg">
            <h1>🏠 List Your <span class="page-header-accent">Room</span></h1>
            <p>Reach thousands of students looking for accommodation in Pune</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="php-msg error" style="max-width:750px;margin:0 auto 1.5rem;">
                <div><strong>❌ Please fix the following errors:</strong></div>
                <?php foreach ($errors as $e): ?><p style="margin-top:.3rem;">• <?php echo htmlspecialchars($e); ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="max-width:750px;margin:0 auto;">
            <form method="POST" action="list.php" id="listRoomForm" novalidate onsubmit="return validateListForm()">

                <div class="form-card">
                    <div class="form-section-title">🏠 Basic Information</div>
                    <div class="form-group">
                        <label class="form-label">Room Title *</label>
                        <input type="text" class="form-input" name="title"
                               placeholder="e.g., Spacious Single Room near FC College"
                               required maxlength="300"
                               value="<?php echo htmlspecialchars($post['title']??''); ?>">
                        <div class="field-error" id="titleErr"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" name="description" rows="4"
                                  placeholder="Describe your room, nearby landmarks, house rules…"
                                  maxlength="1000"><?php echo htmlspecialchars($post['description']??''); ?></textarea>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-section-title">💰 Pricing & Location</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Monthly Rent (₹) *</label>
                            <input type="number" class="form-input" name="monthly_rent"
                                   placeholder="8000" required min="500" max="200000"
                                   value="<?php echo (int)($post['monthly_rent']??0)?:''; ?>">
                            <div class="field-error" id="rentErr"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Security Deposit (₹)</label>
                            <input type="number" class="form-input" name="security_deposit"
                                   placeholder="15000" min="0"
                                   value="<?php echo isset($post['security_deposit'])?(int)$post['security_deposit']:''; ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Area in Pune *</label>
                            <select class="form-select" name="area" required>
                                <option value="">Select Area</option>
                                <?php foreach (['Kothrud','Karve Nagar','Warje','Baner','Aundh','Shivaji Nagar','Camp','Koregaon Park','Viman Nagar','Hadapsar','Katraj','Sinhgad Road','Wakad','Hinjewadi','Pimpri'] as $a): ?>
                                <option value="<?php echo $a; ?>" <?php echo ($post['area']??'')===$a?'selected':''; ?>><?php echo $a; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="field-error" id="areaErr"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nearby College</label>
                            <input type="text" class="form-input" name="nearby_college"
                                   placeholder="e.g., FC College, COEP, MIT"
                                   value="<?php echo htmlspecialchars($post['nearby_college']??''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-section-title">📋 Details & Preferences</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Room Type *</label>
                            <select class="form-select" name="room_type" required>
                                <option value="">Select Type</option>
                                <?php foreach (['Single Room','Shared Room','PG','1BHK','Studio'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($post['room_type']??'')===$t?'selected':''; ?>><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="field-error" id="rtypeErr"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender Preference *</label>
                            <select class="form-select" name="gender_preference" required>
                                <option value="">Select</option>
                                <?php foreach (['Male','Female','Co-ed'] as $g): ?>
                                <option value="<?php echo $g; ?>" <?php echo ($post['gender_preference']??'')===$g?'selected':''; ?>><?php echo $g; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="field-error" id="genderErr"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Occupancy *</label>
                            <select class="form-select" name="occupancy" required>
                                <option value="1" <?php echo ($post['occupancy']??'1')==='1'?'selected':''; ?>>Single (1 person)</option>
                                <option value="2" <?php echo ($post['occupancy']??'')==='2'?'selected':''; ?>>Double (2 persons)</option>
                                <option value="3" <?php echo ($post['occupancy']??'')==='3'?'selected':''; ?>>Triple (3 persons)</option>
                                <option value="4" <?php echo ($post['occupancy']??'')==='4'?'selected':''; ?>>4+ persons</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact Number * (10 digits)</label>
                            <input type="tel" class="form-input" name="contact"
                                   placeholder="9876543210" pattern="[0-9]{10}" maxlength="10" required
                                   value="<?php echo htmlspecialchars($post['contact']??''); ?>">
                            <div class="field-error" id="contactErr"></div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-section-title">✨ Amenities</div>
                    <div class="amenities-grid-form">
                        <?php $amenities=[
                            ['has_wifi','📶','WiFi'],['has_ac','❄️','AC'],['has_meals','🍽️','Meals'],
                            ['has_parking','🚗','Parking'],['has_laundry','👕','Laundry'],['has_security','🔒','Security'],
                            ['has_furnished','🛋️','Furnished'],['has_power_backup','⚡','Power Backup'],
                            ['has_balcony','🌇','Balcony'],['has_cctv','📹','CCTV'],
                            ['has_pets','🐾','Pets OK'],['has_water','💧','Water 24/7']
                        ];
                        foreach ($amenities as [$name,$ico,$label]): ?>
                        <label class="amenity-check">
                            <input type="checkbox" name="<?php echo $name; ?>" <?php echo isset($post[$name])?'checked':''; ?>>
                            <span class="ico"><?php echo $ico; ?></span> <?php echo $label; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn-secondary-form">Reset</button>
                    <button type="submit" class="btn-submit" id="listBtn">
                        <span id="listBtnText">🏠 List My Room</span>
                        <span id="listBtnLoad" style="display:none;">⏳ Listing…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.field-error{color:#c0392b;font-size:.78rem;margin-top:.3rem;min-height:1rem;}
.form-input.invalid,.form-select.invalid{border-color:var(--vermillion)!important;box-shadow:0 0 0 3px rgba(228,61,18,.15)!important;}
</style>

<script>
function validateListForm() {
    let valid = true;

    function check(selector, errId, msg, extraCheck) {
        const el  = document.querySelector(selector);
        const err = document.getElementById(errId);
        if (!el || !err) return;
        el.classList.remove('invalid');
        err.textContent = '';
        const val = el.value ? el.value.trim() : '';
        if (!val || (extraCheck && !extraCheck(val))) {
            err.textContent = msg;
            el.classList.add('invalid');
            valid = false;
        }
    }

    check('input[name="title"]',           'titleErr',   'Room title is required (min 5 chars).', function(v){ return v.length >= 5; });
    check('input[name="monthly_rent"]',    'rentErr',    'Enter a valid rent (₹500 - ₹2,00,000).', function(v){ return parseInt(v) >= 500 && parseInt(v) <= 200000; });
    check('select[name="area"]',           'areaErr',    'Please select an area.');
    check('select[name="room_type"]',      'rtypeErr',   'Please select room type.');
    check('select[name="gender_preference"]','genderErr','Please select gender preference.');
    check('input[name="contact"]',         'contactErr', 'Contact must be exactly 10 digits.', function(v){ return /^[0-9]{10}$/.test(v); });

    if (valid) {
        document.getElementById('listBtnText').style.display = 'none';
        document.getElementById('listBtnLoad').style.display = 'inline';
        document.getElementById('listBtn').disabled = true;
    }
    return valid;
}

// Numbers only for contact
document.querySelector('input[name="contact"]')?.addEventListener('input', function(){
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>
<?php include 'footer.php'; ?>
