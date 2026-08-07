/* Public blog: live-filter the post cards as the visitor types. */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('blogSearch');
    const list = document.getElementById('blogList');
    const grid = document.getElementById('blogGrid');
    const noResults = document.getElementById('blogNoResults');
    const noResultsTerm = document.getElementById('blogNoResultsTerm');
    if (!input || !grid) return;

    const cards = Array.prototype.slice.call(grid.querySelectorAll('.blog-card'));

    function filter() {
      const query = input.value.trim().toLowerCase();
      const terms = query.split(/\s+/).filter(Boolean);
      let visible = 0;

      if (list) list.classList.toggle('is-searching', terms.length > 0);

      cards.forEach(function (card) {
        const haystack = card.getAttribute('data-search') || '';
        const match = terms.every(function (t) { return haystack.indexOf(t) !== -1; });
        card.hidden = !match;
        if (match) visible++;
      });

      if (noResults) {
        const showEmpty = terms.length > 0 && visible === 0;
        noResults.hidden = !showEmpty;
        if (showEmpty && noResultsTerm) noResultsTerm.textContent = '“' + input.value.trim() + '”';
      }
    }

    input.addEventListener('input', filter);
  });
})();
