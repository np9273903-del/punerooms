</main>

<!-- ROOM DETAIL MODAL -->
<div id="roomModal" onclick="if(event.target===this)closeModal()" style="display:none;position:fixed;inset:0;z-index:4000;background:rgba(0,0,0,.75);backdrop-filter:blur(8px);overflow-y:auto;padding:2rem 1rem;">
  <div style="background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius);max-width:580px;margin:0 auto;padding:2rem;position:relative;box-shadow:0 32px 80px rgba(0,0,0,.4);" id="modalContent">
    <button onclick="closeModal()" style="position:absolute;top:1rem;right:1rem;background:var(--bg2);border:1px solid var(--border);border-radius:50%;width:34px;height:34px;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;">✕</button>
    <div id="modalBody"></div>
  </div>
</div>

<button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>
<div class="toast-container" id="toastContainer"></div>

<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand-mark">🏠</div>
        <div class="footer-brand-name">Pune<span>Rooms</span></div>
        <p class="footer-about">Pune's trusted platform for student accommodation. Find rooms, PGs & shared spaces near your college. Free for students, always.</p>
      </div>
      <div class="footer-col">
        <div class="footer-col-title">Popular Areas</div>
        <ul><?php foreach(['Kothrud','Baner','Aundh','Shivaji Nagar','Koregaon Park','Wakad'] as $a):?><li><a href="index.php?area=<?php echo urlencode($a);?>"><?php echo $a;?></a></li><?php endforeach;?></ul>
      </div>
      <div class="footer-col">
        <div class="footer-col-title">Navigate</div>
        <ul>
          <li><a href="index.php">Find Rooms</a></li>
          <?php if(is_logged_in()):?>
          <li><a href="saved.php">Saved Rooms</a></li>
          <li><a href="predict.php">Predict Rent</a></li>
          <?php if(is_owner()):?><li><a href="list.php">List a Room</a></li><?php endif;?>
          <?php endif;?>
        </ul>
      </div>
      <div class="footer-col">
        <div class="footer-col-title">Contact</div>
        <p style="font-size:.83rem;color:rgba(255,255,255,.4);">info@punerooms.com</p>
        <p style="margin-top:.75rem;font-size:.72rem;color:rgba(255,255,255,.3);">© 2025 PuneRooms</p>
      </div>
    </div>
    <div class="footer-bottom">
      <span>Built with ❤ for students of Pune</span>
      <span class="footer-bottom-badge">PHP + PostgreSQL</span>
    </div>
  </div>
</footer>

<script src="main.js"></script>
</body>
</html>
