/* Chat: guest (stateless) + logged-in customer (DB + polling). */
(function () {
  'use strict';

  const user = window.CURRENT_USER;
  const GUEST_KEY = 'guestChat';
  const GREETING = {
    sender_type: 'ai',
    body: "Hi! I'm Randy's virtual assistant — how can I help with your painting or drywall project?",
  };
  const BANNER = {
    waiting_human: 'Connecting you with a team member…',
    human: 'You are chatting with a team member.',
    closed: 'This conversation is closed.',
  };
  const LABELS = { ai: 'Assistant', admin: 'Team', customer: 'You' };

  function bubble(m) {
    if (m.sender_type === 'system') {
      return '<div class="msg msg--system"><div class="msg__sys">' + escapeHtml(m.body) + '</div></div>';
    }
    return (
      '<div class="msg msg--' + m.sender_type + '">' +
      '<span class="msg__who">' + (LABELS[m.sender_type] || '') + '</span>' +
      '<div class="msg__bubble">' + escapeHtml(m.body) + '</div></div>'
    );
  }

  function buildShell(body) {
    body.innerHTML =
      '<div class="chat-banner" data-banner hidden></div>' +
      '<div class="chat-messages" data-messages></div>' +
      '<div class="chat-typing" data-typing hidden>Assistant is typing…</div>' +
      '<div class="chat-human" data-human hidden></div>' +
      '<form class="chat-input" data-form>' +
      '<input type="text" placeholder="Type your message…" data-input autocomplete="off">' +
      '<button type="submit">Send</button></form>';
    return {
      banner: body.querySelector('[data-banner]'),
      messages: body.querySelector('[data-messages]'),
      typing: body.querySelector('[data-typing]'),
      human: body.querySelector('[data-human]'),
      form: body.querySelector('[data-form]'),
      input: body.querySelector('[data-input]'),
      sendBtn: body.querySelector('[data-form] button'),
    };
  }

  function loadGuest() {
    try { return JSON.parse(sessionStorage.getItem(GUEST_KEY)) || []; } catch (_) { return []; }
  }
  function saveGuest(m) { sessionStorage.setItem(GUEST_KEY, JSON.stringify(m)); }

  // ---------- Guest (not logged in) ----------
  function initGuest(body) {
    const el = buildShell(body);
    let messages = []; // conversation turns (excludes greeting)

    function render() {
      const display = [GREETING].concat(messages);
      el.messages.innerHTML = display.map(bubble).join('');
      el.messages.scrollTop = el.messages.scrollHeight;
    }

    el.human.hidden = false;
    el.human.innerHTML =
      '<button type="button" data-talk>Talk to the owner / a team member</button>' +
      '<p>Log in or create an account to chat with our team.</p>';
    el.human.querySelector('[data-talk]').addEventListener('click', function () {
      saveGuest(messages);
      window.location.href = api.url('login.php');
    });

    el.form.addEventListener('submit', async function (ev) {
      ev.preventDefault();
      const text = el.input.value.trim();
      if (!text) return;
      messages.push({ sender_type: 'customer', body: text });
      el.input.value = '';
      render();
      el.typing.hidden = false;
      try {
        const payload = [GREETING].concat(messages).map((m) => ({ sender_type: m.sender_type, body: m.body }));
        const data = await api.post('api/chat/guest_reply.php', { messages: payload });
        messages.push({ sender_type: 'ai', body: data.reply });
      } catch (_) {
        messages.push({ sender_type: 'ai', body: 'Sorry, something went wrong. Please try again.' });
      } finally {
        el.typing.hidden = true;
        render();
      }
    });

    render();
  }

  // ---------- Customer (logged in) ----------
  async function initCustomer(body) {
    const el = buildShell(body);
    const seen = new Set();
    let conversation = null;
    let lastId = 0;

    function append(msgs, replace) {
      if (replace) { el.messages.innerHTML = ''; seen.clear(); }
      msgs.forEach(function (m) {
        const id = Number(m.id) || 0;
        if (id && seen.has(id)) return;
        if (id) seen.add(id);
        el.messages.insertAdjacentHTML('beforeend', bubble(m));
        if (id > lastId) lastId = id;
      });
      el.messages.scrollTop = el.messages.scrollHeight;
    }

    function updateStatusUI() {
      const s = conversation.status;
      if (BANNER[s]) { el.banner.hidden = false; el.banner.textContent = BANNER[s]; }
      else { el.banner.hidden = true; }

      if (s === 'ai') {
        el.human.hidden = false;
        el.human.innerHTML = '<button type="button" data-req>Talk to a human</button>';
        el.human.querySelector('[data-req]').addEventListener('click', requestHuman);
      } else {
        el.human.hidden = true;
      }
      const closed = s === 'closed';
      el.input.disabled = closed;
      el.sendBtn.disabled = closed;
    }

    async function requestHuman() {
      try {
        await api.post('api/chat/request_human.php', { conversationId: conversation.id });
        conversation.status = 'waiting_human';
        updateStatusUI();
      } catch (e) { toast(e.message, 'error'); }
    }

    el.form.addEventListener('submit', async function (ev) {
      ev.preventDefault();
      const text = el.input.value.trim();
      if (!text || !conversation) return;
      el.input.value = '';
      if (conversation.status === 'ai') el.typing.hidden = false;
      try {
        const data = await api.post('api/chat/send.php', { conversationId: conversation.id, body: text });
        append(data.messages || [], false);
      } catch (e) {
        toast(e.message, 'error');
      } finally {
        el.typing.hidden = true;
      }
    });

    async function poll() {
      if (document.hidden || !conversation) return;
      try {
        const data = await api.get('api/chat/messages.php?conversation_id=' + conversation.id + '&after=' + lastId);
        if (data.conversation.status !== conversation.status) {
          conversation.status = data.conversation.status;
          updateStatusUI();
        }
        if (data.messages && data.messages.length) append(data.messages, false);
      } catch (_) { /* ignore transient errors */ }
    }

    // Boot: seed from a guest session if present, else resume/create.
    let reqBody = {};
    const saved = loadGuest();
    if (saved && saved.length) {
      reqBody = { seed: saved, requestHuman: true };
      sessionStorage.removeItem(GUEST_KEY);
    }
    try {
      const data = await api.post('api/chat/start.php', reqBody);
      conversation = data.conversation;
      append(data.messages || [], true);
      updateStatusUI();
      setInterval(poll, 3000);
    } catch (_) {
      el.messages.innerHTML = '<p style="padding:1rem;color:var(--coral)">Could not start chat. Please try again.</p>';
    }
  }

  function initChat(body) {
    if (!body || body.dataset.ready) return;
    body.dataset.ready = '1';
    if (user && user.role === 'customer') initCustomer(body);
    else if (!user) initGuest(body);
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Floating widget
    const bubbleBtn = document.querySelector('[data-chat-open]');
    const panel = document.querySelector('[data-chat-panel]');
    const closeBtn = document.querySelector('[data-chat-close]');
    const widgetBody = panel ? panel.querySelector('[data-chat-body]') : null;
    if (bubbleBtn && panel && widgetBody) {
      bubbleBtn.addEventListener('click', function () {
        panel.classList.add('is-open');
        bubbleBtn.style.display = 'none';
        initChat(widgetBody);
      });
      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          panel.classList.remove('is-open');
          bubbleBtn.style.display = '';
        });
      }
    }

    // Full-page chat
    const pageBody = document.querySelector('[data-chat-page] [data-chat-body]');
    if (pageBody) initChat(pageBody);
  });
})();
