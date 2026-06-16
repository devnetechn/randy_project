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
    const card = (k, l, badge) => '<div class="kpi"><div class="kpi__label">' + l + ' <span class="kpi__badge kpi__badge--' + badge + '">' + badge + '</span></div><div class="kpi__value" data-kpi="' + k + '">—</div></div>';
    panel.innerHTML =
      '<div class="kpi-section"><h2>Live now</h2><div class="kpi-grid">' + LIVE.map((c) => card(c[0], c[1], 'live')).join('') + '</div></div>' +
      '<div class="kpi-section"><h2>Today</h2><div class="kpi-grid">' + TODAY.map((c) => card(c[0], c[1], 'today')).join('') + '</div></div>';

    const set = (k, v) => { const el = panel.querySelector('[data-kpi="' + k + '"]'); if (el) el.textContent = v == null ? '—' : v; };
    async function load() {
      try {
        const d = await api.get('api/admin/overview.php');
        LIVE.forEach((c) => set(c[0], d.live[c[0]]));
        TODAY.forEach((c) => set(c[0], d.today[c[0]]));
      } catch (_) { /* ignore */ }
    }
    load();
    setInterval(() => { if (!document.hidden) load(); }, 30000);
    refresh.overview = load;
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

  const MODULES = { overview: initOverview, chat: initChat, bookings: initBookings, gallery: initGallery, blog: initBlog, reviews: initReviews };

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
