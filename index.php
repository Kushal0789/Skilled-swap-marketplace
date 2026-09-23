<?php
/**
 * Landing Page (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = get_db();
$stats = get_platform_stats($pdo);

// Featured categories & skills
$featuredCategories = [
    [
        'title' => 'Programming & Tech',
        'icon'  => '💻',
        'skills'=> ['Python', 'JavaScript', 'PHP & MySQL', 'Java', 'HTML5/CSS3'],
        'badge' => 'High Demand'
    ],
    [
        'title' => 'Design & Creative',
        'icon'  => '🎨',
        'skills'=> ['Adobe Photoshop', 'UI/UX Design', 'Figma', 'Video Editing'],
        'badge' => 'Popular'
    ],
    [
        'title' => 'Languages & Academics',
        'icon'  => '🌍',
        'skills'=> ['English Writing', 'Spanish Conversation', 'College Calculus'],
        'badge' => 'Essential'
    ],
    [
        'title' => 'Music & Arts',
        'icon'  => '🎸',
        'skills'=> ['Acoustic Guitar', 'Digital Photography', 'Music Production'],
        'badge' => 'Creative'
    ],
    [
        'title' => 'Business & Marketing',
        'icon'  => '📈',
        'skills'=> ['Public Speaking', 'Digital Marketing', 'SEO Strategy'],
        'badge' => 'Career'
    ]
];

$pageTitle = 'Skill Swap Marketplace - Learn. Share. Connect.';
include __DIR__ . '/includes/header.php';
?>

<div class="landing-hero-section">
    <div class="hero-container">
        <!-- Floating Badges / Visual Glow -->
        <div class="hero-badge">
            <span class="pulse-dot"></span> Peer-to-Peer Knowledge Economy
        </div>

        <h1 class="hero-title">
            Learn. Share. Connect.<br>
            <span class="text-gradient">Exchange Skills, Zero Money.</span>
        </h1>

        <p class="hero-subtitle">
            Skill Swap connects people with complementary talents. Know Python and want to learn Photoshop? 
            Find a designer wanting to code, and tutor each other 1-on-1.
        </p>

        <div class="hero-cta-group">
            <?php if (is_logged_in()): ?>
                <a href="matches.php" class="btn btn-primary btn-lg">View Your Matches &rarr;</a>
                <a href="discover.php" class="btn btn-secondary btn-lg">Explore Members</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary btn-lg">Get Started Free</a>
                <a href="discover.php" class="btn btn-secondary btn-lg">Explore Catalog</a>
            <?php endif; ?>
        </div>

        <!-- Live Platform Stats (Dynamic from DB) -->
        <div class="hero-stats-strip">
            <div class="stat-item">
                <span class="stat-number"><?= number_format($stats['users']) ?>+</span>
                <span class="stat-label">Active Members</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-number"><?= number_format($stats['skills']) ?></span>
                <span class="stat-label">Skills Catalog</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-number"><?= number_format($stats['offerings']) ?></span>
                <span class="stat-label">Listed Offerings</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-number"><?= number_format($stats['swaps']) ?></span>
                <span class="stat-label">Successful Swaps</span>
            </div>
        </div>
    </div>
</div>

<!-- How It Works Section -->
<section class="section-container" id="how-it-works">
    <div class="section-header">
        <span class="section-eyebrow">Seamless Flow</span>
        <h2 class="section-heading">How Skill Swap Works</h2>
        <p class="section-subheading">A straightforward 4-step cycle designed for mutual learning and growth.</p>
    </div>

    <div class="steps-grid">
        <div class="step-card">
            <div class="step-number">01</div>
            <div class="step-icon-box">👤</div>
            <h3>Create Your Profile</h3>
            <p>Sign up in seconds. Tell the community about your interests, your background, and your learning goals.</p>
        </div>

        <div class="step-card">
            <div class="step-number">02</div>
            <div class="step-icon-box">💡</div>
            <h3>List Your Skills</h3>
            <p>Add skills you can comfortably teach (Offer) and skills you are excited to master (Want) with proficiency levels.</p>
        </div>

        <div class="step-card">
            <div class="step-number">03</div>
            <div class="step-icon-box">⚡</div>
            <h3>Intelligent Match</h3>
            <p>Our matchmaker algorithm detects mutual 2-way matches where you teach what they want and they teach what you want.</p>
        </div>

        <div class="step-card">
            <div class="step-number">04</div>
            <div class="step-icon-box">🤝</div>
            <h3>Connect & Exchange</h3>
            <p>Send a swap proposal, coordinate sessions via integrated private messaging, and leave reviews upon completion.</p>
        </div>
    </div>
</section>

<!-- Featured Skills Section -->
<section class="section-container" id="featured-skills">
    <div class="section-header">
        <span class="section-eyebrow">Explore Topics</span>
        <h2 class="section-heading">Popular Swap Disciplines</h2>
        <p class="section-subheading">From software engineering to creative arts, discover hundreds of subjects.</p>
    </div>

    <div class="category-grid">
        <?php foreach ($featuredCategories as $cat): ?>
            <div class="category-card">
                <div class="cat-header">
                    <span class="cat-icon"><?= $cat['icon'] ?></span>
                    <span class="cat-badge"><?= $cat['badge'] ?></span>
                </div>
                <h3><?= e($cat['title']) ?></h3>
                <div class="cat-skills-pills">
                    <?php foreach ($cat['skills'] as $s): ?>
                        <span class="skill-pill"><?= e($s) ?></span>
                    <?php endforeach; ?>
                </div>
                <a href="discover.php?category=<?= urlencode(explode(' ', $cat['title'])[0]) ?>" class="cat-link">
                    Browse Category &rarr;
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Reciprocal Swap Demonstration -->
<section class="section-container">
    <div class="swap-demo-card">
        <div class="swap-demo-content">
            <span class="badge-pill bg-primary" style="align-self: flex-start; margin-bottom: 0.75rem;">Real Example</span>
            <h2>Two-Way Reciprocal Matching in Action</h2>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem; line-height: 1.6;">
                Imagine Sarah is a backend engineer who wants to design her own app in Photoshop. Alex is a graphics designer wanting to learn Python scripts.
                Skill Swap instantly identifies the reciprocal match and pairs them for an equal knowledge exchange.
            </p>
            <div class="match-exchange-visual" style="max-width: 500px;">
                <div class="exchange-node">
                    <div class="exchange-node-title">Sarah Chen</div>
                    <div class="exchange-node-skill" style="color: var(--primary);">Teaches Python</div>
                </div>
                <div class="exchange-arrows">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="7 10 12 15 17 10"></polyline><polyline points="17 14 12 9 7 14"></polyline></svg>
                </div>
                <div class="exchange-node">
                    <div class="exchange-node-title">Alex Miller</div>
                    <div class="exchange-node-skill" style="color: var(--success);">Teaches Photoshop</div>
                </div>
            </div>
            <div style="margin-top: 1.5rem;">
                <a href="register.php" class="btn btn-primary">Find Your Swap Partner</a>
            </div>
        </div>
    </div>
</section>

<style>
/* Landing Page Specific Styling */
.landing-hero-section {
    position: relative;
    padding: 6rem 1.5rem 4rem;
    text-align: center;
    overflow: hidden;
    background: var(--gradient-glow);
}

