<?php
require_once 'db.php';
start_session();
require_login();
require_admin();

$conn = db_connect();

// Handle actions
if (isset($_GET['delete_room'])) {
    $rid = (int)$_GET['delete_room'];
    if ($rid > 0) {
        db_query($conn, "DELETE FROM pr_rooms WHERE id=$1", [$rid]);
    }
    header('Location: admin.php?tab=rooms&msg=' . urlencode('Room deleted successfully.') . '&mtype=info'); exit;
}
if (isset($_GET['delete_user'])) {
    $uid = (int)$_GET['delete_user'];
    if ($uid > 0 && $uid !== (int)get_user_id()) {
        db_query($conn, "DELETE FROM pr_users WHERE id=$1 AND user_type != 'admin'", [$uid]);
    }
    header('Location: admin.php?tab=users&msg=' . urlencode('User deleted.') . '&mtype=info'); exit;
}
if (isset($_GET['toggle_room'])) {
    $rid = (int)$_GET['toggle_room'];
    db_query($conn, "UPDATE pr_rooms SET status = CASE WHEN status='Available' THEN 'Rented' ELSE 'Available' END WHERE id=$1", [$rid]);
    header('Location: admin.php?tab=rooms&msg=' . urlencode('Room status updated.') . '&mtype=success'); exit;
}
if (isset($_GET['toggle_user'])) {
    $uid = (int)$_GET['toggle_user'];
    if ($uid !== (int)get_user_id()) {
        db_query($conn, "UPDATE pr_users SET is_active = NOT is_active WHERE id=$1", [$uid]);
    }
    header('Location: admin.php?tab=users&msg=' . urlencode('User status updated.') . '&mtype=success'); exit;
}

// ── Load all data ──
$stats = get_stats($conn);

$rooms = db_fetch_all(db_query($conn,
    "SELECT r.*, TRIM(u.first_name || ' ' || COALESCE(u.last_name,'')) AS owner_name
     FROM pr_rooms r
     LEFT JOIN pr_users u ON r.user_id = u.id
     ORDER BY r.upload_date DESC"
));

$users = db_fetch_all(db_query($conn,
    "SELECT * FROM pr_users ORDER BY
     CASE user_type WHEN 'admin' THEN 0 WHEN 'owner' THEN 1 ELSE 2 END,
     created_at DESC"
));

$predictions = db_fetch_all(db_query($conn,
    "SELECT p.*, COALESCE(u.first_name,'Guest') AS first_name
     FROM pr_predictions p
     LEFT JOIN pr_users u ON p.user_id = u.id
     ORDER BY p.created_at DESC LIMIT 50"
));

$type_data = db_fetch_all(db_query($conn,
    "SELECT room_type,
            COUNT(*) AS total,
            SUM(CASE WHEN status='Available' THEN 1 ELSE 0 END) AS available,
            ROUND(AVG(monthly_rent)) AS avg_rent
     FROM pr_rooms GROUP BY room_type ORDER BY total DESC"
));

$area_data = db_fetch_all(db_query($conn,
    "SELECT area,
            COUNT(*) AS cnt,
            SUM(CASE WHEN status='Available' THEN 1 ELSE 0 END) AS avail,
            ROUND(AVG(monthly_rent)) AS avg_rent
     FROM pr_rooms GROUP BY area ORDER BY cnt DESC LIMIT 10"
));

$active_tab = in_array($_GET['tab']??'', ['overview','rooms','users','predictions']) ? $_GET['tab'] : 'overview';
$page_title = 'Admin Dashboard - PuneRooms';
include 'header.php';
?>

