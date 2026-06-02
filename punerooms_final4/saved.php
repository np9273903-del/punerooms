<?php
require_once 'db.php';
start_session();
require_login();

$conn    = db_connect();
$user_id = get_user_id();

if (isset($_GET['unsave'])) {
    $room_id = (int)$_GET['unsave'];
    if ($room_id > 0) {
        db_query($conn, "DELETE FROM pr_saved WHERE user_id=$1 AND room_id=$2", [$user_id, $room_id]);
    }
    header('Location: saved.php?msg=Room+removed+from+saved&mtype=info');
    exit;
}

$sql         = "SELECT r.* FROM pr_rooms r JOIN pr_saved s ON r.id=s.room_id WHERE s.user_id=$1 ORDER BY s.saved_at DESC";
$saved_rooms = db_fetch_all(db_query($conn, $sql, [$user_id]));

$page_title = 'Saved Rooms - PuneRooms';
include 'header.php';
?>
<div class="page active">
    <div class="container">
        <div class="page-header">
            <img src="images/room_boho.jpg" alt="" class="page-header-bg">
            <h1>❤ Saved <span class="page-header-accent">Rooms</span></h1>
            <p>Your favourite listings in one place</p>
        </div>

        <?php if (empty($saved_rooms)): ?>
            <div class="empty-state">
                <div class="ico">💔</div>
                <h3>No saved rooms yet</h3>
                <p><a href="index.php" style="color:var(--vermillion)">Browse rooms</a> and click 🤍 to save them here.</p>
            </div>
        <?php else: ?>
            <div style="margin-bottom:1rem;color:var(--text3);font-size:.88rem;">
                <strong><?php echo count($saved_rooms); ?></strong> saved room<?php echo count($saved_rooms)!==1?'s':''; ?>
            </div>
            <div class="rooms-grid">
                <?php
                $room_imgs = [
                    'Single Room' => 'images/room_bedroom.jpg',
                    'Shared Room' => 'images/room_shared.jpg',
                    'PG'          => 'images/room_living1.jpg',
                    '1BHK'        => 'images/room_1bhk.jpg',
                    'Studio'      => 'images/room_studio.jpg',
                ];
                $fallback_imgs = ['images/room_minimal.jpg','images/room_rustic.jpg','images/room_premium.jpg','images/room_apartment.jpg'];
                $fi = 0;
                foreach ($saved_rooms as $room):
                    $card_img = $room_imgs[$room['room_type']] ?? $fallback_imgs[$fi++ % count($fallback_imgs)];
                ?>
                <div class="room-card reveal">
                    <div class="room-card-img">
                        <img src="<?php echo $card_img; ?>"
                             alt="<?php echo htmlspecialchars($room['title']); ?>"
                             loading="lazy"
                             onerror="this.parentNode.classList.add('img-fallback');this.style.display='none';">
                        <div class="room-card-img-overlay"></div>
                        <div class="room-status-badge <?php echo $room['status']==='Available'?'status-available':'status-rented'; ?>"><?php echo $room['status']; ?></div>
                        <a href="saved.php?unsave=<?php echo $room['id']; ?>" class="room-save-btn saved" title="Remove from saved">❤️</a>
                        <div class="room-type-img-tag"><?php echo htmlspecialchars($room['room_type']); ?></div>
                    </div>
                    <div class="room-card-body">
                        <div class="room-type-tag"><?php echo htmlspecialchars($room['room_type']); ?> · <?php echo htmlspecialchars($room['gender_preference']); ?></div>
                        <div class="room-title"><?php echo htmlspecialchars($room['title']); ?></div>
                        <div class="room-location">📍 <?php echo htmlspecialchars($room['area']); ?></div>
                        <div class="room-rent">₹<?php echo number_format($room['monthly_rent']); ?><span>/month</span></div>
                        <div class="room-deposit">Deposit: ₹<?php echo number_format($room['security_deposit']); ?></div>
                        <div class="badges-row"><?php echo amenity_badges($room); ?></div>
                        <div class="room-meta">
                            <span class="room-rating"><?php echo stars($room['rating']??0); ?></span>
                            <span>👥 <?php echo $room['occupancy']; ?> person<?php echo $room['occupancy']>1?'s':''; ?></span>
                        </div>
                    </div>
                    <div class="room-card-footer">
                        <a href="tel:<?php echo htmlspecialchars($room['contact']); ?>" class="btn-contact">📞 Contact</a>
                        <a href="saved.php?unsave=<?php echo $room['id']; ?>" class="btn-view" style="text-align:center"
                           onclick="return confirm('Remove this room from saved?')">Remove</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
