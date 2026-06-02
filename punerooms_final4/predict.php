<?php
require_once 'db.php';
start_session();
require_login();

$conn      = db_connect();
$user_id   = get_user_id();
$predicted = null;
$history   = [];

function predict_rent($area, $room_type, $sqft, $amenities) {
    $base = [
        'Single Room' => 6000, 'Shared Room' => 4000,
        'PG'          => 8000, '1BHK'        => 12000, 'Studio' => 10000,
    ][$room_type] ?? 6000;

    $area_mult = [
        'Koregaon Park' => 1.6, 'Baner'         => 1.4, 'Viman Nagar'   => 1.35,
        'Aundh'         => 1.3, 'Hinjewadi'     => 1.25,'Wakad'         => 1.2,
        'Shivaji Nagar' => 1.2, 'Kothrud'       => 1.1, 'Karve Nagar'   => 1.05,
        'Pimpri'        => 0.9, 'Katraj'        => 0.85,'Hadapsar'      => 0.95,
        'Camp'          => 1.1, 'Warje'         => 1.0, 'Sinhgad Road'  => 0.9,
    ][$area] ?? 1.0;

    $sqft        = max(50, min(1000, (int)$sqft));
    $sqft_factor = 1 + ($sqft - 150) * 0.0015;

    $bonuses = [
        'wifi'=>300,'ac'=>1000,'meals'=>2000,'furnished'=>1500,'parking'=>500,
        'laundry'=>400,'security'=>300,'power_backup'=>400,'balcony'=>500,
        'cctv'=>200,'water'=>150,'pets'=>200
    ];
    $amenity_bonus = 0;
    foreach ($amenities as $a) { $amenity_bonus += $bonuses[$a] ?? 0; }

    $p     = round(($base * $area_mult * $sqft_factor + $amenity_bonus) / 100) * 100;
    $noise = $p * 0.03 * (rand(-100,100)/100);
    return (int)round(($p + $noise) / 100) * 100;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $area      = trim($_POST['area']      ?? '');
    $room_type = trim($_POST['room_type'] ?? '');
    $sqft      = (int)($_POST['sqft']     ?? 150);
    $amenities = array_map('trim', $_POST['amenities'] ?? []);

    if ($area && $room_type) {
        $predicted        = predict_rent($area, $room_type, $sqft, $amenities);
        $amenities_json   = json_encode($amenities);
        db_query($conn,
            "INSERT INTO pr_predictions (user_id, area, room_type, sqft, amenities, predicted_rent)
             VALUES ($1,$2,$3,$4,$5,$6)",
            [$user_id, $area, $room_type, $sqft, $amenities_json, $predicted]
        );
    }
}

$res     = db_query($conn,
    "SELECT * FROM pr_predictions WHERE user_id=$1 ORDER BY created_at DESC LIMIT 5",
    [$user_id]
);
$history = db_fetch_all($res);

$page_title = 'Predict Rent - PuneRooms';
include 'header.php';
?>

