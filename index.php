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
                <a href="matches.php" class="btn btn-primary btn-lg">
                    <span>View Your Matches</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
                <a href="discover.php" class="btn btn-secondary btn-lg">Explore Members</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary btn-lg">
                    <span>Get Started Free</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
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
                    <span>Browse Category</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Reciprocal Swap Demonstration -->
<section class="section-container">
    <div class="swap-demo-card">
        <div class="swap-demo-content">
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.85rem;">
                <span class="status-badge accepted">Real-Time Simulation</span>
                <span class="match-ribbon" style="position: static;">100% Mutual Match</span>
            </div>
            <h2 style="font-size: 2rem; margin-bottom: 0.75rem;">Two-Way Reciprocal Matching in Action</h2>
            <p style="color: var(--text-secondary); margin-bottom: 1.75rem; line-height: 1.6; max-width: 650px;">
                Sarah Chen is a backend engineer looking to learn Adobe Photoshop. Alex Miller is a graphic designer looking for Python scripting lessons. Skill Swap matches them instantly for a 1-to-1 zero-cost barter.
            </p>

            <div class="match-exchange-visual" style="max-width: 620px; box-shadow: var(--shadow-md);">
                <div class="exchange-node" style="padding: 0.5rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Partner A</div>
                    <div style="font-weight: 700; font-size: 1.05rem; margin-bottom: 2px;">Sarah Chen</div>
                    <div class="skill-badge offer" style="font-size: 0.8rem; margin-top: 4px;">Teaches Python</div>
                </div>
                
                <div class="exchange-arrows">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l4-4m-4 4l-4-4"/></svg>
                </div>

                <div class="exchange-node" style="padding: 0.5rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Partner B</div>
                    <div style="font-weight: 700; font-size: 1.05rem; margin-bottom: 2px;">Alex Miller</div>
                    <div class="skill-badge want" style="font-size: 0.8rem; margin-top: 4px;">Teaches Photoshop</div>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <?php if (is_logged_in()): ?>
                    <a href="matches.php" class="btn btn-primary">Find Your Swap Partner &rarr;</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary">Join & Find Your Partner &rarr;</a>
                    <a href="discover.php" class="btn btn-secondary">Browse All 50+ Skills</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
/* Landing Page Specific Styling */
.landing-hero-section {
    position: relative;
    padding: 7rem 1.5rem 5rem;
    text-align: center;
    overflow: hidden;
    background: var(--gradient-glow);
}

.landing-hero-section::before {
    content: '';
    position: absolute;
    top: 20%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 600px;
    height: 350px;
    background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%);
    pointer-events: none;
    z-index: 0;
    filter: blur(50px);
}

.hero-container {
    max-width: 960px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--primary);
    background: var(--primary-glow);
    border: 1px solid rgba(99, 102, 241, 0.35);
    padding: 7px 18px;
    border-radius: var(--radius-full);
    margin-bottom: 1.75rem;
    box-shadow: 0 0 20px var(--primary-glow);
}

.pulse-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--primary);
    box-shadow: 0 0 0 0 var(--primary);
    animation: pulse 1.8s infinite;
}

@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
}

.hero-title {
    font-size: 3.8rem;
    letter-spacing: -0.035em;
    line-height: 1.12;
    margin-bottom: 1.35rem;
    font-weight: 800;
}

.text-gradient {
    background: var(--gradient-brand);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero-subtitle {
    font-size: 1.2rem;
    color: var(--text-secondary);
    max-width: 700px;
    margin-bottom: 2.5rem;
    line-height: 1.65;
}

.hero-cta-group {
    display: flex;
    gap: 1.25rem;
    margin-bottom: 4rem;
}

.hero-stats-strip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2.5rem;
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    padding: 1.5rem 3rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg), 0 0 35px rgba(0, 0, 0, 0.15);
    transition: transform var(--transition-normal);
}

.hero-stats-strip:hover {
    transform: translateY(-2px);
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-family: var(--font-heading);
    font-size: 2.1rem;
    font-weight: 800;
    background: var(--gradient-brand);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    line-height: 1.1;
}

.stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
    margin-top: 3px;
}

.stat-divider {
    width: 1px;
    height: 42px;
    background: var(--border-color);
}

/* Sections */
.section-container {
    max-width: 1280px;
    margin: 5rem auto;
    padding: 0 1.5rem;
}

.section-header {
    text-align: center;
    max-width: 680px;
    margin: 0 auto 3.5rem;
}

.section-eyebrow {
    font-size: 0.82rem;
    text-transform: uppercase;
    font-weight: 800;
    color: var(--primary);
    letter-spacing: 0.1em;
    margin-bottom: 0.6rem;
    display: block;
}

.section-heading {
    font-size: 2.4rem;
    letter-spacing: -0.025em;
    margin-bottom: 0.85rem;
}

.section-subheading {
    color: var(--text-secondary);
    font-size: 1.1rem;
    line-height: 1.55;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.75rem;
}

.step-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 2.25rem 1.75rem;
    position: relative;
    overflow: hidden;
    transition: all var(--transition-normal);
}

.step-card:hover {
    border-color: var(--primary);
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg), 0 0 25px var(--primary-glow);
}

.step-number {
    position: absolute;
    top: 1.25rem;
    right: 1.5rem;
    font-family: var(--font-heading);
    font-size: 2.2rem;
    font-weight: 900;
    color: var(--text-muted);
    opacity: 0.25;
}

.step-icon-box {
    font-size: 2.2rem;
    width: 54px;
    height: 54px;
    border-radius: var(--radius-md);
    background: var(--gradient-brand-subtle);
    display: grid;
    place-items: center;
    margin-bottom: 1.25rem;
    border: 1px solid var(--border-color);
}

.step-card h3 {
    font-size: 1.25rem;
    margin-bottom: 0.65rem;
}

.step-card p {
    font-size: 0.92rem;
    color: var(--text-secondary);
    line-height: 1.6;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
    gap: 1.75rem;
}

.category-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 2rem;
    transition: all var(--transition-normal);
}

.category-card:hover {
    border-color: var(--primary);
    transform: translateY(-5px);
    box-shadow: var(--shadow-md), 0 0 20px var(--primary-glow);
}

.cat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
}

.cat-icon {
    font-size: 2rem;
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md);
    background: var(--bg-surface-elevated);
    display: grid;
    place-items: center;
}

.cat-badge {
    font-size: 0.74rem;
    font-weight: 700;
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    padding: 3px 10px;
    border-radius: var(--radius-full);
    color: var(--text-muted);
}

.category-card h3 {
    font-size: 1.3rem;
    margin-bottom: 1rem;
}

.cat-skills-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.skill-pill {
    font-size: 0.82rem;
    font-weight: 500;
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    padding: 4px 11px;
    border-radius: var(--radius-full);
    color: var(--text-secondary);
    transition: all var(--transition-fast);
}

.skill-pill:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--bg-surface);
}

.cat-link {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--primary);
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    transition: all var(--transition-fast);
}

.cat-link:hover {
    gap: 0.7rem;
}

.swap-demo-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 3.5rem;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-md);
}

.swap-demo-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 400px;
    height: 300px;
    background: var(--gradient-brand-subtle);
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
}

@media (max-width: 768px) {
    .hero-title { font-size: 2.35rem; }
    .hero-stats-strip { flex-direction: column; gap: 1.25rem; padding: 1.5rem; }
    .stat-divider { display: none; }
    .hero-cta-group { flex-direction: column; width: 100%; }
    .swap-demo-card { padding: 1.75rem; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
