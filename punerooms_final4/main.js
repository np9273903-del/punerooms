// ══════════════════════════════════════════════
//  PuneRooms — Main JavaScript
// ══════════════════════════════════════════════

// ── THEME ──
(function(){
    var t = localStorage.getItem('pr_theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
})();

function setTheme(t){
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('pr_theme', t);
    updateThemePickerActive(t);
    closeThemePicker();
}

function updateThemePickerActive(t) {
    document.querySelectorAll('.tpick-btn').forEach(function(btn){
        btn.classList.toggle('active', btn.getAttribute('data-theme') === t);
    });
}

function toggleThemePicker(){
    var popup = document.getElementById('themePickerPopup');
    var btn   = document.getElementById('themeDotsBtn');
    if (!popup) return;
    var isOpen = popup.classList.contains('open');
    popup.classList.toggle('open', !isOpen);
    btn.classList.toggle('open', !isOpen);
}

function closeThemePicker(){
    var popup = document.getElementById('themePickerPopup');
    var btn   = document.getElementById('themeDotsBtn');
    if (popup) popup.classList.remove('open');
    if (btn)   btn.classList.remove('open');
}

// Close picker when clicking outside
document.addEventListener('click', function(e){
    var wrap = document.getElementById('themePickerWrap');
    if (wrap && !wrap.contains(e.target)) closeThemePicker();
});

// Set active on load
document.addEventListener('DOMContentLoaded', function(){
    var t = localStorage.getItem('pr_theme') || 'light';
    updateThemePickerActive(t);
});

// Legacy toggle support
function toggleTheme(){
    var now = document.documentElement.getAttribute('data-theme');
    setTheme(now === 'dark' ? 'light' : 'dark');
}

// ── SCROLL ──
window.addEventListener('scroll', function(){
    const pct = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
    const scrollBar = document.getElementById('scrollBar');
    if (scrollBar) scrollBar.style.width = pct + '%';
    
    const btt = document.getElementById('backToTop');
    if (btt) {
        if (window.scrollY > 400) btt.classList.add('visible');
        else btt.classList.remove('visible');
    }
    
    document.querySelectorAll('.reveal').forEach(function(el){
        if (el.getBoundingClientRect().top < window.innerHeight - 50) {
            el.classList.add('visible');
        }
    });
});

// ── MOBILE NAV ──
function toggleMobileNav(){
    const n = document.getElementById('mobileNav');
    const h = document.getElementById('hamburger');
    if (n && h) { n.classList.toggle('open'); h.classList.toggle('open'); }
}

// ── TOAST ──
function showToast(msg, type){
    type = type || 'success';
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.textContent = msg;
    container.appendChild(t);
    setTimeout(function(){
        t.style.opacity = '0';
        t.style.transform = 'translateX(40px)';
        t.style.transition = 'all .3s';
        setTimeout(function(){ t.remove(); }, 300);
    }, 3000);
}

// ── ROOM DETAIL MODAL ──
function isTrueVal(v) { return v === 't' || v === 'true' || v === '1' || v === true; }

function showRoomDetail(room){
    const amenities = [];
    if (isTrueVal(room.has_wifi)) amenities.push('📶 WiFi');
    if (isTrueVal(room.has_ac)) amenities.push('❄️ AC');
    if (isTrueVal(room.has_meals)) amenities.push('🍽️ Meals');
    if (isTrueVal(room.has_furnished)) amenities.push('🛋️ Furnished');
    if (isTrueVal(room.has_parking)) amenities.push('🚗 Parking');
    if (isTrueVal(room.has_laundry)) amenities.push('👕 Laundry');
    if (isTrueVal(room.has_security)) amenities.push('🔒 Security');
    if (isTrueVal(room.has_power_backup)) amenities.push('⚡ Power Backup');
    if (isTrueVal(room.has_balcony)) amenities.push('🌇 Balcony');
    if (isTrueVal(room.has_cctv)) amenities.push('📹 CCTV');
    if (isTrueVal(room.has_water)) amenities.push('💧 Water 24/7');
    if (isTrueVal(room.has_pets)) amenities.push('🐾 Pets OK');

    const modalBody = document.getElementById('modalBody');
    if (!modalBody) return;
    modalBody.innerHTML = `
        <div style="font-family:var(--font-display);font-weight:800;font-size:1.3rem;margin-bottom:.5rem;">${room.title}</div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
            <span class="badge">${room.room_type}</span>
            <span class="badge">${room.gender_preference}</span>
            <span class="room-status-badge ${room.status==='Available'?'status-available':'status-rented'}">${room.status}</span>
        </div>
        <div style="font-size:.9rem;color:var(--text2);line-height:1.7;margin-bottom:1.25rem;">${room.description || 'No description provided.'}</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem;font-size:.88rem;">
            <div><strong style="color:var(--text3);font-size:.75rem;text-transform:uppercase;">Monthly Rent</strong><br><strong style="font-size:1.4rem;font-family:var(--font-display);">₹${Number(room.monthly_rent).toLocaleString()}</strong></div>
            <div><strong style="color:var(--text3);font-size:.75rem;text-transform:uppercase;">Security Deposit</strong><br><strong style="font-size:1.1rem;font-family:var(--font-display);">₹${Number(room.security_deposit).toLocaleString()}</strong></div>
            <div><strong style="color:var(--text3);font-size:.75rem;text-transform:uppercase;">Area</strong><br>${room.area}</div>
            <div><strong style="color:var(--text3);font-size:.75rem;text-transform:uppercase;">Occupancy</strong><br>${room.occupancy} person${room.occupancy>1?'s':''}</div>
            ${room.nearby_college?`<div><strong style="color:var(--text3);font-size:.75rem;text-transform:uppercase;">Nearby College</strong><br>${room.nearby_college}</div>`:''}
        </div>
        ${amenities.length?`<div style="margin-bottom:1.25rem;"><strong style="color:var(--text3);font-size:.75rem;text-transform:uppercase;display:block;margin-bottom:.5rem;">Amenities</strong><div>${amenities.map(a=>`<span class="badge">${a}</span>`).join('')}</div></div>`:''}
        <a href="tel:${room.contact}" style="display:block;text-align:center;" class="btn-submit">📞 Call ${room.contact}</a>
    `;
    const modal = document.getElementById('roomModal');
    if (modal) modal.style.display = 'flex';
}

function closeModal(){
    const modal = document.getElementById('roomModal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeModal();
});

// ── OFFLINE ──
window.addEventListener('offline', function(){
    const b = document.getElementById('offlineBadge');
    if (b) b.style.display = 'block';
});
window.addEventListener('online', function(){
    const b = document.getElementById('offlineBadge');
    if (b) b.style.display = 'none';
    showToast('📡 Back online!', 'success');
});

// ── INIT ──
document.addEventListener('DOMContentLoaded', function(){
    const t = localStorage.getItem('pr_theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
    updateThemeBtns(t);
    setTimeout(function(){ window.dispatchEvent(new Event('scroll')); }, 100);
    document.querySelectorAll('.faq-question').forEach(function(q){
        q.addEventListener('click', function(){
            this.closest('.faq-item').classList.toggle('open');
        });
    });
});
