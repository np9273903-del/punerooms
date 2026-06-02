<?php
require_once 'db.php';
start_session();
if (!is_logged_in()) { header('Location: login.php'); exit; }
$conn    = db_connect();
$user_id = get_user_id();
if (isset($_GET['save_room'])) {
    $room_id = (int)$_GET['save_room'];
    if ($room_id > 0) {
        $check = db_query($conn, "SELECT id FROM pr_saved WHERE user_id=$1 AND room_id=$2", [$user_id, $room_id]);
        if ($check && pg_num_rows($check) > 0) { db_query($conn, "DELETE FROM pr_saved WHERE user_id=$1 AND room_id=$2", [$user_id, $room_id]); $msg = 'Room removed from saved.'; }
        else { db_query($conn, "INSERT INTO pr_saved (user_id,room_id) VALUES ($1,$2) ON CONFLICT DO NOTHING", [$user_id, $room_id]); $msg = 'Room saved! ❤️'; }
    } else { $msg = 'Invalid room.'; }
    $qs = http_build_query(array_filter(['search'=>$_GET['search']??'','area'=>$_GET['area']??'','room_type'=>$_GET['room_type']??'','gender'=>$_GET['gender']??'','min_rent'=>$_GET['min_rent']??'','max_rent'=>$_GET['max_rent']??'','sort'=>$_GET['sort']??'']));
    header('Location: index.php?'.($qs?$qs.'&':'').'msg='.urlencode($msg).'&mtype=success'); exit;
}
$search    = trim($_GET['search']    ?? '');
$area      = trim($_GET['area']      ?? '');
$room_type = trim($_GET['room_type'] ?? '');
$gender    = trim($_GET['gender']    ?? '');
$min_rent  = (int)($_GET['min_rent'] ?? 0);
$max_rent  = (int)($_GET['max_rent'] ?? 0);
$sort      = trim($_GET['sort']      ?? 'newest');
$rooms     = get_filtered_rooms($conn, $search, $min_rent, $max_rent, $room_type, $gender, $area, $sort);
$saved_ids = get_saved_room_ids($conn, $user_id);
$stats     = get_stats($conn);
$page_title = 'Find Rooms - PuneRooms';
include 'header.php';
?>

<div class="hero">
  <img src="images/hero_bg.jpg" alt="Modern apartment Pune" class="hero-bg-img" loading="eager">
  <div class="hero-overlay"></div>
  <div class="container">
    <div class="hero-inner">
      <div class="hero-left">
        <div class="hero-badge"><div class="dot"></div> Pune's #1 Student Platform</div>
        <h1>Find Your<br><span class="line-accent">Perfect Room</span><br>in Pune</h1>
        <p class="hero-sub">Verified rooms, PGs, and shared apartments near your college. Browse free, contact owners directly.</p>
        <div class="hero-actions">
          <a href="#room-results" class="btn-primary">🔍 Browse Rooms</a>
          <a href="predict.php" class="btn-ghost">🔮 Predict Rent</a>
        </div>
        <form method="GET" action="index.php" class="hero-search">
          <span style="font-size:1.1rem;flex-shrink:0;">🔍</span>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search area, college, room type…">
          <button type="submit" class="hero-search-btn">Search</button>
        </form>
        <div style="display:flex;gap:1.5rem;margin-top:1.5rem;flex-wrap:wrap;">
          <div style="display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.6);font-size:.78rem;"><span style="color:#4ade80;">✓</span> Free for students</div>
          <div style="display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.6);font-size:.78rem;"><span style="color:#4ade80;">✓</span> No broker fees</div>
          <div style="display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.6);font-size:.78rem;"><span style="color:#4ade80;">✓</span> Direct contact</div>
        </div>
      </div>
      <div class="hero-visual">
        <div class="hv-img"><img src="images/room_living1.jpg" alt="Living room" loading="eager"><div class="hv-badge">🏡 PG Available</div></div>
        <div class="hv-img"><img src="images/room_bedroom.jpg" alt="Bedroom" loading="eager"></div>
        <div class="hv-img"><img src="images/room_apartment.jpg" alt="Apartment" loading="lazy"></div>
        <div class="hero-card-float" style="top:-12px;right:-18px;">
          <div class="hfc-label">Avg. Rent</div>
          <div class="hfc-val">₹<?php echo number_format($stats['avg_rent']); ?></div>
          <div class="hfc-sub">Per month</div>
        </div>
        <div class="hero-card-float" style="bottom:-12px;left:-18px;">
          <div class="hfc-label">Available Now</div>
          <div class="hfc-val"><?php echo $stats['available_rooms']; ?></div>
          <div class="hfc-sub">Live listings</div>
        </div>
      </div>
    </div>
  </div>
  <div class="hero-scroll"><div class="hero-scroll-line"></div>Scroll</div>
