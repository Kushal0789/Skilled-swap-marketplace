<?php
/**
 * Global Footer Component (Presentation Layer)
 * Skill Swap Marketplace
 */

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
?>
    </main>

    <!-- Global Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-grid">
                <!-- Col 1: Brand & Mission -->
                <div class="footer-col brand-col">
                    <div class="footer-logo">
                        <span class="brand-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 3h5v5"></path>
                                <path d="M4 20L21 3"></path>
                                <path d="M21 16v5h-5"></path>
                                <path d="M15 15l6 6"></path>
                                <path d="M4 4l5 5"></path>
                            </svg>
                        </span>
                        <span class="brand-text">Skill<strong>Swap</strong></span>
                    </div>
                    <p class="footer-tagline">
                        A peer-to-peer knowledge exchange network where people learn by sharing. No money required — just pure skill reciprocity.
                    </p>
                    <div class="arch-badge">
                        <span class="arch-dot"></span> 3-Tier Architecture (Presentation &bull; Business &bull; Data)
                    </div>
                </div>

                <!-- Col 2: Quick Links -->
                <div class="footer-col">
                    <h4 class="footer-heading">Platform</h4>
                    <ul class="footer-nav">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="discover.php">Discover Skills</a></li>
                        <li><a href="matches.php">Skill Matchmaker</a></li>
                        <li><a href="index.php#how-it-works">How It Works</a></li>
                    </ul>
                </div>

                <!-- Col 3: Categories -->
                <div class="footer-col">
                    <h4 class="footer-heading">Popular Disciplines</h4>
                    <ul class="footer-nav">
                        <li><a href="discover.php?category=Programming">Programming & Code</a></li>
                        <li><a href="discover.php?category=Design">Design & Multimedia</a></li>
                        <li><a href="discover.php?category=Creative">Music & Creative Arts</a></li>
                        <li><a href="discover.php?category=Education">Languages & Education</a></li>
                        <li><a href="discover.php?category=Business">Business & Marketing</a></li>
                    </ul>
                </div>

                <!-- Col 4: Tech Stack & System -->
                <div class="footer-col">
                    <h4 class="footer-heading">Architecture & Stack</h4>
                    <ul class="footer-nav stack-list">
                        <li><span class="stack-tag">PHP 8.x</span> Pure Server Logic</li>
                        <li><span class="stack-tag">MySQL</span> Relational Database</li>
                        <li><span class="stack-tag">PDO</span> Prepared Statements</li>
                        <li><span class="stack-tag">ES6+</span> Vanilla Fetch API</li>
                        <li><span class="stack-tag">CSS3</span> Modern Flex/Grid & Themes</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Skill Swap Marketplace. Built for academic excellence & practical software engineering.</p>
                <div class="footer-bottom-links">
                    <a href="README.md" target="_blank">Documentation</a>
                    <a href="DOCUMENTATION.md" target="_blank">Viva Exam Guide</a>
                    <a href="admin.php">Admin Portal</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Universal Toast Container -->
    <div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="true"></div>

    <!-- Universal Dynamic Modal Backdrop -->
    <div id="app-modal" class="modal-backdrop hidden" role="dialog" aria-modal="true" tabindex="-1">
        <div class="modal-card" id="app-modal-card">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title">Modal Title</h3>
                <button type="button" class="btn-icon modal-close-btn" id="modal-close-btn" aria-label="Close modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Dynamically populated via JS -->
            </div>
        </div>
    </div>

    <!-- Core Application JavaScript -->
    <script src="assets/js/app.js"></script>
    <?php if (isset($pageScript) && !empty($pageScript)): ?>
        <script src="assets/js/<?= e($pageScript) ?>"></script>
    <?php endif; ?>
</body>
</html>
