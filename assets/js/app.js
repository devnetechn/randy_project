/* Shared front-end behaviour: nav, FAQ accordion, before/after slider, helpers. */
(function () {
  'use strict';

  // ---- API helpers ----
  const BASE = window.BASE_URL || '/';
  window.api = {
    url(path) {
      return BASE.replace(/\/$/, '') + '/' + String(path).replace(/^\//, '');
    },
    async get(path) {
      const res = await fetch(this.url(path), { headers: { Accept: 'application/json' } });
      return handle(res);
    },
    async post(path, body) {
      const res = await fetch(this.url(path), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(body || {}),
      });
      return handle(res);
    },
    async upload(path, formData) {
      const res = await fetch(this.url(path), { method: 'POST', body: formData });
      return handle(res);
    },
  };
  async function handle(res) {
    let data = null;
    try { data = await res.json(); } catch (_) { /* no body */ }
    if (!res.ok) {
      throw new Error((data && data.error) || 'Request failed (' + res.status + ')');
    }
    return data;
  }

  // ---- Toast ----
  let toastTimer = null;
  window.toast = function (message, type) {
    const el = document.querySelector('[data-toast]');
    if (!el) return;
    el.textContent = message;
    el.className = 'toast is-show' + (type === 'error' ? ' toast--error' : '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { el.className = 'toast'; }, 3000);
  };

  // ---- Escape helper for innerHTML ----
  window.escapeHtml = function (s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  };

  document.addEventListener('DOMContentLoaded', function () {
    // Mobile nav toggle
    const toggle = document.querySelector('[data-nav-toggle]');
    const panel = document.querySelector('[data-nav-panel]');
    if (toggle && panel) {
      toggle.addEventListener('click', function () {
        const open = panel.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', String(open));
      });
    }

    // FAQ accordions
    document.querySelectorAll('[data-faq]').forEach(function (item) {
      const btn = item.querySelector('[data-faq-q]');
      if (!btn) return;
      btn.addEventListener('click', function () {
        const open = item.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', String(open));
      });
    });

    // Before/after sliders
    document.querySelectorAll('[data-ba]').forEach(function (ba) {
      const range = ba.querySelector('[data-ba-range]');
      const after = ba.querySelector('[data-ba-after]');
      const handle = ba.querySelector('[data-ba-handle]');
      const knob = ba.querySelector('[data-ba-knob]');
      if (!range) return;
      const apply = function (v) {
        after.style.clipPath = 'inset(0 0 0 ' + v + '%)';
        handle.style.left = v + '%';
        knob.style.left = v + '%';
      };
      range.addEventListener('input', function () { apply(this.value); });
      apply(range.value);
    });
  });
})();