</div>

<div class="hero-stats-strip">
  <div class="container">
    <div class="hero-stats-inner">
      <div class="hero-stat-item"><div class="hero-stat-num"><?php echo $stats['available_rooms']; ?>+</div><div class="hero-stat-label">Available Rooms</div></div>
      <div class="hero-stat-item"><div class="hero-stat-num">₹<?php echo number_format($stats['avg_rent']); ?></div><div class="hero-stat-label">Avg Monthly Rent</div></div>
      <div class="hero-stat-item"><div class="hero-stat-num"><?php echo $stats['total_areas']; ?>+</div><div class="hero-stat-label">Areas Covered</div></div>
      <div class="hero-stat-item"><div class="hero-stat-num"><?php echo $stats['total_users']; ?>+</div><div class="hero-stat-label">Happy Students</div></div>
      <div class="hero-stat-item"><div class="hero-stat-num">100%</div><div class="hero-stat-label">Free to Use</div></div>
    </div>
  </div>
</div>

<div class="container">
  <div class="type-gallery-section">
    <div class="type-gallery-header">
      <div class="sec-title">Browse by <span>Room Type</span></div>
      <p class="sec-subtitle">Find the perfect space that matches your lifestyle and budget</p>
    </div>
    <div class="type-gallery-grid">
      <?php
      $tg=[['Single Room','images/room_bedroom.jpg','🛏','Your private sanctuary'],['Shared Room','images/room_shared.jpg','👥','Budget-friendly & social'],['PG','images/room_living1.jpg','🏡','Meals & amenities included'],['1BHK','images/room_1bhk.jpg','🏢','Spacious & independent'],['Studio','images/room_studio.jpg','🎨','Modern compact living']];
      foreach($tg as [$tp,$im,$ic,$ds]):?>
      <a href="index.php?room_type=<?php echo urlencode($tp);?>" class="type-gallery-card reveal">
        <div class="tgc-img"><img src="<?php echo $im;?>" alt="<?php echo $tp;?>" loading="lazy"><div class="tgc-overlay"></div>
          <div class="tgc-text"><div class="tgc-ico"><?php echo $ic;?></div><div class="tgc-name"><?php echo $tp;?></div><div class="tgc-desc"><?php echo $ds;?></div></div>
        </div>
      </a>
      <?php endforeach;?>
    </div>
  </div>
</div>

<div class="categories-strip">
  <div class="container">
    <div class="categories-scroll">
      <a href="index.php" class="cat-chip <?php echo (!$room_type&&!$gender)?'active-chip':'';?>">🏠 All</a>
      <?php foreach(['Single Room'=>'🛏','Shared Room'=>'👥','PG'=>'🏡','1BHK'=>'🏢','Studio'=>'🎨'] as $t=>$ico):?>
      <a href="index.php?room_type=<?php echo urlencode($t);?>" class="cat-chip <?php echo $room_type===$t?'active-chip':'';?>"><?php echo $ico.' '.$t;?></a>
      <?php endforeach;?>
      <a href="index.php?gender=Female" class="cat-chip <?php echo $gender==='Female'?'active-chip':'';?>">👩 Girls Only</a>
      <a href="index.php?gender=Male" class="cat-chip <?php echo $gender==='Male'?'active-chip':'';?>">👨 Boys Only</a>
      <a href="index.php?max_rent=8000" class="cat-chip">💰 Under ₹8K</a>
    </div>
  </div>
</div>

