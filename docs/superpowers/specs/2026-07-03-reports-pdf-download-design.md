# Reports Tab — Download PDF (Print-to-PDF)

**Date:** 2026-07-03
**Status:** Approved (design)

## Summary

Add a "Download PDF" button to the admin dashboard's Reports tab (built previously — daily/weekly/monthly booking counts). Clicking it triggers the browser's native print dialog (`window.print()`) with a dedicated print stylesheet that hides all site/admin chrome and shows only the report table plus a generated heading. The user picks "Save as PDF" as the print destination — no server-side PDF library, no new dependency, no build step.

## Goals

- A "Download PDF" button inside the Reports panel, next to the Daily/Weekly/Monthly toggle buttons.
- Clicking it opens the browser print dialog showing only: a heading ("Booking Report — {Period} — Generated {today's date}") and the currently-visible report table — no nav, footer, chat widget, tab bar, or other admin panels.
- Works for whichever period view (Daily/Weekly/Monthly) is currently selected when the button is clicked.

## Non-Goals (YAGNI)

- No server-side PDF generation library (no TCPDF/mPDF/Dompdf) — this project has no `composer.json` today, and adding one just for this feature is a much bigger footprint than the request calls for.
- No client-side PDF library (jsPDF, etc.) — ruled out in favor of browser print-to-PDF per the owner's choice.
- No print styling for any other page/tab — scoped only to the Reports panel. (Hiding sitewide chrome like `.site-header`/`.site-footer`/the chat widget on print is a reasonable universal default and costs nothing extra to include, but no other tab's *content* gets special print treatment.)
- No "print date range" customization — the printed table shows exactly what's on screen (whichever period/rows are currently loaded), matching the existing toggle-driven UI.

## Architecture

### Frontend: `assets/js/admin.js` — extend `initReports(panel)`

- Add a "Download PDF" button next to the toggle buttons, e.g. inside the existing `data-rp-filter` toolbar row (or immediately after it) as `<button type="button" class="btn-soft" data-rp-print>Download PDF</button>`.
- Add a hidden `<div class="report-print-heading" data-rp-heading></div>` immediately above the `data-rp-table` container — invisible on screen, shown only in print (via CSS, see below).
- Wire a click handler on `[data-rp-print]`: before calling `window.print()`, set the heading's text to `"Booking Report — " + LABELS[view] + " — Generated " + new Date().toLocaleDateString(...)`.
- No changes to `load()`/`render()` — the button only needs to read the current `view` variable already tracked by the existing toggle logic.

### CSS: `assets/css/styles.css` — new `@media print` block

```css
.report-print-heading { display: none; }

@media print {
  .site-header, .site-footer, .chat-bubble, .chat-panel, .toast { display: none !important; }
  .admin .tabs, .admin [data-panel]:not([data-panel="reports"]) { display: none !important; }
  .admin { padding: 0; max-width: none; }
  .report-print-heading { display: block; margin-bottom: 1rem; font-weight: 700; font-size: 1.1rem; }
  .report-table-wrap [data-rp-filter], [data-rp-print] { display: none !important; }
}
```

- `.site-header`/`.site-footer`/`.chat-bubble`/`.chat-panel`/`.toast` are hidden unconditionally on any print (sensible sitewide default; these classes are never something a user wants printed, on any page).
- `.admin .tabs` and `.admin [data-panel]:not([data-panel="reports"])` are scoped to the admin dashboard specifically (these class/attribute combinations only exist there), so this doesn't touch any other page's print output.
- The Reports panel's own toggle row (`[data-rp-filter]`) and the Download PDF button itself (`[data-rp-print]`) are hidden in print too — only the heading and the table should appear.

## Testing

- Manual: click "Download PDF" on each of the 3 period views, confirm the print preview (browser print dialog) shows only the heading + table, with correct period label and today's date, and no site chrome.
- Confirm normal (non-print) view is unaffected — the button and toggles remain visible/functional on screen; `.report-print-heading` stays hidden until print is invoked.
