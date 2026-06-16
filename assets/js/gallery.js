/* Public gallery: load images from the API and filter by category. */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    const grid = document.querySelector('[data-gallery-grid]');
    const empty = document.querySelector('[data-gallery-empty]');
    const filters = document.querySelector('[data-gallery-filters]');
    if (!grid) return;

    let images = [];
    let active = 'all';

    function render() {
      const visible = active === 'all' ? images : images.filter((i) => i.category === active);
      if (images.length === 0) {
        grid.innerHTML = '';
        if (empty) empty.style.display = 'block';
        return;
      }
      if (empty) empty.style.display = 'none';
      grid.innerHTML = visible.map(function (img) {
        return (
          '<a class="gallery-item" href="' + escapeHtml(img.projectUrl) + '">' +
          '<img src="' + escapeHtml(img.url) + '" alt="' + escapeHtml(img.caption || 'Project photo') + '" loading="lazy">' +
          '<span class="gallery-item__cap">' + escapeHtml(img.caption || 'Recent work') +
          ' <span>' + escapeHtml(img.category) + '</span></span>' +
          '<span class="gallery-item__view">View project &rarr;</span>' +
          '</a>'
        );
      }).join('') || '<p class="center" style="color:var(--muted)">No photos in this category yet.</p>';
    }

    if (filters) {
      filters.addEventListener('click', function (ev) {
        const btn = ev.target.closest('[data-filter]');
        if (!btn) return;
        active = btn.getAttribute('data-filter');
        filters.querySelectorAll('.filter-btn').forEach((b) => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        render();
      });
    }

    api.get('api/gallery/list.php')
      .then(function (data) { images = data.images || []; render(); })
      .catch(function () { images = []; render(); });
  });
})();