<div class="container" id="room-results">
  <div class="content-layout">
    <aside class="sidebar">
      <form method="GET" action="index.php">
        <div style="font-family:var(--font-display);font-weight:800;font-size:.95rem;margin-bottom:1.25rem;">🔍 Filter Rooms</div>
        <div class="filter-group"><label class="filter-label">Search</label><input type="text" class="filter-input" name="search" value="<?php echo htmlspecialchars($search);?>" placeholder="Area or college…"></div>
        <div class="filter-group"><label class="filter-label">Room Type</label><select class="filter-select" name="room_type"><option value="">All Types</option><?php foreach(['Single Room','Shared Room','PG','1BHK','Studio'] as $t):?><option value="<?php echo $t;?>" <?php echo $room_type===$t?'selected':'';?>><?php echo $t;?></option><?php endforeach;?></select></div>
        <div class="filter-group"><label class="filter-label">Gender</label><select class="filter-select" name="gender"><option value="">Any</option><option value="Male" <?php echo $gender==='Male'?'selected':'';?>>Male Only</option><option value="Female" <?php echo $gender==='Female'?'selected':'';?>>Female Only</option><option value="Co-ed" <?php echo $gender==='Co-ed'?'selected':'';?>>Co-ed</option></select></div>
        <div class="filter-group"><label class="filter-label">Rent Range (₹)</label><div class="price-row"><input type="number" class="filter-input" name="min_rent" value="<?php echo $min_rent?:'';?>" placeholder="Min"><input type="number" class="filter-input" name="max_rent" value="<?php echo $max_rent?:'';?>" placeholder="Max"></div></div>
        <div class="filter-group"><label class="filter-label">Area</label><select class="filter-select" name="area"><option value="">All Areas</option><?php foreach(['Kothrud','Karve Nagar','Warje','Baner','Aundh','Shivaji Nagar','Camp','Koregaon Park','Viman Nagar','Hadapsar','Katraj','Sinhgad Road','Wakad','Hinjewadi','Pimpri'] as $a):?><option value="<?php echo $a;?>" <?php echo $area===$a?'selected':'';?>><?php echo $a;?></option><?php endforeach;?></select></div>
        <div class="filter-group"><label class="filter-label">Sort By</label><select class="filter-select" name="sort"><option value="newest" <?php echo $sort==='newest'?'selected':'';?>>Newest First</option><option value="rent-low" <?php echo $sort==='rent-low'?'selected':'';?>>Rent: Low → High</option><option value="rent-high" <?php echo $sort==='rent-high'?'selected':'';?>>Rent: High → Low</option></select></div>
        <hr class="filter-divider">
        <button type="submit" class="btn-apply">Apply Filters</button>
        <a href="index.php" class="btn-clear-filter" style="display:block;text-align:center;margin-top:.5rem;text-decoration:none;">Clear All</a>
      </form>
    </aside>
    <div>
      <div class="results-header">
        <div class="results-count"><strong><?php echo count($rooms);?></strong> room<?php echo count($rooms)!==1?'s':'';?> found<?php echo $search?' for "'.htmlspecialchars($search).'"':'';?></div>
        <?php if($room_type||$gender||$min_rent||$max_rent||$area||$search):?><a href="index.php" style="font-size:.78rem;color:var(--vermillion);font-family:var(--font-display);font-weight:700;">✕ Clear filters</a><?php endif;?>
      </div>
      <?php if(empty($rooms)):?>
      <div class="empty-state"><div class="ico">🏠</div><h3>No rooms found</h3><p>Try adjusting your filters or <a href="index.php" style="color:var(--vermillion)">clear all filters</a>.</p></div>
      <?php else:?>
      <div class="rooms-grid">
        <?php
        $ri=['Single Room'=>'images/room_bedroom.jpg','Shared Room'=>'images/room_shared.jpg','PG'=>'images/room_living1.jpg','1BHK'=>'images/room_1bhk.jpg','Studio'=>'images/room_studio.jpg'];
        $rx=['images/room_minimal.jpg','images/room_rustic.jpg','images/room_premium.jpg','images/room_apartment.jpg','images/room_white.jpg','images/room_luxury.jpg','images/room_study.jpg','images/room_boho.jpg','images/room_boho2.jpg'];
        $ei=0;
        foreach($rooms as $room):
          $is_saved=in_array($room['id'],$saved_ids);
          $ci=$ri[$room['room_type']]??$rx[$ei++%count($rx)];
        ?>
        <div class="room-card reveal">
          <div class="room-card-img">
            <img src="<?php echo $ci;?>" alt="<?php echo htmlspecialchars($room['title']);?>" loading="lazy" onerror="this.parentNode.classList.add('img-fallback');this.remove();">
            <div class="room-card-img-overlay"></div>
            <div class="room-status-badge <?php echo $room['status']==='Available'?'status-available':'status-rented';?>"><?php echo $room['status'];?></div>
            <a href="index.php?save_room=<?php echo $room['id'];?>&<?php echo http_build_query(array_filter(['search'=>$search,'area'=>$area,'room_type'=>$room_type,'gender'=>$gender,'min_rent'=>$min_rent?:null,'max_rent'=>$max_rent?:null,'sort'=>$sort]));?>" class="room-save-btn <?php echo $is_saved?'saved':'';?>" title="<?php echo $is_saved?'Unsave':'Save';?>"><?php echo $is_saved?'❤️':'🤍';?></a>
            <div class="room-type-img-tag"><?php echo htmlspecialchars($room['room_type']);?> · <?php echo htmlspecialchars($room['gender_preference']);?></div>
          </div>
          <div class="room-card-body">
            <div class="room-type-tag">📍 <?php echo htmlspecialchars($room['area']);?><?php echo $room['nearby_college']?' · '.htmlspecialchars($room['nearby_college']):'';?></div>
            <div class="room-title"><?php echo htmlspecialchars($room['title']);?></div>
            <div class="room-rent">₹<?php echo number_format($room['monthly_rent']);?><span>/month</span></div>
            <div class="room-deposit">Deposit: ₹<?php echo number_format($room['security_deposit']);?></div>
            <div class="badges-row"><?php echo amenity_badges($room);?></div>
            <div class="room-meta"><span class="room-rating"><?php echo stars($room['rating']??0);?></span><span>👥 <?php echo $room['occupancy'];?> person<?php echo $room['occupancy']>1?'s':'';?></span></div>
          </div>
          <div class="room-card-footer">
            <a href="tel:<?php echo htmlspecialchars($room['contact']);?>" class="btn-contact">📞 Call Owner</a>
            <button class="btn-view" onclick='showRoomDetail(<?php echo json_encode($room);?>)'>View Details</button>
          </div>
        </div>
        <?php endforeach;?>
      </div>
      <?php endif;?>
    </div>
  </div>
