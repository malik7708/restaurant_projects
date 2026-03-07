<?php
// Reusable hero include. Set these variables before including:
// $hero_title, $hero_subtitle, $hero_cta_text, $hero_cta_href, $hero_cta_secondary_text, $hero_cta_secondary_href, $hero_image

$hero_title = $hero_title ?? 'Welcome';
$hero_subtitle = $hero_subtitle ?? '';
$hero_image = $hero_image ?? '/restaurant_project/assets/images/hero-bg.jpg';
$primary_text = $hero_cta_text ?? '';
$primary_href = $hero_cta_href ?? '#';
$secondary_text = $hero_cta_secondary_text ?? '';
$secondary_href = $hero_cta_secondary_href ?? '#';
?>

<section class="hero" style="background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('<?= htmlspecialchars($hero_image) ?>');">
    <div class="container">
        <div class="hero-content">
            <h1><?= htmlspecialchars($hero_title) ?></h1>
            <?php if ($hero_subtitle): ?>
                <p><?= htmlspecialchars($hero_subtitle) ?></p>
            <?php endif; ?>

            <div style="margin-top: 1.25rem; display: inline-flex; gap: 0.75rem;">
                <?php if ($primary_text): ?>
                    <a href="<?= htmlspecialchars($primary_href) ?>" class="btn"><?= htmlspecialchars($primary_text) ?></a>
                <?php endif; ?>
                <?php if ($secondary_text): ?>
                    <a href="<?= htmlspecialchars($secondary_href) ?>" class="btn" style="background: transparent; border: 2px solid white;"><?= htmlspecialchars($secondary_text) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>