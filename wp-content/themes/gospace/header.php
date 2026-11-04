<?php
/**
 * Header template for Go! Space Samarinda
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="navbar" id="mainNavbar">
  <div class="nav-container">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="brand-logo">
      <div class="logo-badge">
        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.png" alt="Go! Space Logo" onerror="this.onerror=null; this.parentElement.innerText='GO!';">
      </div>
      <span class="brand-text">Go! Space Samarinda</span>
    </a>

    <ul class="nav-links" id="navLinks">
      <li class="active"><a href="#beranda">Beranda</a></li>
      <li><a href="#menu">Menu</a></li>
      <li><a href="#tentang-kami">Tentang Kami</a></li>
      <li><a href="#kontak">Kontak</a></li>
    </ul>

    <div class="nav-actions">
      <button type="button" class="btn-icon" id="searchBtn" title="Cari Menu" aria-label="Cari Menu">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>

      <button type="button" class="btn-qr" id="openQrModalBtn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="7" height="7"></rect>
          <rect x="14" y="3" width="7" height="7"></rect>
          <rect x="14" y="14" width="7" height="7"></rect>
          <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span>Scan QR</span>
      </button>

      <button type="button" class="mobile-menu-btn" id="mobileToggle" aria-label="Buka Menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
    </div>
  </div>
</header>