</div>

<div class="why-section">
  <div class="container">
    <div style="text-align:center;"><div class="sec-title">Why <span>PuneRooms?</span></div><p class="sec-subtitle">The smartest way for Pune students to find their ideal home</p></div>
    <div class="why-grid">
      <div class="why-card reveal"><div class="why-card-img"><img src="images/room_premium.jpg" alt="Verified" loading="lazy"><div class="why-card-img-overlay"></div></div><div class="why-card-body"><div class="why-card-icon">✅</div><div class="why-card-title">Verified Listings</div><div class="why-card-desc">Every owner registers before listing. All rooms are real, owner-verified, and up-to-date.</div></div></div>
      <div class="why-card reveal"><div class="why-card-img"><img src="images/room_1bhk.jpg" alt="Direct" loading="lazy"><div class="why-card-img-overlay"></div></div><div class="why-card-body"><div class="why-card-icon">📞</div><div class="why-card-title">Direct Owner Contact</div><div class="why-card-desc">Zero brokers, zero commission. Call the owner directly from any listing page.</div></div></div>
      <div class="why-card reveal"><div class="why-card-img"><img src="images/room_apartment.jpg" alt="Free" loading="lazy"><div class="why-card-img-overlay"></div></div><div class="why-card-body"><div class="why-card-icon">💸</div><div class="why-card-title">100% Free Forever</div><div class="why-card-desc">PuneRooms is completely free for students. No hidden charges. No subscriptions. Ever.</div></div></div>
    </div>
  </div>
</div>

<div class="container">
  <div class="faq-section">
    <div style="text-align:center;margin-bottom:2rem;"><div class="sec-title">❓ Frequently <span>Asked</span></div></div>
    <?php foreach([['How do I book a room?','Browse listings, click Call Owner, visit the room, verify details, then pay deposit directly.'],['Is PuneRooms free?','Yes — 100% free for students to browse and contact owners. No hidden charges ever.'],['How do I list my room?','Create an Owner account, go to List Room, fill in details and submit. Your listing goes live immediately.'],['Are listings verified?','Owners register before listing. Always visit in person and verify before paying any deposit.'],['Can I save favourite rooms?','Yes! Click the 🤍 heart on any card. Access all saved rooms from the Saved page in navigation.']] as $faq):?>
    <div class="faq-item">
      <div class="faq-question" onclick="this.closest('.faq-item').classList.toggle('open')"><span><?php echo $faq[0];?></span><span class="faq-arrow">▼</span></div>
      <div class="faq-answer"><p><?php echo $faq[1];?></p></div>
    </div>
    <?php endforeach;?>
  </div>
</div>

<?php include 'footer.php'; ?>
