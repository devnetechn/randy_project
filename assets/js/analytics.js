/**
 * Ad-campaign conversion tracking. Runs sitewide; safely no-ops when no
 * analytics IDs are configured (gtag/fbq will simply be undefined).
 */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        var link = e.target.closest && e.target.closest('a[href^="tel:"]');
        if (!link) return;

        var conf = window.ANALYTICS_CONFIG || {};

        if (typeof gtag === 'function') {
            gtag('event', 'phone_call_click', { event_category: 'engagement' });
            if (conf.googleAdsConversionId && conf.googleAdsConversionLabel) {
                gtag('event', 'conversion', {
                    send_to: conf.googleAdsConversionId + '/' + conf.googleAdsConversionLabel,
                });
            }
        }
        if (typeof fbq === 'function') {
            fbq('track', 'Contact');
        }
    }, true);
})();
