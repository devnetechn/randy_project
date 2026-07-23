/* Admin dashboard: overview KPIs, live chat, bookings, gallery. */
(function () {
  'use strict';

  const refresh = {};
  const loaded = {};

  function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
  function fmt(dt) {
    if (!dt) return '—';
    const d = new Date(String(dt).replace(' ', 'T'));
    return isNaN(d.getTime()) ? dt : d.toLocaleString();
  }

  /* ----------  Overview  ---------- */
  function initOverview(panel) {
    const LIVE = [['queueDepth', 'Queue depth'], ['activeChats', 'Active chats'], ['pendingBookings', 'Pending bookings'], ['upcomingToday', 'Upcoming today']];
    const TODAY = [['newConversations', 'New conversations'], ['newBookings', 'New bookings'], ['newSignups', 'New signups'], ['aiEscalationPct', 'AI escalation %']];
    const MONTH = [['monthBookings', 'Quotes requested'], ['monthLeads', 'New leads'], ['monthSignups', 'New signups']];
    const card = (k, l, badge) => '<div class="kpi"><div class="kpi__label">' + l + ' <span class="kpi__badge kpi__badge--' + badge + '">' + badge + '</span></div><div class="kpi__value" data-kpi="' + k + '">—</div></div>';
    panel.innerHTML =
      '<div class="kpi-section"><h2>Live now</h2><div class="kpi-grid">' + LIVE.map((c) => card(c[0], c[1], 'live')).join('') + '</div></div>' +
      '<div class="kpi-section"><h2>Today</h2><div class="kpi-grid">' + TODAY.map((c) => card(c[0], c[1], 'today')).join('') + '</div></div>' +
      '<div class="kpi-section"><h2>This month</h2><div class="kpi-grid">' + MONTH.map((c) => card(c[0], c[1], 'month')).join('') + '</div></div>' +
      '<div class="kpi-section"><h2>Last 6 months</h2><div class="chart-card"><canvas data-overview-chart height="90"></canvas></div></div>';

    const set = (k, v) => { const el = panel.querySelector('[data-kpi="' + k + '"]'); if (el) el.textContent = v == null ? '—' : v; };

    let chart = null;
    async function loadChart() {
      const canvas = panel.querySelector('[data-overview-chart]');
      if (!canvas || !window.Chart) return;
      try {
        const d = await api.get('api/admin/analytics.php');
        const datasets = [
          { label: 'Quotes requested', data: d.bookings, borderColor: '#2a66d6', backgroundColor: '#2a66d6' },
          { label: 'New leads', data: d.leads, borderColor: '#b4241d', backgroundColor: '#b4241d' },
          { label: 'New signups', data: d.signups, borderColor: '#059669', backgroundColor: '#059669' },
        ].map((ds) => Object.assign(ds, { borderWidth: 2, pointRadius: 4, pointHoverRadius: 5, tension: 0.25, fill: false }));

        if (chart) {
          chart.data.labels = d.months;
          chart.data.datasets = datasets;
          chart.update();
          return;
        }
        chart = new Chart(canvas.getContext('2d'), {
          type: 'line',
          data: { labels: d.months, datasets },
          options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true } } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
          },
        });
      } catch (_) { /* ignore */ }
    }

    async function load() {
      try {
        const d = await api.get('api/admin/overview.php');
        LIVE.forEach((c) => set(c[0], d.live[c[0]]));
        TODAY.forEach((c) => set(c[0], d.today[c[0]]));
        MONTH.forEach((c) => set(c[0], d.month[c[0]]));
      } catch (_) { /* ignore */ }
    }
    load();
    loadChart();
    setInterval(() => { if (!document.hidden) load(); }, 30000);
    refresh.overview = () => { load(); loadChart(); };
  }

  /* ----------  Bookings  ---------- */
  function initBookings(panel) {
    const STATUSES = ['all', 'pending', 'confirmed', 'declined', 'cancelled', 'completed'];
    let bookings = [], filter = 'all';
    panel.innerHTML =
      '<div class="tabs" data-bk-filter style="margin-top:0">' +
      STATUSES.map((s) => '<button class="tab' + (s === 'all' ? ' is-active' : '') + '" data-f="' + s + '">' + cap(s) + '</button>').join('') +
      '</div><ul class="booking-list" data-bk-list></ul>';
    const listEl = panel.querySelector('[data-bk-list]');

    panel.querySelector('[data-bk-filter]').addEventListener('click', (e) => {
      const b = e.target.closest('[data-f]'); if (!b) return;
      filter = b.dataset.f;
      panel.querySelectorAll('[data-bk-filter] .tab').forEach((t) => t.classList.toggle('is-active', t === b));
      render();
    });
    listEl.addEventListener('click', onAction);

    function item(b) {
      let actions = '';
      if (b.status === 'pending') {
        actions = '<button class="btn-primary" data-act="confirm" data-id="' + b.id + '">Confirm</button> ' +
                  '<button class="btn-soft" data-act="decline" data-id="' + b.id + '">Decline</button>';
      } else if (b.status === 'confirmed') {
        actions = '<button class="btn-primary" data-act="complete" data-id="' + b.id + '">Mark complete</button> ' +
                  '<button class="btn-soft" data-act="reschedule" data-id="' + b.id + '">Reschedule</button>';
      }
      return '<li class="booking-item"><div class="booking-item__head">' +
        '<span class="booking-item__title">#' + b.id + ' · ' + escapeHtml(b.service_type) + '</span>' +
        '<span class="badge badge--' + b.status + '">' + b.status + '</span></div>' +
        '<p class="booking-item__meta">' + escapeHtml(b.customer_name || '') + ' · ' + escapeHtml(b.customer_email || '') + '</p>' +
        '<p class="booking-item__meta">Preferred: ' + fmt(b.preferred_at) + (b.scheduled_at ? ' · Scheduled: ' + fmt(b.scheduled_at) : '') + '</p>' +
        '<p class="booking-item__meta">' + escapeHtml(b.address) + (b.phone ? ' · ' + escapeHtml(b.phone) : '') + '</p>' +
        (b.notes ? '<p class="booking-item__meta">' + escapeHtml(b.notes) + '</p>' : '') +
        (actions ? '<div class="booking-actions">' + actions + '</div>' : '') + '</li>';
    }
    function render() {
      const vis = filter === 'all' ? bookings : bookings.filter((b) => b.status === filter);
      listEl.innerHTML = vis.length ? vis.map(item).join('') : '<li style="color:var(--muted)">No bookings.</li>';
    }
    async function onAction(e) {
      const btn = e.target.closest('[data-act]'); if (!btn) return;
      const id = +btn.dataset.id, act = btn.dataset.act, body = { id, action: act };
      if (act === 'reschedule') { const w = prompt('New date & time (e.g. 2026-06-01 14:30):'); if (!w) return; body.scheduledAt = w; }
      if (act === 'decline') { body.reason = prompt('Reason (optional):') || ''; }
      try {
        const d = await api.post('api/appointments/update.php', body);
        const i = bookings.findIndex((x) => String(x.id) === String(id));
        if (i >= 0) bookings[i] = d.appointment;
        render();
        toast('Updated');
      } catch (err) { toast(err.message, 'error'); }
    }
    async function load() {
      try { bookings = (await api.get('api/appointments/list.php')).appointments || []; render(); }
      catch (e) { toast(e.message, 'error'); }
    }
    load();
    refresh.bookings = load;
  }

  /* ----------  Reports  ---------- */
  function initReports(panel) {
    const VIEWS = ['daily', 'weekly', 'monthly'];
    const LABELS = { daily: 'Daily', weekly: 'Weekly', monthly: 'Monthly' };
    const COLS = ['total', 'pending', 'confirmed', 'declined', 'cancelled', 'completed'];
    const PRESETS = [
      ['month', 'This month'],
      ['30d', 'Last 30 days'],
      ['year', 'This year'],
      ['all', 'All time'],
    ];
    let view = 'monthly';
    let sub = 'summary';

    panel.innerHTML =
      '<div class="tabs" data-rp-sub style="margin-top:0">' +
      '<button class="tab is-active" data-s="summary">Summary</button>' +
      '<button class="tab" data-s="contacts">Contact list</button>' +
      '<button type="button" class="btn-soft" data-rp-print style="margin-left:.5rem">Download PDF</button>' +
      '</div>' +
      '<div class="report-print-heading" data-rp-heading></div>' +
      '<div data-rp-summary>' +
      '<div class="tabs" data-rp-filter>' +
      VIEWS.map((v) => '<button class="tab' + (v === view ? ' is-active' : '') + '" data-v="' + v + '">' + LABELS[v] + '</button>').join('') +
      '</div><div class="report-table-wrap" data-rp-table></div></div>' +
      '<div data-rp-contacts hidden>' +
      '<div class="tabs" data-rp-presets>' +
      PRESETS.map((p) => '<button class="tab' + (p[0] === 'month' ? ' is-active' : '') + '" data-p="' + p[0] + '">' + p[1] + '</button>').join('') +
      '</div>' +
      '<div class="report-dates" data-rp-dates>' +
      '<label>From <input type="date" data-rp-from></label>' +
      '<label>To <input type="date" data-rp-to></label>' +
      '</div><div class="report-table-wrap" data-rp-clist></div></div>';

    const tableEl = panel.querySelector('[data-rp-table]');
    const headingEl = panel.querySelector('[data-rp-heading]');
    const summaryEl = panel.querySelector('[data-rp-summary]');
    const contactsEl = panel.querySelector('[data-rp-contacts]');
    const clistEl = panel.querySelector('[data-rp-clist]');
    const fromEl = panel.querySelector('[data-rp-from]');
    const toEl = panel.querySelector('[data-rp-to]');

    panel.querySelector('[data-rp-sub]').addEventListener('click', (e) => {
      const b = e.target.closest('[data-s]'); if (!b) return;
      sub = b.dataset.s;
      panel.querySelectorAll('[data-rp-sub] .tab').forEach((t) => t.classList.toggle('is-active', t === b));
      summaryEl.hidden = sub !== 'summary';
      contactsEl.hidden = sub !== 'contacts';
      if (sub === 'contacts') loadContacts();
    });

    panel.querySelector('[data-rp-filter]').addEventListener('click', (e) => {
      const b = e.target.closest('[data-v]'); if (!b) return;
      view = b.dataset.v;
      panel.querySelectorAll('[data-rp-filter] .tab').forEach((t) => t.classList.toggle('is-active', t === b));
      loadSummary();
    });

    panel.querySelector('[data-rp-presets]').addEventListener('click', (e) => {
      const b = e.target.closest('[data-p]'); if (!b) return;
      panel.querySelectorAll('[data-rp-presets] .tab').forEach((t) => t.classList.toggle('is-active', t === b));
      applyPreset(b.dataset.p);
      loadContacts();
    });

    panel.querySelector('[data-rp-dates]').addEventListener('change', () => {
      panel.querySelectorAll('[data-rp-presets] .tab').forEach((t) => t.classList.remove('is-active'));
      loadContacts();
    });

    panel.querySelector('[data-rp-print]').addEventListener('click', () => {
      headingEl.innerHTML = sub === 'summary' ? summaryHeading() : contactsHeading();
      window.print();
    });

    /* ---- shared ---- */
    function iso(d) {
      return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function today() {
      return new Date().toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function prettyDate(s) {
      if (!s) return '';
      return new Date(s + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }

    /* ---- summary ---- */
    function summaryHeading() {
      return escapeHtml('Booking Report — ' + LABELS[view] + ' — Generated ' + today());
    }
    function periodLabel(p) {
      const d = new Date(p + 'T00:00:00');
      if (view === 'daily') return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
      if (view === 'weekly') {
        const end = new Date(d);
        end.setDate(end.getDate() + 6);
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ' – ' + end.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
      }
      return d.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
    }
    function renderSummary(rows) {
      if (!rows.length) {
        tableEl.innerHTML = '<p style="color:var(--muted)">No bookings in this range.</p>';
        return;
      }
      let html = '<table class="report-table"><thead><tr><th>Period</th><th>Total</th><th>Pending</th><th>Confirmed</th><th>Declined</th><th>Cancelled</th><th>Completed</th></tr></thead><tbody>';
      rows.forEach((r) => {
        html += '<tr><td>' + escapeHtml(periodLabel(r.period)) + '</td>' +
          COLS.map((c) => '<td>' + (r[c] || 0) + '</td>').join('') + '</tr>';
      });
      html += '</tbody></table>';
      tableEl.innerHTML = html;
    }
    async function loadSummary() {
      tableEl.innerHTML = '<p style="color:var(--muted)">Loading&hellip;</p>';
      try {
        const d = await api.get('api/admin/reports.php?period=' + view);
        renderSummary(d.rows || []);
      } catch (e) { toast(e.message, 'error'); }
    }

    /* ---- contact list ---- */
    let contactRange = { from: null, to: null, count: 0 };

    function applyPreset(p) {
      const now = new Date();
      if (p === 'all') { fromEl.value = ''; toEl.value = ''; return; }
      toEl.value = iso(now);
      if (p === 'month') fromEl.value = iso(new Date(now.getFullYear(), now.getMonth(), 1));
      else if (p === 'year') fromEl.value = iso(new Date(now.getFullYear(), 0, 1));
      else { const s = new Date(now); s.setDate(s.getDate() - 29); fromEl.value = iso(s); }
    }

    function contactsHeading() {
      const range = contactRange.from
        ? prettyDate(contactRange.from) + ' – ' + prettyDate(contactRange.to)
        : 'All time';
      return escapeHtml("Randy's Painting & Drywall Services — Booking Contact Report") +
        '<br><span style="font-weight:400;font-size:.9rem">' +
        escapeHtml(range + ' · ' + contactRange.count + ' booking' + (contactRange.count === 1 ? '' : 's') +
          ' · Generated ' + today()) + '</span>';
    }

    function renderContacts(rows) {
      if (!rows.length) {
        clistEl.innerHTML = '<p style="color:var(--muted)">No bookings in this range.</p>';
        return;
      }
      let html = '<table class="report-table"><thead><tr><th>Date</th><th>Name</th><th>Phone</th><th>Email</th><th>Service</th><th>Status</th></tr></thead><tbody>';
      rows.forEach((r) => {
        html += '<tr>' +
          '<td>' + escapeHtml(fmt(r.createdAt)) + '</td>' +
          '<td>' + escapeHtml(r.name) + '</td>' +
          '<td>' + escapeHtml(r.phone || '—') + '</td>' +
          '<td>' + escapeHtml(r.email || '—') + '</td>' +
          '<td>' + escapeHtml(r.serviceType) + '</td>' +
          '<td>' + escapeHtml(cap(r.status)) + '</td>' +
          '</tr>';
      });
      html += '</tbody></table>';
      clistEl.innerHTML = html;
    }

    async function loadContacts() {
      clistEl.innerHTML = '<p style="color:var(--muted)">Loading&hellip;</p>';
      const qs = [];
      if (fromEl.value) qs.push('from=' + encodeURIComponent(fromEl.value));
      if (toEl.value) qs.push('to=' + encodeURIComponent(toEl.value));
      try {
        const d = await api.get('api/admin/contact-report.php' + (qs.length ? '?' + qs.join('&') : ''));
        const rows = d.rows || [];
        contactRange = { from: d.from, to: d.to, count: rows.length };
        renderContacts(rows);
      } catch (e) {
        clistEl.innerHTML = '<p style="color:var(--muted)">Could not load the contact list.</p>';
        toast(e.message, 'error');
      }
    }

    applyPreset('month');
    loadSummary();
    refresh.reports = () => (sub === 'summary' ? loadSummary() : loadContacts());
  }

  /* ----------  Gallery  ---------- */
  function initGallery(panel) {
    const CATS = ['interior', 'exterior', 'drywall', 'commercial', 'other'];
    panel.innerHTML =
      '<form class="app-card" data-up>' +
      '<label class="field"><span>Photo (JPEG/PNG/WebP, ≤5MB)</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label>' +
      '<label class="field"><span>Caption (optional)</span><input type="text" name="caption" maxlength="200"></label>' +
      '<label class="field"><span>Description (optional)</span><textarea name="description" rows="3"></textarea></label>' +
      '<label class="field"><span>Category</span><select name="category">' + CATS.map((c) => '<option value="' + c + '">' + cap(c) + '</option>').join('') + '</select></label>' +
      '<button class="btn-primary" type="submit">Upload photo</button></form>' +
      '<div class="gallery-admin__grid" data-ga-grid></div>';
    const grid = panel.querySelector('[data-ga-grid]');
    const form = panel.querySelector('[data-up]');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = form.querySelector('button');
      btn.disabled = true;
      try { await api.upload('api/gallery/upload.php', new FormData(form)); form.reset(); await load(); toast('Photo uploaded'); }
      catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });
    grid.addEventListener('click', async (e) => {
      const editBtn = e.target.closest('[data-edit]');
      if (editBtn) {
        const f = grid.querySelector('[data-edit-form="' + editBtn.dataset.edit + '"]');
        if (f) f.hidden = !f.hidden;
        return;
      }
      const cancelBtn = e.target.closest('.gallery-admin__cancel');
      if (cancelBtn) {
        const f = cancelBtn.closest('[data-edit-form]');
        if (f) f.hidden = true;
        return;
      }
      const delBtn = e.target.closest('[data-del]');
      if (!delBtn) return;
      if (!confirm('Delete this photo?')) return;
      try { await api.post('api/gallery/delete.php', { id: +delBtn.dataset.del }); await load(); }
      catch (err) { toast(err.message, 'error'); }
    });
    grid.addEventListener('submit', async (e) => {
      const f = e.target.closest('[data-edit-form]');
      if (!f) return;
      e.preventDefault();
      const id = +f.getAttribute('data-edit-form');
      const body = {
        id,
        caption: f.caption.value.trim(),
        category: f.category.value,
        description: f.description.value.trim(),
      };
      const btn = f.querySelector('button[type="submit"]');
      btn.disabled = true;
      try { await api.post('api/gallery/update.php', body); await load(); toast('Photo updated'); }
      catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });
    async function load() {
      try {
        const imgs = (await api.get('api/gallery/list.php')).images || [];
        grid.innerHTML = imgs.length ? imgs.map((img) =>
          '<div class="gallery-admin__item" data-id="' + img.id + '">' +
            '<img src="' + escapeHtml(img.url) + '" alt="">' +
            '<div class="gallery-admin__meta"><div class="cat">' + escapeHtml(img.category) + '</div>' + escapeHtml(img.caption || '') + '</div>' +
            '<div class="gallery-admin__actions">' +
              '<button class="gallery-admin__edit" data-edit="' + img.id + '">Edit</button>' +
              '<button class="gallery-admin__del" data-del="' + img.id + '">Delete</button>' +
            '</div>' +
            '<form class="gallery-admin__form" data-edit-form="' + img.id + '" hidden>' +
              '<label class="field"><span>Caption</span><input type="text" name="caption" maxlength="200" value="' + escapeHtml(img.caption || '') + '"></label>' +
              '<label class="field"><span>Category</span><select name="category">' + CATS.map((c) => '<option value="' + c + '"' + (c === img.category ? ' selected' : '') + '>' + cap(c) + '</option>').join('') + '</select></label>' +
              '<label class="field"><span>Description</span><textarea name="description" rows="3">' + escapeHtml(img.description || '') + '</textarea></label>' +
              '<div><button class="btn-primary" type="submit">Save</button> <button type="button" class="gallery-admin__cancel">Cancel</button></div>' +
            '</form>' +
          '</div>').join('')
          : '<p style="color:var(--muted)">No photos yet.</p>';
      } catch (e) { toast(e.message, 'error'); }
    }
    load();
    refresh.gallery = load;
  }

  /* ----------  Blog  ---------- */
  function initBlog(panel) {
    panel.innerHTML =
      '<form class="app-card" data-blog-url-form>' +
      '<label class="field"><span>External blog URL — where the About-page blog cards link to</span>' +
      '<input type="url" name="blogUrl" placeholder="https://yourblog.com"></label>' +
      '<button class="btn-primary" type="submit">Save blog URL</button>' +
      '</form>' +
      '<form class="app-card" data-blog-form>' +
      '<input type="hidden" name="id" value="">' +
      '<label class="field"><span>Title</span><input type="text" name="title" maxlength="200" required></label>' +
      '<label class="field"><span>Excerpt (short blurb for cards, optional)</span><input type="text" name="excerpt" maxlength="300"></label>' +
      '<label class="field"><span>Body</span><textarea name="body" rows="8" required></textarea></label>' +
      '<label class="field"><span>Status</span><select name="status"><option value="draft">Draft</option><option value="published">Published</option></select></label>' +
      '<label class="field"><span>Featured image (JPEG/PNG/WebP, ≤5MB — optional)</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>' +
      '<div class="booking-actions"><button class="btn-primary" type="submit" data-blog-submit>Save post</button> ' +
      '<button class="btn-soft" type="button" data-blog-reset>New / clear</button></div>' +
      '</form>' +
      '<div class="blog-admin__list" data-blog-list></div>';
    const form = panel.querySelector('[data-blog-form]');
    const listEl = panel.querySelector('[data-blog-list]');
    const urlForm = panel.querySelector('[data-blog-url-form]');

    async function loadUrl() {
      try { urlForm.querySelector('[name="blogUrl"]').value = (await api.get('api/blog/settings.php')).blogUrl || ''; }
      catch (_) { /* ignore */ }
    }
    urlForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = urlForm.querySelector('button');
      btn.disabled = true;
      try {
        await api.post('api/blog/settings.php', { blogUrl: urlForm.querySelector('[name="blogUrl"]').value.trim() });
        toast('Blog URL saved');
      } catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });

    function clearForm() {
      form.reset();
      form.querySelector('[name="id"]').value = '';
      form.querySelector('[data-blog-submit]').textContent = 'Save post';
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = form.querySelector('[data-blog-submit]');
      btn.disabled = true;
      try {
        await api.upload('api/blog/save.php', new FormData(form));
        clearForm();
        await load();
        toast('Post saved');
      } catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });

    form.querySelector('[data-blog-reset]').addEventListener('click', clearForm);

    listEl.addEventListener('click', async (e) => {
      const edit = e.target.closest('[data-edit]');
      const del = e.target.closest('[data-del]');
      if (edit) {
        try {
          const p = (await api.get('api/blog/get.php?id=' + edit.dataset.edit)).post;
          form.querySelector('[name="id"]').value = p.id;
          form.querySelector('[name="title"]').value = p.title || '';
          form.querySelector('[name="excerpt"]').value = p.excerpt || '';
          form.querySelector('[name="body"]').value = p.body || '';
          form.querySelector('[name="status"]').value = p.status || 'draft';
          form.querySelector('[name="image"]').value = '';
          form.querySelector('[data-blog-submit]').textContent = 'Update post';
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (err) { toast(err.message, 'error'); }
        return;
      }
      if (del) {
        if (!confirm('Delete this post?')) return;
        try { await api.post('api/blog/delete.php', { id: +del.dataset.del }); await load(); toast('Post deleted'); }
        catch (err) { toast(err.message, 'error'); }
      }
    });

    async function load() {
      try {
        const posts = (await api.get('api/blog/list.php')).posts || [];
        listEl.innerHTML = posts.length ? posts.map((p) =>
          '<div class="blog-admin__item">' +
          (p.image ? '<img src="' + escapeHtml(p.image) + '" alt="">' : '<div class="blog-admin__noimg">No image</div>') +
          '<div class="blog-admin__meta">' +
          '<span class="badge badge--' + (p.status === 'published' ? 'confirmed' : 'pending') + '">' + escapeHtml(p.status) + '</span>' +
          '<div class="blog-admin__title">' + escapeHtml(p.title) + '</div>' +
          '<div class="blog-admin__date">' + fmt(p.date) + '</div></div>' +
          '<div class="blog-admin__actions"><button class="btn-soft" data-edit="' + p.id + '">Edit</button> ' +
          '<button class="btn-soft" data-del="' + p.id + '">Delete</button></div></div>').join('')
          : '<p style="color:var(--muted)">No posts yet.</p>';
      } catch (e) { toast(e.message, 'error'); }
    }
    load();
    loadUrl();
    refresh.blog = load;
  }

  /* ----------  Live chat  ---------- */
  function initChat(panel) {
    panel.innerHTML =
      '<div class="admin-chat"><div class="admin-queue" data-queue></div>' +
      '<div class="admin-chat__main" data-room><div class="admin-chat__empty">Select a conversation from the queue.</div></div></div>';
    const queueEl = panel.querySelector('[data-queue]');
    const roomEl = panel.querySelector('[data-room]');
    const LABELS = { ai: 'Assistant', admin: 'Team', customer: 'Customer' };
    let current = null, lastId = 0, seen = new Set();

    function bubble(m) {
      if (m.sender_type === 'system') return '<div class="msg msg--system"><div class="msg__sys">' + escapeHtml(m.body) + '</div></div>';
      // From the admin's view the customer is on the left, the team (admin) on the right.
      const own = m.sender_type === 'admin';
      const cls = own ? 'customer' : (m.sender_type === 'system' ? 'system' : 'ai');
      return '<div class="msg msg--' + cls + '"><span class="msg__who">' + (LABELS[m.sender_type] || '') + '</span>' +
        '<div class="msg__bubble">' + escapeHtml(m.body) + '</div></div>';
    }

    async function loadQueue() {
      try {
        const list = (await api.get('api/admin/queue.php')).conversations || [];
        queueEl.innerHTML = list.length ? list.map((c) =>
          '<button class="admin-queue__item' + (current && String(current.id) === String(c.id) ? ' is-active' : '') + '" data-cid="' + c.id + '" data-status="' + c.status + '">' +
          '<div class="cust">' + escapeHtml(c.customer_name || ('#' + c.customer_id)) + ' <span class="badge badge--' + c.status + '">' + c.status + '</span></div>' +
          '<div class="time">' + fmt(c.last_message_at) + '</div></button>').join('')
          : '<p style="padding:1rem;color:var(--muted)">Queue is empty.</p>';
      } catch (_) { /* ignore */ }
    }
    queueEl.addEventListener('click', (e) => {
      const b = e.target.closest('[data-cid]'); if (!b) return;
      openRoom({ id: +b.dataset.cid, status: b.dataset.status });
    });

    function controls() {
      if (current.status === 'waiting_human') return '<button class="btn-primary" data-act="takeover">Take over</button>';
      if (current.status === 'human') return '<button class="btn-soft" data-act="return">Return to AI</button> <button class="btn-soft" data-act="close">Close</button>';
      return '';
    }
    function renderRoomShell() {
      roomEl.innerHTML =
        '<div class="admin-chat__bar"><button class="btn-soft" data-back>← Back</button>' +
        '<span class="badge badge--' + current.status + '">' + current.status + '</span>' +
        '<span style="flex:1"></span>' + controls() + '</div>' +
        '<div class="chat-messages" data-msgs></div>' +
        '<form class="chat-input" data-form><input data-input placeholder="Type a reply…" autocomplete="off"><button type="submit">Send</button></form>';
      roomEl.querySelector('[data-back]').addEventListener('click', () => { current = null; roomEl.innerHTML = '<div class="admin-chat__empty">Select a conversation from the queue.</div>'; loadQueue(); });
      const bar = roomEl.querySelector('.admin-chat__bar');
      bar.addEventListener('click', onControl);
      roomEl.querySelector('[data-form]').addEventListener('submit', onSend);
    }
    function appendMsgs(msgs, replace) {
      const box = roomEl.querySelector('[data-msgs]'); if (!box) return;
      if (replace) { box.innerHTML = ''; seen.clear(); }
      msgs.forEach((m) => { const id = +m.id || 0; if (id && seen.has(id)) return; if (id) seen.add(id); box.insertAdjacentHTML('beforeend', bubble(m)); if (id > lastId) lastId = id; });
      box.scrollTop = box.scrollHeight;
    }
    async function openRoom(conv) {
      current = conv; lastId = 0; seen = new Set();
      renderRoomShell();
      try {
        const d = await api.get('api/chat/messages.php?conversation_id=' + conv.id + '&after=0');
        current.status = d.conversation.status;
        renderRoomShell();
        appendMsgs(d.messages || [], true);
      } catch (e) { toast(e.message, 'error'); }
      loadQueue();
    }
    async function onSend(e) {
      e.preventDefault();
      const input = roomEl.querySelector('[data-input]');
      const text = input.value.trim(); if (!text || !current) return;
      input.value = '';
      try { appendMsgs((await api.post('api/chat/send.php', { conversationId: current.id, body: text })).messages || [], false); }
      catch (err) { toast(err.message, 'error'); }
    }
    async function onControl(e) {
      const b = e.target.closest('[data-act]'); if (!b || !current) return;
      try {
        const d = await api.post('api/chat/admin_action.php', { conversationId: current.id, action: b.dataset.act });
        current.status = d.conversation.status;
        renderRoomShell();
        const after = lastId;
        const md = await api.get('api/chat/messages.php?conversation_id=' + current.id + '&after=' + after);
        appendMsgs(md.messages || [], false);
        loadQueue();
      } catch (err) { toast(err.message, 'error'); }
    }

    async function pollRoom() {
      if (document.hidden || !current) return;
      try {
        const d = await api.get('api/chat/messages.php?conversation_id=' + current.id + '&after=' + lastId);
        if (d.conversation.status !== current.status) { current.status = d.conversation.status; renderRoomShell(); }
        if (d.messages && d.messages.length) appendMsgs(d.messages, false);
      } catch (_) { /* ignore */ }
    }

    loadQueue();
    setInterval(() => { if (!document.hidden) loadQueue(); }, 5000);
    setInterval(pollRoom, 3000);
    refresh.chat = loadQueue;
  }

  /* ----------  Reviews  ---------- */
  function initReviews(panel) {
    panel.innerHTML =
      '<form class="app-card" data-rev-url-form>' +
      '<label class="field"><span>“Leave a review” link — where the homepage button sends customers</span>' +
      '<input type="url" name="reviewUrl" placeholder="https://g.page/r/…/review"></label>' +
      '<button class="btn-primary" type="submit">Save link</button></form>' +
      '<form class="app-card" data-rev-form>' +
      '<input type="hidden" name="id" value="">' +
      '<label class="field"><span>Customer name</span><input type="text" name="author" maxlength="120" required placeholder="Maria S."></label>' +
      '<label class="field"><span>Rating</span><select name="rating">' +
      '<option value="5">★★★★★ (5)</option><option value="4">★★★★ (4)</option>' +
      '<option value="3">★★★ (3)</option><option value="2">★★ (2)</option><option value="1">★ (1)</option></select></label>' +
      '<label class="field"><span>Review text</span><textarea name="body" rows="4" required placeholder="What the customer wrote…"></textarea></label>' +
      '<label class="field"><span>Subtitle (optional — e.g. “Interior repaint · Easton, PA” or “2 weeks ago”)</span>' +
      '<input type="text" name="meta" maxlength="160"></label>' +
      '<div class="booking-actions"><button class="btn-primary" type="submit" data-rev-submit>Add review</button> ' +
      '<button class="btn-soft" type="button" data-rev-reset>New / clear</button></div></form>' +
      '<div class="blog-admin__list" data-rev-list></div>';

    const urlForm = panel.querySelector('[data-rev-url-form]');
    const form = panel.querySelector('[data-rev-form]');
    const listEl = panel.querySelector('[data-rev-list]');

    async function loadUrl() {
      try { urlForm.querySelector('[name="reviewUrl"]').value = (await api.get('api/reviews/settings.php')).reviewUrl || ''; }
      catch (_) { /* ignore */ }
    }
    urlForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = urlForm.querySelector('button');
      btn.disabled = true;
      try {
        await api.post('api/reviews/settings.php', { reviewUrl: urlForm.querySelector('[name="reviewUrl"]').value.trim() });
        toast('Review link saved');
      } catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });

    function clearForm() {
      form.reset();
      form.querySelector('[name="id"]').value = '';
      form.querySelector('[data-rev-submit]').textContent = 'Add review';
    }
    form.querySelector('[data-rev-reset]').addEventListener('click', clearForm);

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = form.querySelector('[data-rev-submit]');
      btn.disabled = true;
      try {
        await api.post('api/reviews/save.php', {
          id: +form.querySelector('[name="id"]').value || 0,
          author: form.querySelector('[name="author"]').value.trim(),
          rating: +form.querySelector('[name="rating"]').value,
          body: form.querySelector('[name="body"]').value.trim(),
          meta: form.querySelector('[name="meta"]').value.trim(),
        });
        clearForm();
        await load();
        toast('Review saved');
      } catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });

    listEl.addEventListener('click', async (e) => {
      const edit = e.target.closest('[data-edit]');
      const del = e.target.closest('[data-del]');
      if (edit) {
        const r = editCache[edit.dataset.edit];
        if (!r) return;
        form.querySelector('[name="id"]').value = r.id;
        form.querySelector('[name="author"]').value = r.author || '';
        form.querySelector('[name="rating"]').value = r.rating || 5;
        form.querySelector('[name="body"]').value = r.body || '';
        form.querySelector('[name="meta"]').value = r.meta || '';
        form.querySelector('[data-rev-submit]').textContent = 'Update review';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
      }
      if (del) {
        if (!confirm('Delete this review?')) return;
        try { await api.post('api/reviews/delete.php', { id: +del.dataset.del }); await load(); toast('Review deleted'); }
        catch (err) { toast(err.message, 'error'); }
      }
    });

    let editCache = {};
    async function load() {
      try {
        const reviews = (await api.get('api/reviews/list.php')).reviews || [];
        editCache = {};
        reviews.forEach((r) => { editCache[r.id] = r; });
        listEl.innerHTML = reviews.length ? reviews.map((r) =>
          '<div class="blog-admin__item">' +
          '<div class="blog-admin__meta">' +
          '<div style="color:var(--coral)">' + '★'.repeat(r.rating) + '</div>' +
          '<div class="blog-admin__title">' + escapeHtml(r.author) + '</div>' +
          '<div class="blog-admin__date">' + escapeHtml(r.body) + '</div>' +
          (r.meta ? '<div class="blog-admin__date">' + escapeHtml(r.meta) + '</div>' : '') + '</div>' +
          '<div class="blog-admin__actions"><button class="btn-soft" data-edit="' + r.id + '">Edit</button> ' +
          '<button class="btn-soft" data-del="' + r.id + '">Delete</button></div></div>').join('')
          : '<p style="color:var(--muted)">No reviews yet — add your real Google reviews above. Until then the homepage shows sample testimonials.</p>';
      } catch (e) { toast(e.message, 'error'); }
    }

    load();
    loadUrl();
    refresh.reviews = load;
  }

  /* ----------  CRM / Leads  ---------- */
  function initCrm(panel) {
    const STAGES = ['new', 'contacted', 'quoted', 'won', 'lost'];
    let leads = [], filter = 'all';
    panel.innerHTML =
      '<p class="app-sub" style="margin-top:0">Every quote request is a lead. Move it through the pipeline and keep notes — call or text with one tap.</p>' +
      '<div class="tabs" data-crm-filter style="margin-top:0">' +
      ['all'].concat(STAGES).map((s) => '<button class="tab' + (s === 'all' ? ' is-active' : '') + '" data-f="' + s + '">' + cap(s) + '</button>').join('') +
      '</div><ul class="booking-list" data-crm-list></ul>';
    const listEl = panel.querySelector('[data-crm-list]');

    panel.querySelector('[data-crm-filter]').addEventListener('click', (e) => {
      const b = e.target.closest('[data-f]'); if (!b) return;
      filter = b.dataset.f;
      panel.querySelectorAll('[data-crm-filter] .tab').forEach((t) => t.classList.toggle('is-active', t === b));
      render();
    });

    function phoneOf(l) { return l.phone || l.guest_phone || ''; }

    function item(l) {
      const phone = phoneOf(l);
      const tel = phone.replace(/[^+0-9]/g, '');
      const contactLinks =
        (l.customer_email ? '<a href="mailto:' + escapeHtml(l.customer_email) + '">' + escapeHtml(l.customer_email) + '</a>' : '') +
        (phone ? (l.customer_email ? ' · ' : '') +
          '<a href="tel:' + escapeHtml(tel) + '">📞 ' + escapeHtml(phone) + '</a> ' +
          '<a href="sms:' + escapeHtml(tel) + '">💬 Text</a>' : '');
      const stageSel = '<select data-stage data-id="' + l.id + '">' +
        STAGES.map((s) => '<option value="' + s + '"' + (s === l.lead_stage ? ' selected' : '') + '>' + cap(s) + '</option>').join('') + '</select>';
      return '<li class="booking-item" data-lead="' + l.id + '"><div class="booking-item__head">' +
        '<span class="booking-item__title">#' + l.id + ' · ' + escapeHtml(l.service_type) + '</span>' +
        '<span class="badge badge--' + l.status + '">' + l.status + '</span></div>' +
        '<p class="booking-item__meta"><strong>' + escapeHtml(l.customer_name || 'Guest') + '</strong></p>' +
        (contactLinks ? '<p class="booking-item__meta">' + contactLinks + '</p>' : '') +
        '<p class="booking-item__meta">Preferred: ' + fmt(l.preferred_at) + (l.scheduled_at ? ' · Scheduled: ' + fmt(l.scheduled_at) : '') + '</p>' +
        '<p class="booking-item__meta">' + escapeHtml(l.address || '') + '</p>' +
        '<div class="booking-actions" style="align-items:center;gap:.5rem"><span style="color:var(--muted)">Stage:</span> ' + stageSel + '</div>' +
        '<label class="field" style="margin-top:.5rem"><span>Notes</span>' +
        '<textarea data-notes rows="2">' + escapeHtml(l.crm_notes || '') + '</textarea></label>' +
        '<div class="booking-actions"><button class="btn-soft" data-save-notes data-id="' + l.id + '">Save notes</button></div></li>';
    }
    function render() {
      const vis = filter === 'all' ? leads : leads.filter((l) => l.lead_stage === filter);
      listEl.innerHTML = vis.length ? vis.map(item).join('') : '<li style="color:var(--muted)">No leads in this stage.</li>';
    }

    listEl.addEventListener('change', async (e) => {
      const sel = e.target.closest('[data-stage]'); if (!sel) return;
      const id = +sel.dataset.id;
      try {
        const d = await api.post('api/crm/update.php', { id, leadStage: sel.value });
        const i = leads.findIndex((x) => String(x.id) === String(id));
        if (i >= 0) leads[i] = d.lead;
        toast('Stage updated');
        if (filter !== 'all') render();
      } catch (err) { toast(err.message, 'error'); }
    });
    listEl.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-save-notes]'); if (!btn) return;
      const id = +btn.dataset.id;
      const li = btn.closest('[data-lead]');
      const notes = li.querySelector('[data-notes]').value;
      btn.disabled = true;
      try {
        const d = await api.post('api/crm/update.php', { id, notes });
        const i = leads.findIndex((x) => String(x.id) === String(id));
        if (i >= 0) leads[i] = d.lead;
        toast('Notes saved');
      } catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });

    async function load() {
      try { leads = (await api.get('api/appointments/list.php')).appointments || []; render(); }
      catch (e) { toast(e.message, 'error'); }
    }
    load();
    refresh.leads = load;
  }

  /* ----------  Careers  ---------- */
  function initCareers(panel) {
    const EMP_TYPES = ['full_time', 'part_time', 'contract'];
    const EMP_LABELS = { full_time: 'Full-time', part_time: 'Part-time', contract: 'Contract' };
    const APP_STATUSES = ['new', 'reviewed', 'hired', 'rejected'];
    let view = 'positions', positions = [], applications = [], appFilter = 'all', applicationsLoaded = false;

    panel.innerHTML =
      '<div class="tabs" data-cr-view style="margin-top:0">' +
      '<button class="tab is-active" data-v="positions">Positions</button>' +
      '<button class="tab" data-v="applicants">Applicants</button>' +
      '</div><div data-cr-positions></div><div data-cr-applicants hidden></div>';

    const viewTabs = panel.querySelector('[data-cr-view]');
    const positionsEl = panel.querySelector('[data-cr-positions]');
    const applicantsEl = panel.querySelector('[data-cr-applicants]');

    viewTabs.addEventListener('click', (e) => {
      const b = e.target.closest('[data-v]'); if (!b) return;
      view = b.dataset.v;
      viewTabs.querySelectorAll('.tab').forEach((t) => t.classList.toggle('is-active', t === b));
      positionsEl.hidden = view !== 'positions';
      applicantsEl.hidden = view !== 'applicants';
      if (view === 'applicants' && !applicationsLoaded) { applicationsLoaded = true; loadApplications(); }
    });

    /* ---- Positions ---- */
    positionsEl.innerHTML =
      '<form class="app-card" data-pos-form>' +
      '<input type="hidden" name="id" value="">' +
      '<label class="field"><span>Title</span><input type="text" name="title" maxlength="150" required></label>' +
      '<label class="field"><span>Description</span><textarea name="description" rows="4" required></textarea></label>' +
      '<label class="field"><span>Requirements (optional)</span><textarea name="requirements" rows="3"></textarea></label>' +
      '<label class="field"><span>Employment type</span><select name="employmentType">' + EMP_TYPES.map((t) => '<option value="' + t + '">' + EMP_LABELS[t] + '</option>').join('') + '</select></label>' +
      '<label class="field"><span>Pay range (optional)</span><input type="text" name="payRange" maxlength="100" placeholder="e.g. $20-25/hr"></label>' +
      '<label class="field"><span>Status</span><select name="status"><option value="open">Open</option><option value="closed">Closed</option></select></label>' +
      '<div class="booking-actions"><button class="btn-primary" type="submit" data-pos-submit>Add position</button> ' +
      '<button class="btn-soft" type="button" data-pos-reset>New / clear</button></div>' +
      '</form>' +
      '<ul class="booking-list" data-pos-list></ul>';
    const posForm = positionsEl.querySelector('[data-pos-form]');
    const posList = positionsEl.querySelector('[data-pos-list]');

    function clearPosForm() {
      posForm.reset();
      posForm.querySelector('[name="id"]').value = '';
      posForm.querySelector('[data-pos-submit]').textContent = 'Add position';
    }
    posForm.querySelector('[data-pos-reset]').addEventListener('click', clearPosForm);

    posForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = posForm.querySelector('[data-pos-submit]');
      btn.disabled = true;
      try {
        await api.post('api/careers/positions-save.php', {
          id: +posForm.querySelector('[name="id"]').value || 0,
          title: posForm.title.value.trim(),
          description: posForm.description.value.trim(),
          requirements: posForm.requirements.value.trim(),
          employmentType: posForm.employmentType.value,
          payRange: posForm.payRange.value.trim(),
          status: posForm.status.value,
        });
        clearPosForm();
        await loadPositions();
        toast('Position saved');
      } catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });

    posList.addEventListener('click', async (e) => {
      const editBtn = e.target.closest('[data-pos-edit]');
      if (editBtn) {
        const p = positions.find((x) => String(x.id) === editBtn.dataset.posEdit);
        if (!p) return;
        posForm.querySelector('[name="id"]').value = p.id;
        posForm.title.value = p.title || '';
        posForm.description.value = p.description || '';
        posForm.requirements.value = p.requirements || '';
        posForm.employmentType.value = p.employment_type;
        posForm.payRange.value = p.pay_range || '';
        posForm.status.value = p.status;
        posForm.querySelector('[data-pos-submit]').textContent = 'Update position';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
      }
      const delBtn = e.target.closest('[data-pos-del]');
      if (!delBtn) return;
      if (!confirm('Delete this position? Existing applications will keep a record of the position title.')) return;
      try { await api.post('api/careers/positions-delete.php', { id: +delBtn.dataset.posDel }); await loadPositions(); toast('Position deleted'); }
      catch (err) { toast(err.message, 'error'); }
    });

    function posItem(p) {
      return '<li class="booking-item">' +
        '<div class="booking-item__head"><span class="booking-item__title">' + escapeHtml(p.title) + '</span>' +
        '<span class="badge badge--' + (p.status === 'open' ? 'confirmed' : 'cancelled') + '">' + p.status + '</span></div>' +
        '<p class="booking-item__meta">' + EMP_LABELS[p.employment_type] + (p.pay_range ? ' · ' + escapeHtml(p.pay_range) : '') + '</p>' +
        '<div class="booking-actions">' +
        '<button class="btn-soft" data-pos-edit="' + p.id + '">Edit</button> ' +
        '<button class="btn-soft" data-pos-del="' + p.id + '">Delete</button></div></li>';
    }
    async function loadPositions() {
      try {
        positions = (await api.get('api/careers/positions-list.php')).positions || [];
        posList.innerHTML = positions.length ? positions.map(posItem).join('') : '<li style="color:var(--muted)">No positions yet.</li>';
      } catch (e) { toast(e.message, 'error'); }
    }

    /* ---- Applicants ---- */
    applicantsEl.innerHTML =
      '<div class="tabs" data-app-filter style="margin-top:0">' +
      ['all'].concat(APP_STATUSES).map((s) => '<button class="tab' + (s === 'all' ? ' is-active' : '') + '" data-f="' + s + '">' + cap(s) + '</button>').join('') +
      '</div><ul class="booking-list" data-app-list></ul>';
    const appFilterEl = applicantsEl.querySelector('[data-app-filter]');
    const appList = applicantsEl.querySelector('[data-app-list]');

    appFilterEl.addEventListener('click', (e) => {
      const b = e.target.closest('[data-f]'); if (!b) return;
      appFilter = b.dataset.f;
      appFilterEl.querySelectorAll('.tab').forEach((t) => t.classList.toggle('is-active', t === b));
      renderApplications();
    });

    function appItem(a) {
      const statusSel = '<select data-app-status data-id="' + a.id + '">' +
        APP_STATUSES.map((s) => '<option value="' + s + '"' + (s === a.status ? ' selected' : '') + '>' + cap(s) + '</option>').join('') + '</select>';
      return '<li class="booking-item">' +
        '<div class="booking-item__head"><span class="booking-item__title">' + escapeHtml(a.name) + ' — ' + escapeHtml(a.position_title || '') + '</span>' +
        '<span class="badge badge--' + a.status + '">' + a.status + '</span></div>' +
        '<p class="booking-item__meta"><a href="mailto:' + escapeHtml(a.email) + '">' + escapeHtml(a.email) + '</a> · <a href="tel:' + escapeHtml(a.phone) + '">' + escapeHtml(a.phone) + '</a></p>' +
        '<p class="booking-item__meta">Experience: ' + escapeHtml(a.years_experience || '—') + ' · Availability: ' + escapeHtml(a.availability || '—') + '</p>' +
        (a.message ? '<p class="booking-item__meta">' + escapeHtml(a.message) + '</p>' : '') +
        '<p class="booking-item__meta">Submitted: ' + fmt(a.created_at) + '</p>' +
        '<div class="booking-actions" style="align-items:center;gap:.5rem">' +
        '<a class="btn-soft" href="' + api.url('api/careers/resume-download.php?id=' + a.id) + '" target="_blank" rel="noopener">Download resume</a>' +
        '<span style="color:var(--muted)">Status:</span> ' + statusSel + '</div></li>';
    }
    function renderApplications() {
      const vis = appFilter === 'all' ? applications : applications.filter((a) => a.status === appFilter);
      appList.innerHTML = vis.length ? vis.map(appItem).join('') : '<li style="color:var(--muted)">No applicants in this status.</li>';
    }
    appList.addEventListener('change', async (e) => {
      const sel = e.target.closest('[data-app-status]'); if (!sel) return;
      const id = +sel.dataset.id;
      try {
        const d = await api.post('api/careers/applications-update.php', { id, status: sel.value });
        const i = applications.findIndex((x) => String(x.id) === String(id));
        if (i >= 0) applications[i] = d.application;
        toast('Status updated');
        renderApplications();
      } catch (err) { toast(err.message, 'error'); }
    });
    async function loadApplications() {
      try {
        applications = (await api.get('api/careers/applications-list.php')).applications || [];
        renderApplications();
      } catch (e) { toast(e.message, 'error'); }
    }

    loadPositions();
    refresh.careers = () => { loadPositions(); if (applicationsLoaded) loadApplications(); };
  }

  const MODULES = { overview: initOverview, chat: initChat, leads: initCrm, bookings: initBookings, reports: initReports, gallery: initGallery, blog: initBlog, reviews: initReviews, careers: initCareers };

  document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelector('[data-tabs]');
    if (!tabs) return;
    const panels = {};
    Object.keys(MODULES).forEach((n) => { panels[n] = document.querySelector('[data-panel="' + n + '"]'); });

    function show(name) {
      tabs.querySelectorAll('.tab').forEach((t) => t.classList.toggle('is-active', t.dataset.tab === name));
      Object.keys(panels).forEach((n) => { panels[n].hidden = n !== name; });
      if (!loaded[name]) { loaded[name] = true; MODULES[name](panels[name]); }
      else if (refresh[name]) refresh[name]();
    }
    tabs.addEventListener('click', (e) => { const b = e.target.closest('[data-tab]'); if (b) show(b.dataset.tab); });

    // Default tab.
    MODULES.overview(panels.overview);
    loaded.overview = true;
  });
})();