<div class="page active">
<div class="container" style="padding-top:2rem;padding-bottom:4rem;">

    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 style="font-family:var(--font-display);font-weight:800;font-size:2rem;letter-spacing:-1px;">
                ⚙ Admin <span style="color:var(--vermillion)">Dashboard</span>
            </h1>
            <p style="color:var(--text3);font-size:.88rem;">Manage rooms, users, and platform data</p>
        </div>
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            <div style="font-size:.82rem;color:var(--text3);">
                Logged in as <strong style="color:var(--text)"><?php echo htmlspecialchars(get_user_name()); ?></strong>
                <span style="background:rgba(228,61,18,.1);color:var(--vermillion);border-radius:100px;padding:.15rem .6rem;font-size:.7rem;font-family:var(--font-display);font-weight:700;margin-left:.4rem;">ADMIN</span>
            </div>
            <a href="index.php" class="btn-nav-outline" style="font-size:.78rem;">← Back to Site</a>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="admin-stats-grid" style="margin-bottom:2rem;">
        <div class="admin-stat-card">
            <div class="admin-stat-icon">🏠</div>
            <div class="admin-stat-num"><?php echo $stats['total_rooms']; ?></div>
            <div class="admin-stat-label">Total Rooms</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-icon">✅</div>
            <div class="admin-stat-num" style="color:#16a34a"><?php echo $stats['available_rooms']; ?></div>
            <div class="admin-stat-label">Available</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-icon">🔴</div>
            <div class="admin-stat-num" style="color:var(--vermillion)"><?php echo $stats['rented_rooms']; ?></div>
            <div class="admin-stat-label">Rented</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-icon">👥</div>
            <div class="admin-stat-num"><?php echo $stats['total_users']; ?></div>
            <div class="admin-stat-label">Total Users</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-icon">💰</div>
            <div class="admin-stat-num">₹<?php echo number_format($stats['avg_rent']); ?></div>
            <div class="admin-stat-label">Avg Rent</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-icon">📍</div>
            <div class="admin-stat-num"><?php echo $stats['total_areas']; ?></div>
            <div class="admin-stat-label">Areas</div>
        </div>
    </div>

    <!-- TABS -->
    <div class="admin-tabs">
        <?php $tabs = ['overview'=>'📊 Overview','rooms'=>'🏠 Rooms','users'=>'👥 Users','predictions'=>'🔮 Predictions'];
        foreach ($tabs as $tab => $label): ?>
        <button class="admin-tab <?php echo $active_tab===$tab?'active':''; ?>"
                onclick="switchTab('<?php echo $tab; ?>')"><?php echo $label; ?>
            <?php if ($tab==='rooms'): ?><span class="tab-count"><?php echo count($rooms); ?></span><?php endif; ?>
            <?php if ($tab==='users'): ?><span class="tab-count"><?php echo count($users); ?></span><?php endif; ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- ── OVERVIEW TAB ── -->
    <div class="admin-tab-pane <?php echo $active_tab==='overview'?'active':''; ?>" id="tab-overview">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:1rem;">
            <div class="form-card">
                <div class="form-section-title">🏷 Room Types Breakdown</div>
                <table class="data-table">
                    <thead><tr><th>Type</th><th>Total</th><th>Available</th><th>Avg Rent</th></tr></thead>
                    <tbody>
                        <?php if (empty($type_data)): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--text3);">No rooms yet</td></tr>
                        <?php else: foreach ($type_data as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['room_type']); ?></td>
                            <td><?php echo $r['total']; ?></td>
                            <td><span style="color:#16a34a;font-weight:700;"><?php echo $r['available']; ?></span></td>
                            <td>₹<?php echo number_format((int)$r['avg_rent']); ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-card">
                <div class="form-section-title">📍 Top Areas</div>
                <table class="data-table">
                    <thead><tr><th>Area</th><th>Rooms</th><th>Available</th><th>Avg Rent</th></tr></thead>
                    <tbody>
                        <?php if (empty($area_data)): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--text3);">No data</td></tr>
                        <?php else: foreach ($area_data as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['area']); ?></td>
                            <td><?php echo $r['cnt']; ?></td>
                            <td><span style="color:#16a34a;font-weight:700;"><?php echo $r['avail']; ?></span></td>
                            <td>₹<?php echo number_format((int)$r['avg_rent']); ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick recent activity -->
        <div class="form-card" style="margin-top:1.5rem;">
            <div class="form-section-title">🕐 Recent Rooms (Last 5)</div>
            <table class="data-table">
                <thead><tr><th>Title</th><th>Area</th><th>Rent</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    <?php
                    $recent = array_slice($rooms, 0, 5);
                    if (empty($recent)): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--text3);">No rooms yet</td></tr>
                    <?php else: foreach ($recent as $r): ?>
                    <tr>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($r['title']); ?></td>
                        <td><?php echo htmlspecialchars($r['area']); ?></td>
                        <td>₹<?php echo number_format((int)$r['monthly_rent']); ?></td>
                        <td><span class="room-status-badge <?php echo $r['status']==='Available'?'status-available':'status-rented'; ?>"><?php echo $r['status']; ?></span></td>
                        <td style="color:var(--text3)"><?php echo date('d M Y', strtotime($r['upload_date'])); ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── ROOMS TAB ── -->
    <div class="admin-tab-pane <?php echo $active_tab==='rooms'?'active':''; ?>" id="tab-rooms">
        <div class="admin-toolbar" style="margin-top:1rem;">
            <input type="text" id="roomSearch" placeholder="🔍 Search rooms by title, area, type…"
                   class="filter-input" style="max-width:320px;"
                   oninput="filterTable('roomsTable',this.value)">
            <span style="font-size:.82rem;color:var(--text3);margin-left:auto;">
                <strong><?php echo count($rooms); ?></strong> rooms total
            </span>
        </div>
        <div class="table-container">
            <table class="data-table" id="roomsTable">
                <thead>
                    <tr>
                        <th>#</th><th>Title</th><th>Area</th><th>Type</th>
                        <th>Rent</th><th>Status</th><th>Owner</th><th>Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rooms)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text3);">No rooms in database yet.</td></tr>
                    <?php else: foreach ($rooms as $r): ?>
                    <tr>
                        <td style="color:var(--text3)"><?php echo $r['id']; ?></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($r['title']); ?>">
                            <?php echo htmlspecialchars($r['title']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($r['area']); ?></td>
                        <td><span class="badge" style="font-size:.7rem;"><?php echo htmlspecialchars($r['room_type']); ?></span></td>
                        <td><strong>₹<?php echo number_format((int)$r['monthly_rent']); ?></strong></td>
                        <td>
                            <span class="room-status-badge <?php echo $r['status']==='Available'?'status-available':'status-rented'; ?>">
                                <?php echo $r['status']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($r['owner_name']??'—'); ?></td>
                        <td style="color:var(--text3);white-space:nowrap;"><?php echo date('d M Y', strtotime($r['upload_date'])); ?></td>
                        <td style="white-space:nowrap;">
                            <a href="admin.php?toggle_room=<?php echo $r['id']; ?>&tab=rooms"
                               class="action-btn btn-toggle"
                               onclick="return confirm('Toggle status of this room?')">Toggle</a>
                            <a href="admin.php?delete_room=<?php echo $r['id']; ?>&tab=rooms"
                               class="action-btn btn-delete"
                               onclick="return confirm('Are you sure you want to delete this room?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── USERS TAB ── -->
    <div class="admin-tab-pane <?php echo $active_tab==='users'?'active':''; ?>" id="tab-users">
        <div class="admin-toolbar" style="margin-top:1rem;">
            <input type="text" id="userSearch" placeholder="🔍 Search users by name, email…"
                   class="filter-input" style="max-width:320px;"
                   oninput="filterTable('usersTable',this.value)">
            <span style="font-size:.82rem;color:var(--text3);margin-left:auto;">
                <strong><?php echo count($users); ?></strong> users total
            </span>
        </div>
        <div class="table-container">
            <table class="data-table" id="usersTable">
                <thead>
                    <tr>
                        <th>#</th><th>Name</th><th>Email</th><th>Phone</th>
                        <th>Type</th><th>College</th><th>Status</th><th>Joined</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text3);">No users yet.</td></tr>
                    <?php else: foreach ($users as $u): ?>
                    <tr>
                        <td style="color:var(--text3)"><?php echo $u['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars(trim(($u['first_name']??'').' '.($u['last_name']??''))); ?></strong>
                        </td>
                        <td style="font-size:.8rem;"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td style="color:var(--text3)"><?php echo htmlspecialchars($u['phone']??'—'); ?></td>
                        <td>
                            <span class="badge" style="font-size:.7rem;<?php echo $u['user_type']==='admin'?'background:rgba(228,61,18,.15);color:var(--vermillion);':($u['user_type']==='owner'?'background:rgba(239,177,29,.15);color:#7c5c00;':''); ?>">
                                <?php echo htmlspecialchars($u['user_type']); ?>
                            </span>
                        </td>
                        <td style="font-size:.8rem;color:var(--text3)"><?php echo htmlspecialchars($u['college']??'—'); ?></td>
                        <td>
                            <?php
                            $active = ($u['is_active'] === 't' || $u['is_active'] === true || $u['is_active'] === '1');
                            echo $active
                                ? '<span style="color:#16a34a;font-weight:700;">● Active</span>'
                                : '<span style="color:var(--vermillion);font-weight:700;">○ Inactive</span>';
                            ?>
                        </td>
                        <td style="color:var(--text3);white-space:nowrap;"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                        <td style="white-space:nowrap;">
                            <?php if ($u['user_type'] !== 'admin'): ?>
                            <a href="admin.php?toggle_user=<?php echo $u['id']; ?>&tab=users"
                               class="action-btn btn-toggle"
                               onclick="return confirm('Toggle this user\'s active status?')">Toggle</a>
                            <a href="admin.php?delete_user=<?php echo $u['id']; ?>&tab=users"
                               class="action-btn btn-delete"
                               onclick="return confirm('Delete this user permanently?')">Delete</a>
                            <?php else: ?>
                            <span style="font-size:.7rem;color:var(--text3);font-family:var(--font-display);font-weight:700;">ADMIN</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── PREDICTIONS TAB ── -->
    <div class="admin-tab-pane <?php echo $active_tab==='predictions'?'active':''; ?>" id="tab-predictions">
        <div style="margin-top:1rem;">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr><th>User</th><th>Area</th><th>Room Type</th><th>Sq Ft</th><th>Predicted Rent</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($predictions)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text3);">No predictions yet.</td></tr>
                        <?php else: foreach ($predictions as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['area']); ?></td>
                            <td><span class="badge" style="font-size:.7rem;"><?php echo htmlspecialchars($p['room_type']); ?></span></td>
                            <td><?php echo (int)$p['sqft']; ?> sq ft</td>
                            <td><strong style="color:var(--vermillion);font-size:1.05rem;">₹<?php echo number_format((int)$p['predicted_rent']); ?></strong></td>
                            <td style="color:var(--text3);white-space:nowrap;"><?php echo date('d M Y H:i', strtotime($p['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>

<style>
.tab-count{display:inline-flex;align-items:center;justify-content:center;background:var(--bg2);border:1px solid var(--border);color:var(--text3);border-radius:100px;font-size:.65rem;padding:0 .4rem;margin-left:.3rem;font-family:var(--font-display);font-weight:700;min-width:20px;}
.admin-tab.active .tab-count{background:rgba(228,61,18,.1);color:var(--vermillion);border-color:rgba(228,61,18,.2);}
@media(max-width:700px){
  .admin-stats-grid{grid-template-columns:repeat(2,1fr)!important;}
  #tab-overview > div{grid-template-columns:1fr!important;}
}
</style>

<script>
function switchTab(t) {
    var tabs  = ['overview','rooms','users','predictions'];
    document.querySelectorAll('.admin-tab').forEach(function(b, i){
        b.classList.toggle('active', tabs[i] === t);
    });
    document.querySelectorAll('.admin-tab-pane').forEach(function(p){
        p.classList.remove('active');
    });
    var pane = document.getElementById('tab-' + t);
    if (pane) pane.classList.add('active');
    history.replaceState(null, '', 'admin.php?tab=' + t);
}

function filterTable(tableId, q) {
    q = q.toLowerCase();
    var rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    var count = 0;
    rows.forEach(function(row){
        var match = row.textContent.toLowerCase().includes(q);
        row.style.display = match ? '' : 'none';
        if (match) count++;
    });
}
</script>

<?php include 'footer.php'; ?>
