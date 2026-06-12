<?php
/** Shared footer, floating chat widget, and scripts. */
$bf = business_info();
$cu = current_user();
$show_widget = !($cu && $cu['role'] === 'admin');
?>
</main>

<div class="mkt">
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a href="<?= e(url('index.php')) ?>" class="footer-logo" aria-label="<?= e($bf['name']) ?> home"><img src="<?= e(url('assets/img/logo.png')) ?>" alt="<?= e($bf['name']) ?>"></a>
                    <p class="footer-about">
                        <em>&ldquo;We build our reputation, one coat at a time.&rdquo;</em><br>
                        High-end painting and drywall services &mdash; including Level&nbsp;5 finishes and
                        wall restoration &mdash; for homes and businesses across the Lehigh Valley and
                        Bucks County, PA. Licensed, insured, and based in Easton.
                    </p>
                    <div class="footer-social">
                        <a href="https://www.facebook.com/randys.aesthetics" aria-label="Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </a>
                        <a href="#" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                        </a>
                        <a href="https://share.google/jOEA4W8Vst9zQkl5u" aria-label="Google reviews">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6 6.6.5-5 4.3 1.5 6.4L12 16.9 6 19.2l1.5-6.4-5-4.3 6.6-.5z"/></svg>
                        </a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="<?= e(url('level-5-drywall.php')) ?>">Level 5 Drywall Finish</a></li>
                        <li><a href="<?= e(url('skim-coating.php')) ?>">Skim Coating</a></li>
                        <li><a href="<?= e(url('wall-restoration.php')) ?>">Wall Restoration</a></li>
                        <li><a href="<?= e(url('services.php')) ?>">Painting &amp; Drywall</a></li>
                        <li><a href="<?= e(url('services.php')) ?>">Commercial Work</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="<?= e(url('about.php')) ?>">About Us</a></li>
                        <li><a href="<?= e(url('gallery.php')) ?>">Gallery</a></li>
                        <li><a href="<?= e(url('book.php')) ?>">Get a Quote</a></li>
                        <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Get in touch</h4>
                    <ul class="footer-contact">
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <a href="tel:<?= e($bf['phoneTel']) ?>"><?= e($bf['phone']) ?></a>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                            <a href="mailto:<?= e($bf['email']) ?>"><?= e($bf['email']) ?></a>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <a href="https://<?= e($bf['website']) ?>" target="_blank" rel="noopener noreferrer"><?= e($bf['website']) ?></a>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            Mon–Sat, 8am–5pm
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2026 Randy&apos;s Painting &amp; Drywall Services. All rights reserved.</p>
                <p>Painting &amp; Drywall Services · Lehigh Valley &amp; Bucks County, PA</p>
            </div>
        </div>
    </footer>
</div>

<?php if ($show_widget): ?>
<!-- Floating chat widget -->
<button class="chat-bubble" type="button" aria-label="Chat with us" data-chat-open>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
</button>
<div class="chat-panel" role="dialog" aria-label="Chat with Randy's" data-chat-panel>
    <div class="chat-head">
        <div>
            <div class="title">Chat with Randy&apos;s</div>
            <div class="sub">Typically replies in a few minutes</div>
        </div>
        <button type="button" aria-label="Close chat" data-chat-close>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="chat-body" data-chat-body></div>
</div>
<?php endif; ?>

<div class="toast" data-toast></div>

<script>
    window.CURRENT_USER = <?= json_encode($cu ? ['id' => $cu['id'], 'email' => $cu['email'], 'role' => $cu['role']] : null) ?>;
</script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<script src="<?= e(url('assets/js/chat.js')) ?>"></script>
</body>
</html>