.hero-container {
    max-width: 900px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--primary);
    background: var(--primary-glow);
    border: 1px solid rgba(99, 102, 241, 0.3);
    padding: 6px 16px;
    border-radius: var(--radius-full);
    margin-bottom: 1.5rem;
}

.pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--primary);
    box-shadow: 0 0 0 0 var(--primary);
    animation: pulse 1.8s infinite;
}

@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(99, 102, 241, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
}

.hero-title {
    font-size: 3.5rem;
    letter-spacing: -0.03em;
    line-height: 1.15;
    margin-bottom: 1.25rem;
}

.text-gradient {
    background: var(--gradient-brand);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero-subtitle {
    font-size: 1.15rem;
    color: var(--text-secondary);
    max-width: 680px;
    margin-bottom: 2.25rem;
    line-height: 1.6;
}

.hero-cta-group {
    display: flex;
    gap: 1rem;
    margin-bottom: 3.5rem;
}

.hero-stats-strip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2rem;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    padding: 1.25rem 2.5rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-family: var(--font-heading);
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--primary);
    line-height: 1.1;
}

.stat-label {
    font-size: 0.78rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}

.stat-divider {
    width: 1px;
    height: 36px;
    background: var(--border-color);
}

/* Sections */
.section-container {
    max-width: 1280px;
    margin: 4rem auto;
    padding: 0 1.5rem;
}

.section-header {
    text-align: center;
    max-width: 650px;
    margin: 0 auto 3rem;
}

.section-eyebrow {
    font-size: 0.8rem;
    text-transform: uppercase;
    font-weight: 700;
    color: var(--primary);
    letter-spacing: 0.08em;
    margin-bottom: 0.5rem;
    display: block;
}

.section-heading {
    font-size: 2.25rem;
    letter-spacing: -0.02em;
    margin-bottom: 0.75rem;
}

.section-subheading {
    color: var(--text-secondary);
    font-size: 1.05rem;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.step-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 2rem 1.5rem;
    position: relative;
    transition: all var(--transition-normal);
}

.step-card:hover {
    border-color: var(--primary);
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.step-number {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    font-family: var(--font-heading);
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--text-muted);
    opacity: 0.35;
}

.step-icon-box {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.step-card h3 {
    font-size: 1.2rem;
    margin-bottom: 0.6rem;
}

.step-card p {
    font-size: 0.9rem;
    color: var(--text-secondary);
    line-height: 1.55;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.category-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.75rem;
    transition: all var(--transition-normal);
}

.category-card:hover {
    border-color: var(--primary);
    transform: translateY(-3px);
}

.cat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.cat-icon {
    font-size: 1.8rem;
}

.cat-badge {
    font-size: 0.72rem;
    font-weight: 700;
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    padding: 2px 8px;
    border-radius: var(--radius-full);
    color: var(--text-muted);
}

.category-card h3 {
    font-size: 1.25rem;
    margin-bottom: 1rem;
}

.cat-skills-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 1.25rem;
}

.skill-pill {
    font-size: 0.8rem;
    background: var(--bg-surface-elevated);
    padding: 3px 9px;
    border-radius: var(--radius-sm);
    color: var(--text-secondary);
}

.cat-link {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--primary);
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.swap-demo-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 3rem;
    position: relative;
    overflow: hidden;
}

@media (max-width: 768px) {
    .hero-title { font-size: 2.25rem; }
    .hero-stats-strip { flex-direction: column; gap: 1rem; }
    .stat-divider { display: none; }
    .hero-cta-group { flex-direction: column; width: 100%; }
    .swap-demo-card { padding: 1.5rem; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