<div class="page active">
    <div class="container">
        <div class="page-header">
            <img src="images/room_luxury.jpg" alt="" class="page-header-bg">
            <h1>🔮 Predict <span class="page-header-accent">Rent</span></h1>
            <p>Get an estimated monthly rent based on your preferences</p>
        </div>

        <div class="predict-card">
            <form method="POST" action="predict.php">
                <div class="form-section-title">📍 Location & Room Details</div>
                <div class="form-row" style="margin-bottom:1rem;">
                    <div class="form-group">
                        <label class="form-label">Area *</label>
                        <select class="form-select" name="area" required>
                            <option value="">Select Area</option>
                            <?php foreach (['Koregaon Park','Baner','Viman Nagar','Aundh','Wakad','Hinjewadi','Shivaji Nagar','Kothrud','Karve Nagar','Camp','Hadapsar','Pimpri','Katraj','Warje','Sinhgad Road'] as $a): ?>
                            <option value="<?php echo $a; ?>" <?php echo ($_POST['area']??'')===$a?'selected':''; ?>><?php echo $a; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room Type *</label>
                        <select class="form-select" name="room_type" required>
                            <option value="">Select Type</option>
                            <?php foreach (['Single Room','Shared Room','PG','1BHK','Studio'] as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo ($_POST['room_type']??'')===$t?'selected':''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Approximate Size (sq ft)</label>
                    <input type="range" name="sqft" min="50" max="600" step="10"
                           value="<?php echo (int)($_POST['sqft']??150); ?>"
                           class="form-input" style="padding:.4rem;"
                           oninput="document.getElementById('sqft_val').textContent=this.value+' sq ft'">
                    <div style="text-align:right;font-size:.82rem;color:var(--text3);margin-top:.25rem;" id="sqft_val">
                        <?php echo (int)($_POST['sqft']??150); ?> sq ft
                    </div>
                </div>

                <div class="form-section-title" style="margin-top:1.5rem;">✨ Select Amenities</div>
                <?php
                $sel_amenities = $_POST['amenities'] ?? [];
                $am_list = [
                    ['wifi','📶','WiFi'],['ac','❄️','AC'],['meals','🍽️','Meals'],
                    ['furnished','🛋️','Furnished'],['parking','🚗','Parking'],['laundry','👕','Laundry'],
                    ['security','🔒','Security'],['power_backup','⚡','Power Backup'],
                    ['balcony','🌇','Balcony'],['cctv','📹','CCTV'],
                    ['water','💧','Water 24/7'],['pets','🐾','Pets OK']
                ];
                ?>
                <div class="amenities-grid-form">
                    <?php foreach ($am_list as [$val,$ico,$label]): ?>
                    <label class="amenity-check">
                        <input type="checkbox" name="amenities[]" value="<?php echo $val; ?>"
                               <?php echo in_array($val,$sel_amenities)?'checked':''; ?>>
                        <span><?php echo $ico; ?></span> <?php echo $label; ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">🔮 Predict My Rent</button>
                </div>
            </form>

            <?php if ($predicted !== null): ?>
            <div class="predict-result">
                <div style="font-size:.85rem;color:var(--text3);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.8px;font-family:var(--font-display);font-weight:700;">
                    Estimated Monthly Rent
                </div>
                <div class="predict-result-num">₹<?php echo number_format($predicted); ?></div>
                <div class="predict-result-label">
                    for <?php echo htmlspecialchars($_POST['room_type']); ?> in <?php echo htmlspecialchars($_POST['area']); ?>
                </div>
                <div style="margin-top:1rem;font-size:.78rem;color:var(--text3);">
                    ⚠️ This is an estimate. Actual rents may vary based on amenities, floor, condition, and negotiation.
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($history)): ?>
        <div class="predict-history">
            <div class="predict-history-title">📜 Your Recent Predictions</div>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Area</th><th>Room Type</th><th>Sq Ft</th><th>Predicted Rent</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($h['area']); ?></td>
                            <td><?php echo htmlspecialchars($h['room_type']); ?></td>
                            <td><?php echo (int)$h['sqft']; ?></td>
                            <td><strong style="color:var(--vermillion)">₹<?php echo number_format((int)$h['predicted_rent']); ?></strong></td>
                            <td style="color:var(--text3)"><?php echo date('d M H:i', strtotime($h['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div style="margin-top:2rem;padding:1.5rem;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);">
            <div class="form-section-title">ℹ️ How Prediction Works</div>
            <p style="font-size:.88rem;color:var(--text2);line-height:1.7;">
                Our prediction uses a formula based on: <strong>base rent</strong> by room type,
                <strong>area demand multiplier</strong>, <strong>size factor</strong>, and
                <strong>amenity bonuses</strong>. Premium areas like Koregaon Park and Baner command
                higher rents. Amenities like AC, Meals, and Furnishing significantly increase the estimate.
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const slider = document.querySelector('input[name="sqft"]');
    if (slider) document.getElementById('sqft_val').textContent = slider.value + ' sq ft';
});
</script>
<?php include 'footer.php'; ?>
