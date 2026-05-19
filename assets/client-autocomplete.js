/**
 * Client autocomplete for receipt forms.
 */
(function () {
  const DEBOUNCE_MS = 280;
  const MIN_LEN = 2;

  function esc(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function init() {
    const nameEl = document.getElementById('name');
    if (!nameEl || nameEl.dataset.acInit) return;
    nameEl.dataset.acInit = '1';

    const wrap = nameEl.closest('.ac-wrap') || nameEl.parentElement;
    wrap.classList.add('ac-wrap');

    const list = document.createElement('div');
    list.className = 'ac-list';
    list.id = 'clientAcList';
    list.setAttribute('role', 'listbox');
    wrap.appendChild(list);

    const hint = document.createElement('p');
    hint.className = 'ac-hint';
    hint.id = 'clientAcHint';
    hint.textContent = 'Client details loaded — edit as needed.';
    wrap.appendChild(hint);

    let timer = null;
    let items = [];
    let activeIdx = -1;
    let filledFromDb = false;

    function hideList() {
      list.classList.remove('show');
      list.innerHTML = '';
      items = [];
      activeIdx = -1;
    }

    function setActive(idx) {
      activeIdx = idx;
      list.querySelectorAll('.ac-item').forEach((el, i) => {
        el.classList.toggle('active', i === idx);
        if (i === idx) el.scrollIntoView({ block: 'nearest' });
      });
    }

    function applyClient(c) {
      nameEl.value = c.name || nameEl.value;
      document.getElementById('contact').value = c.contact || '';
      document.getElementById('address').value = c.address || '';
      const desc = document.getElementById('description');
      if (desc && c.description) desc.value = c.description;
      const paid = document.getElementById('paid');
      if (paid) paid.value = '0';
      filledFromDb = true;
      hint.classList.add('show');
      hideList();
      if (typeof updatePreview === 'function') updatePreview();
      if (typeof toast === 'function') toast('Loaded details for ' + c.name);
    }

    function render(results) {
      if (!results.length) {
        hideList();
        return;
      }
      list.innerHTML = results.map((c, i) => {
        const sub = [c.contact, c.address].filter(Boolean).join(' · ');
        return '<button type="button" class="ac-item" role="option" data-idx="' + i + '">' +
          '<span class="ac-item-name">' + esc(c.name) + '</span>' +
          (sub ? '<span class="ac-item-sub">' + esc(sub) + '</span>' : '') +
          '</button>';
      }).join('');
      list.classList.add('show');
      items = results;

      list.querySelectorAll('.ac-item').forEach(btn => {
        btn.addEventListener('mousedown', function (e) {
          e.preventDefault();
          applyClient(items[parseInt(btn.dataset.idx, 10)]);
        });
      });
    }

    function search(term) {
      fetch('search-clients.php?q=' + encodeURIComponent(term))
        .then(r => r.json())
        .then(data => {
          if (data.error) return;
          render(Array.isArray(data) ? data : []);
        })
        .catch(() => hideList());
    }

    nameEl.addEventListener('input', function () {
      filledFromDb = false;
      hint.classList.remove('show');
      const term = nameEl.value.trim();
      clearTimeout(timer);
      if (term.length < MIN_LEN) {
        hideList();
        return;
      }
      timer = setTimeout(() => search(term), DEBOUNCE_MS);
    });

    nameEl.addEventListener('keydown', function (e) {
      if (!list.classList.contains('show') || !items.length) return;
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActive(activeIdx < items.length - 1 ? activeIdx + 1 : 0);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActive(activeIdx > 0 ? activeIdx - 1 : items.length - 1);
      } else if (e.key === 'Enter' && activeIdx >= 0) {
        e.preventDefault();
        applyClient(items[activeIdx]);
      } else if (e.key === 'Escape') {
        hideList();
      }
    });

    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) hideList();
    });

    const contactEl = document.getElementById('contact');
    if (contactEl) {
      contactEl.addEventListener('input', function () {
        const term = contactEl.value.trim();
        if (term.length < MIN_LEN) return;
        clearTimeout(timer);
        timer = setTimeout(() => {
          fetch('search-clients.php?q=' + encodeURIComponent(term))
            .then(r => r.json())
            .then(data => {
              if (!Array.isArray(data) || !data.length) return;
              const exact = data.find(c =>
                (c.contact || '').replace(/\s/g, '') === term.replace(/\s/g, '')
              );
              if (exact) {
                nameEl.value = exact.name;
                applyClient(exact);
              }
            })
            .catch(() => {});
        }, DEBOUNCE_MS);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
