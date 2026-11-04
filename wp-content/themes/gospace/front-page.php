<?php
/**
 * The front-page template for Go! Space Samarinda
 */

get_header();
?>

<main id="primary" class="site-main">

  <!-- HERO SECTION -->
  <section class="hero-section" id="beranda">
    <div class="container hero-container">
      <div class="hero-text-col">
        <div class="hero-badge-tag">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:2px;">
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
          </svg>
          <span>Good Food, Good Mood</span>
        </div>

        <h1 class="hero-title">
          Temukan Menu<br>Favoritmu di<br>Go! Space Samarinda
        </h1>

        <p class="hero-desc">
          Satu tempat untuk menjelajahi berbagai pilihan menu makanan dan minuman dari Go! Space dan mitra kami.
        </p>

        <a href="#menu" class="btn-hero-cta">
          <span>Jelajahi Menu</span>
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <line x1="5" y1="12" x2="19" y2="12"></line>
            <polyline points="12 5 19 12 12 19"></polyline>
          </svg>
        </a>
      </div>

      <div class="hero-media-wrapper">
        <div class="hero-note-doodle">
          <span class="doodle-text">Pilihan Menu Lengkap dari 4 Mitra Kami</span>
          <svg class="doodle-arrow" width="46" height="46" viewBox="0 0 50 50" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 10 Q 30 15, 32 35" />
            <path d="M24 30 L 32 38 L 40 30" />
          </svg>
        </div>
        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/hero-dish.png" alt="Pilihan Menu Go! Space Samarinda" class="hero-composite-img">
      </div>
    </div>
  </section>

  <!-- PARTNERS SECTION -->
  <section class="partners-section" id="partner-kami">
    <div class="container">
      <div class="section-header-row">
        <div>
          <span class="section-tag">PARTNER KAMI</span>
          <h2 class="section-title">Pilihan Rasa dari Mitra Terbaik</h2>
        </div>
        <p class="desc-text">
          Kami bekerja sama dengan 4 mitra pilihan untuk memberikan pengalaman kuliner terbaik bagi kamu.
        </p>
      </div>

      <div class="partners-grid">
        <!-- Partner 1: Go! Space -->
        <div class="partner-card">
          <div class="brand-circle">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/partner-gospace.png" alt="Go! Space Logo" onerror="this.src='<?php echo get_template_directory_uri(); ?>/assets/images/logo.png'">
          </div>
          <h3 class="partner-name">Go! Space</h3>
          <p class="partner-desc">Cafe & Hangout Space</p>
          <a href="#menu" class="btn-circle-action" title="Lihat Menu Go! Space">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </a>
        </div>

        <!-- Partner 2: Mentai Go -->
        <div class="partner-card">
          <div class="brand-circle">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/partner-mentaigo.png" alt="Mentai Go Logo">
          </div>
          <h3 class="partner-name">Mentai Go</h3>
          <p class="partner-desc">Mentai & Rice Bowl</p>
          <a href="#menu" class="btn-circle-action" title="Lihat Menu Mentai Go">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </a>
        </div>

        <!-- Partner 3: AUNTEA -->
        <div class="partner-card">
          <div class="brand-circle">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/partner-auntea.png" alt="AUNTEA Logo">
          </div>
          <h3 class="partner-name">AUNTEA</h3>
          <p class="partner-desc">Milk Tea & Beverages</p>
          <a href="#menu" class="btn-circle-action" title="Lihat Menu AUNTEA">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </a>
        </div>

        <!-- Partner 4: Abadi Nan Jaya -->
        <div class="partner-card">
          <div class="brand-circle">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/partner-abadinanjaya.png" alt="Abadi Nan Jaya Logo">
          </div>
          <h3 class="partner-name">Abadi Nan Jaya</h3>
          <p class="partner-desc">Coffee & Beverages</p>
          <a href="#menu" class="btn-circle-action" title="Lihat Menu Abadi Nan Jaya">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- MENU SECTION -->
  <section class="menu-section" id="menu">
    <div class="container">
      <div class="section-header-row">
        <div>
          <span class="section-tag">MENU PILIHAN</span>
          <h2 class="section-title">Rekomendasi Menu</h2>
        </div>
        <a href="#primary" class="menu-header-link" id="viewAllMenuBtn">
          <span>Lihat Semua Menu</span>
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <line x1="5" y1="12" x2="19" y2="12"></line>
            <polyline points="12 5 19 12 12 19"></polyline>
          </svg>
        </a>
      </div>

      <div class="menu-grid">
        <?php
        $menu_query = new WP_Query([
            'post_type'      => 'menu_item',
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        if ($menu_query->have_posts()) :
            while ($menu_query->have_posts()) : $menu_query->the_post();
                $item_id = get_the_ID();
                $item_price = get_post_meta($item_id, '_menu_price', true);
                if (!$item_price) {
                    $item_price = '25.000';
                }
                $price_display = (strpos($item_price, 'Rp') !== false) ? $item_price : 'Rp ' . $item_price;
                
                $item_partner = get_post_meta($item_id, '_menu_partner', true);
                $terms = get_the_terms($item_id, 'menu_partner');
                if ($terms && !is_wp_error($terms)) {
                    $item_partner = $terms[0]->name;
                }
                if (!$item_partner) {
                    $item_partner = 'Go! Space';
                }

                $thumb_url = get_the_post_thumbnail_url($item_id, 'medium');
                if (!$thumb_url) {
                    $thumb_url = get_template_directory_uri() . '/assets/images/menu-mentai.png';
                }

                $review_info = function_exists('gospace_get_product_review_data') ? gospace_get_product_review_data($item_id) : [
                    'rating' => '4.9', 'review_count' => 38, 'satisfaction' => '98%', 'reviews' => []
                ];
                $reviews_json = wp_json_encode($review_info['reviews']);
                ?>
                <div class="menu-card open-review-modal" data-id="<?php echo esc_attr($item_id); ?>" data-title="<?php the_title_attribute(); ?>" data-partner="<?php echo esc_attr($item_partner); ?>" data-price="<?php echo esc_attr($price_display); ?>" data-thumb="<?php echo esc_url($thumb_url); ?>" data-rating="<?php echo esc_attr($review_info['rating']); ?>" data-count="<?php echo esc_attr($review_info['review_count']); ?>" data-reviews="<?php echo esc_attr($reviews_json); ?>" style="cursor:pointer;" title="Klik untuk lihat detail & ulasan">
                  <div class="menu-thumb-wrap">
                    <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php the_title_attribute(); ?>">
                  </div>
                  <div class="menu-content">
                    <span class="partner-tag-pill"><?php echo esc_html($item_partner); ?></span>
                    <h3 class="menu-item-title"><?php the_title(); ?></h3>
                    <div class="menu-card-bottom">
                      <span class="menu-price"><?php echo esc_html($price_display); ?></span>
                      <button type="button" class="btn-add-item add-to-order" data-name="<?php the_title_attribute(); ?>" title="Tambah ke pesanan" onclick="event.stopPropagation();">+</button>
                    </div>
                  </div>
                </div>
                <?php
            endwhile;
            wp_reset_postdata();
        else :
        ?>
        <!-- Menu Item 1 -->
        <div class="menu-card open-review-modal" data-id="101" data-title="Original Mentai Rice" data-partner="Mentai Go" data-price="Rp 28.000" data-thumb="<?php echo get_template_directory_uri(); ?>/assets/images/menu-mentai.png" data-rating="4.9" data-count="48" data-reviews='[{"name":"Budi Santoso","rating":5,"date":"3 hari yang lalu","comment":"Mentainya creamy banget dan ikannya fresh!"}]' style="cursor:pointer;">
          <div class="menu-thumb-wrap">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/menu-mentai.png" alt="Original Mentai Rice">
          </div>
          <div class="menu-content">
            <span class="partner-tag-pill">Mentai Go</span>
            <h3 class="menu-item-title">Original Mentai Rice</h3>
            <div class="menu-card-bottom">
              <span class="menu-price">Rp 28.000</span>
              <button type="button" class="btn-add-item add-to-order" data-name="Original Mentai Rice" title="Tambah ke pesanan" onclick="event.stopPropagation();">+</button>
            </div>
          </div>
        </div>

        <!-- Menu Item 2 -->
        <div class="menu-card open-review-modal" data-id="102" data-title="Brown Sugar Milk Tea" data-partner="Auntea" data-price="Rp 22.000" data-thumb="<?php echo get_template_directory_uri(); ?>/assets/images/menu-boba.png" data-rating="4.9" data-count="36" data-reviews='[{"name":"Nadia Putri","rating":5,"date":"Kemarin","comment":"Bobanya kenyal manisnya pas tidak bikin enek!"}]' style="cursor:pointer;">
          <div class="menu-thumb-wrap">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/menu-boba.png" alt="Brown Sugar Milk Tea">
          </div>
          <div class="menu-content">
            <span class="partner-tag-pill">Auntea</span>
            <h3 class="menu-item-title">Brown Sugar Milk Tea</h3>
            <div class="menu-card-bottom">
              <span class="menu-price">Rp 22.000</span>
              <button type="button" class="btn-add-item add-to-order" data-name="Brown Sugar Milk Tea" title="Tambah ke pesanan" onclick="event.stopPropagation();">+</button>
            </div>
          </div>
        </div>

        <!-- Menu Item 3 -->
        <div class="menu-card open-review-modal" data-id="103" data-title="Americano" data-partner="Abadi Nan Jaya" data-price="Rp 18.000" data-thumb="<?php echo get_template_directory_uri(); ?>/assets/images/menu-americano.png" data-rating="4.8" data-count="29" data-reviews='[{"name":"Fajar Siddiq","rating":5,"date":"1 minggu lalu","comment":"Kopi hitam aromatik, acidity-nya seimbang."}]' style="cursor:pointer;">
          <div class="menu-thumb-wrap">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/menu-americano.png" alt="Americano">
          </div>
          <div class="menu-content">
            <span class="partner-tag-pill">Abadi Nan Jaya</span>
            <h3 class="menu-item-title">Americano</h3>
            <div class="menu-card-bottom">
              <span class="menu-price">Rp 18.000</span>
              <button type="button" class="btn-add-item add-to-order" data-name="Americano" title="Tambah ke pesanan" onclick="event.stopPropagation();">+</button>
            </div>
          </div>
        </div>

        <!-- Menu Item 4 -->
        <div class="menu-card open-review-modal" data-id="104" data-title="Strawberry Yakult" data-partner="Auntea" data-price="Rp 24.000" data-thumb="<?php echo get_template_directory_uri(); ?>/assets/images/menu-strawberry.png" data-rating="4.9" data-count="41" data-reviews='[{"name":"Vina Anggraini","rating":5,"date":"5 hari lalu","comment":"Segar banget asam manis dingin pas di siang terik!"}]' style="cursor:pointer;">
          <div class="menu-thumb-wrap">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/menu-strawberry.png" alt="Strawberry Yakult">
          </div>
          <div class="menu-content">
            <span class="partner-tag-pill">Auntea</span>
            <h3 class="menu-item-title">Strawberry Yakult</h3>
            <div class="menu-card-bottom">
              <span class="menu-price">Rp 24.000</span>
              <button type="button" class="btn-add-item add-to-order" data-name="Strawberry Yakult" title="Tambah ke pesanan" onclick="event.stopPropagation();">+</button>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ABOUT US SECTION -->
  <section class="about-section" id="tentang-kami">
    <div class="container">
      <div class="about-grid">
        <div class="about-image-card">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/about-cafe.png" alt="Suasana Cafe Go! Space Samarinda">
        </div>

        <div class="about-content">
          <span class="section-tag">TENTANG KAMI</span>
          <h2 class="section-title">Go! Space Samarinda</h2>
          <p class="about-desc">
            Go! Space Samarinda adalah tempat nongkrong dan menikmati berbagai pilihan menu lezat dari mitra terbaik kami. Kami hadir untuk memberikan pengalaman kuliner yang seru, nyaman, dan tentunya penuh rasa. Dengan konsep café yang modern dan suasana yang nyaman, Go! Space menjadi tempat yang tepat untuk bersantai, berkumpul, maupun bekerja.
          </p>

          <div class="features-list">
            <!-- Feature 1: Fork & Spoon -->
            <div class="feature-box">
              <div class="feature-icon-wrap">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M18 2v6a3 3 0 0 1-3 3 3 3 0 0 1-3-3V2"></path>
                  <line x1="15" y1="2" x2="15" y2="22"></line>
                  <line x1="12" y1="2" x2="12" y2="7"></line>
                  <line x1="18" y1="2" x2="18" y2="7"></line>
                  <path d="M6 2v20"></path>
                  <path d="M6 2a3 3 0 0 1 3 3v2a3 3 0 0 1-3 3"></path>
                </svg>
              </div>
              <h4 class="feature-title">Menu Beragam</h4>
              <p class="feature-sub">Makanan & minuman dari 4 mitra pilihan</p>
            </div>

            <!-- Feature 2: Quality Shield -->
            <div class="feature-box">
              <div class="feature-icon-wrap">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                  <polyline points="9 12 11 14 15 10"></polyline>
                </svg>
              </div>
              <h4 class="feature-title">Kualitas Terbaik</h4>
              <p class="feature-sub">Bahan berkualitas dan higienis</p>
            </div>

            <!-- Feature 3: Location Pin -->
            <div class="feature-box">
              <div class="feature-icon-wrap">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                  <circle cx="12" cy="10" r="3"></circle>
                </svg>
              </div>
              <h4 class="feature-title">Lokasi Strategis</h4>
              <p class="feature-sub">Mudah diakses di Samarinda</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SCAN QR BANNER SECTION -->
  <section class="qr-banner-section">
    <div class="container">
      <div class="qr-banner-card">
        <div>
          <span class="qr-badge-sub">Akses Menu Lebih Mudah!</span>
          <h2 class="qr-title">Scan QR Code</h2>
          <p class="qr-desc">
            untuk melihat seluruh menu, harga, dan informasi terbaru Go! Space Samarinda.
          </p>
          <button type="button" class="btn-qr-scan" id="bannerQrBtn">
            <span>Scan Sekarang</span>
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>
        </div>

        <div class="qr-right-composite">
          <div class="qr-card-white">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/qr-code.png" alt="Scan QR Code Menu Go! Space">
          </div>
          <div class="qr-doodle-hint">
            <div class="qr-phone-icon">
              <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24">
                <rect x="5" y="2" width="14" height="20" rx="3" ry="3"></rect>
                <line x1="12" y1="18" x2="12.01" y2="18"></line>
              </svg>
            </div>
            <span># Pindai & Jelajahi<br>Menu Kami!</span>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php
get_footer();
