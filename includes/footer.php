    </div><!-- /pg -->
  </div><!-- /main -->
</div><!-- /app -->

<!-- ══════ BOTTOM NAV (mobile ≤640px) ══════ -->
<?php $cp = basename($_SERVER['PHP_SELF']); ?>
<nav class="bottom-nav" aria-label="Bottom navigation">
  <a href="index.php" class="bn <?= $cp==='index.php'?'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    <span>Dashboard</span>
  </a>
  <a href="new-receipt.php" class="bn <?= $cp==='new-receipt.php'?'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    <span>New</span>
  </a>
  <a href="clients.php" class="bn <?= in_array($cp,['clients.php','view-receipt.php'])?'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12zM6 10h2v2H6zm0 4h8v2H6zm10 0h2v2h-2zm-6-4h8v2h-8z"/></svg>
    <span>Receipts</span>
  </a>
  <button class="bn" onclick="toggleTheme()" aria-label="Toggle theme">
    <svg id="bnMoon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a9 9 0 1 0 9 9c0-.46-.04-.92-.1-1.36a5.389 5.389 0 0 1-4.4 2.26 5.403 5.403 0 0 1-3.14-9.8c-.44-.06-.9-.1-1.36-.1z"/></svg>
    <svg id="bnSun" viewBox="0 0 24 24" fill="currentColor" style="display:none"><path d="M12 7a5 5 0 1 0 0 10A5 5 0 0 0 12 7zM2 13h2a1 1 0 0 0 0-2H2a1 1 0 0 0 0 2zm18 0h2a1 1 0 0 0 0-2h-2a1 1 0 0 0 0 2zM11 2v2a1 1 0 0 0 2 0V2a1 1 0 0 0-2 0zm0 18v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-2 0zM5.99 4.58a1 1 0 0 0-1.41 1.41l1.06 1.06a1 1 0 0 0 1.41-1.41L5.99 4.58zm12.37 12.37a1 1 0 0 0-1.41 1.41l1.06 1.06a1 1 0 0 0 1.41-1.41l-1.06-1.06zm1.06-10.96a1 1 0 0 0-1.41-1.41l-1.06 1.06a1 1 0 0 0 1.41 1.41l1.06-1.06zM7.05 18.36a1 1 0 0 0-1.41-1.41l-1.06 1.06a1 1 0 0 0 1.41 1.41l1.06-1.06z"/></svg>
    <span>Theme</span>
  </button>
</nav>

<!-- Toast container -->
<div class="tw-c" id="toasts"></div>

<script>
/* ══ SIDEBAR ═══════════════════════════════════════════ */
function toggleSb() {
  const sb  = document.getElementById('sb');
  const ov  = document.getElementById('sbOv');
  const open = sb.classList.toggle('open');
  ov.classList.toggle('show', open);
  document.body.style.overflow = open ? 'hidden' : '';
}
function closeSb() {
  document.getElementById('sb').classList.remove('open');
  document.getElementById('sbOv').classList.remove('show');
  document.body.style.overflow = '';
}
// Close sidebar on resize to desktop
window.addEventListener('resize', function() {
  if (window.innerWidth > 768) closeSb();
});

/* ══ ACCORDION ══════════════════════════════════════════ */
var _accState = {};
try { _accState = JSON.parse(localStorage.getItem('fc-acc') || '{}'); } catch(e){}

function toggleAcc(id) {
  const el = document.getElementById(id);
  if (!el) return;
  const isClosed = el.classList.toggle('closed');
  // Update button aria-expanded
  const btn = el.querySelector('.nav-acc-btn');
  if (btn) btn.setAttribute('aria-expanded', String(!isClosed));
  // Persist state
  _accState[id] = isClosed ? 'closed' : 'open';
  try { localStorage.setItem('fc-acc', JSON.stringify(_accState)); } catch(e){}
}

// Restore accordion states on load
(function restoreAcc() {
  Object.keys(_accState).forEach(function(id) {
    const el = document.getElementById(id);
    if (!el) return;
    if (_accState[id] === 'closed') {
      el.classList.add('closed');
      const btn = el.querySelector('.nav-acc-btn');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    }
  });
})();

/* ══ THEME TOGGLE ════════════════════════════════════════ */
function toggleTheme() {
  const html     = document.documentElement;
  const current  = html.getAttribute('data-theme') || 'dark';
  const next     = current === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  try { localStorage.setItem('fc-theme', next); } catch(e) {}
  // Update bottom nav icons
  syncThemeIcons(next);
}

function syncThemeIcons(theme) {
  const moon = document.getElementById('bnMoon');
  const sun  = document.getElementById('bnSun');
  if (!moon || !sun) return;
  if (theme === 'light') {
    moon.style.display = 'none';
    sun.style.display  = 'block';
  } else {
    moon.style.display = 'block';
    sun.style.display  = 'none';
  }
}

// Sync icons on page load
(function() {
  var t = document.documentElement.getAttribute('data-theme') || 'dark';
  syncThemeIcons(t);
})();

/* ══ TOAST ═══════════════════════════════════════════════ */
function toast(msg, type) {
  type = type || 'ok';
  var icons = {
    ok:   '<svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14" style="color:var(--green);flex-shrink:0"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
    err:  '<svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14" style="color:var(--red);flex-shrink:0"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>',
    warn: '<svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14" style="color:var(--ylw);flex-shrink:0"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>'
  };
  var t = document.createElement('div');
  t.className = 'toast' + (type==='err'?' err':type==='warn'?' warn':'');
  t.innerHTML = (icons[type] || icons.ok) + '<span style="flex:1">' + msg + '</span>';
  document.getElementById('toasts').appendChild(t);
  setTimeout(function() {
    t.style.transition = '.3s ease';
    t.style.opacity    = '0';
    t.style.transform  = 'translateX(110%)';
    setTimeout(function() { t.remove(); }, 320);
  }, 3500);
}
</script>
</body>
</html>
