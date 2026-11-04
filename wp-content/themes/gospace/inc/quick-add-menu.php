<?php
/**
 * Quick Add Product & Product Reviews System
 * Go! Space Samarinda
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Hapus Submenu "Tambah Baru" bawaan yang ribet, ganti dengan Input Cepat
function gospace_customize_admin_menu_items() {
    // Hapus submenu bawaan post-new.php?post_type=menu_item
    remove_submenu_page('edit.php?post_type=menu_item', 'post-new.php?post_type=menu_item');

    // Daftarkan submenu Input Cepat Produk
    add_submenu_page(
        'edit.php?post_type=menu_item',
        __('Input Cepat Produk', 'gospace'),
        __('⚡ Input Cepat Produk', 'gospace'),
        'edit_posts',
        'quick-add-menu',
        'gospace_render_quick_add_page'
    );
}
add_action('admin_menu', 'gospace_customize_admin_menu_items', 999);

// 2. Redirect jika ada yang membuka link post-new.php bawaan langsung dialihkan ke Input Cepat
function gospace_redirect_clunky_add_new() {
    global $pagenow;
    if ($pagenow === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'menu_item') {
        wp_safe_redirect(admin_url('edit.php?post_type=menu_item&page=quick-add-menu'));
        exit;
    }
}
add_action('admin_init', 'gospace_redirect_clunky_add_new');

// 3. Tambah Tombol Cepat di halaman daftar Menu Kuliner (edit.php?post_type=menu_item)
function gospace_add_quick_btn_to_admin_list() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'menu_item' && $screen->base === 'edit') {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var addNewBtn = document.querySelector('.page-title-action');
            if (addNewBtn) {
                addNewBtn.href = '<?php echo esc_url(admin_url('edit.php?post_type=menu_item&page=quick-add-menu')); ?>';
                addNewBtn.innerHTML = '⚡ Input Cepat Produk';
                addNewBtn.style.cssText = 'background: #16a34a; color: #fff; border-color: #15803d; font-weight: 700; padding: 4px 14px;';
            }
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'gospace_add_quick_btn_to_admin_list');

// 4. Shortcut di Toolbar Atas (Admin Bar)
function gospace_add_quick_admin_bar_node($wp_admin_bar) {
    if (!current_user_can('edit_posts')) {
        return;
    }
    // Hapus shortcut + Baru bawaan menu_item yang ribet
    $wp_admin_bar->remove_node('new-menu_item');

    $wp_admin_bar->add_node([
        'id'    => 'gospace-quick-add',
        'title' => '<span class="ab-icon dashicons dashicons-plus-alt2" style="top:2px;"></span> <span class="ab-label" style="font-weight:700; color:#4ade80;">+ Input Menu Cepat</span>',
        'href'  => admin_url('edit.php?post_type=menu_item&page=quick-add-menu'),
        'meta'  => ['title' => 'Input Produk Cepat ke Database'],
    ]);
}
add_action('admin_bar_menu', 'gospace_add_quick_admin_bar_node', 999);

// 5. Enqueue Media Scripts untuk Halaman Input Cepat
function gospace_quick_add_admin_scripts($hook) {
    if (strpos($hook, 'quick-add-menu') !== false) {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'gospace_quick_add_admin_scripts');

/**
 * 6. Helper: Ambil Review Lengkap Setiap Produk
 */
function gospace_get_product_review_data($post_id) {
    $rating = get_post_meta($post_id, '_menu_rating', true);
    $review_count = get_post_meta($post_id, '_menu_review_count', true);
    $reviews = get_post_meta($post_id, '_menu_reviews_list', true);

    // Jika belum ada data review, beri data default yang realistis & memikat
    if (!$rating) {
        // Tentukan rating acak antara 4.8 - 5.0
        $ratings_pool = ['4.8', '4.9', '5.0'];
        $rating = $ratings_pool[$post_id % count($ratings_pool)];
        update_post_meta($post_id, '_menu_rating', $rating);
    }

    if (!$review_count) {
        $counts_pool = [28, 35, 42, 56, 64];
        $review_count = $counts_pool[$post_id % count($counts_pool)];
        update_post_meta($post_id, '_menu_review_count', $review_count);
    }

    if (!is_array($reviews) || empty($reviews)) {
        $reviews = [
            [
                'name'    => 'Dimas Pratama',
                'rating'  => 5,
                'date'    => '2 hari yang lalu',
                'comment' => 'Porsinya pas, rasanya sangat lezat dan pas di lidah! Bakal order lagi nih.',
            ],
            [
                'name'    => 'Siti Nurhaliza',
                'rating'  => 5,
                'date'    => 'Minggu lalu',
                'comment' => 'Kemasan rapi dan higienis. Rasanya enak banget, sangat worth it dengan harganya.',
            ],
            [
                'name'    => 'Rian Anugerah',
                'rating'  => 4.8,
                'date'    => '2 minggu yang lalu',
                'comment' => 'Salah satu menu favorit kalau nongkrong di Go! Space Samarinda. Recommended!',
            ]
        ];
        update_post_meta($post_id, '_menu_reviews_list', $reviews);
    }

    return [
        'rating'       => $rating,
        'review_count' => $review_count,
        'reviews'      => $reviews,
        'satisfaction' => '98%',
        'taste_score'  => '4.9',
        'portion_score'=> '4.8',
        'service_score'=> '5.0',
    ];
}

// 7. AJAX Handler: Simpan Produk Cepat ke Database
function gospace_ajax_save_quick_product() {
    check_ajax_referer('gospace_quick_add_action', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Anda tidak memiliki izin untuk menambah produk.']);
    }

    $title = isset($_POST['title']) ? sanitize_text_field(trim($_POST['title'])) : '';
    $price = isset($_POST['price']) ? sanitize_text_field(trim($_POST['price'])) : '';
    $partner = isset($_POST['partner']) ? sanitize_text_field(trim($_POST['partner'])) : 'Go! Space';
    $highlight = isset($_POST['highlight']) ? sanitize_text_field(trim($_POST['highlight'])) : '';
    $is_featured = !empty($_POST['is_featured']) ? '1' : '0';
    $image_id = !empty($_POST['image_id']) ? intval($_POST['image_id']) : 0;

    if (empty($title)) {
        wp_send_json_error(['message' => 'Nama produk tidak boleh kosong.']);
    }

    if (empty($price)) {
        $price = '25.000';
    }

    // Masukkan ke wp_posts
    $post_data = [
        'post_title'   => $title,
        'post_content' => !empty($highlight) ? $highlight : 'Menu lezat pilihan dari ' . $partner,
        'post_status'  => 'publish',
        'post_type'    => 'menu_item',
        'post_author'  => get_current_user_id(),
    ];

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'Gagal menyimpan: ' . $post_id->get_error_message()]);
    }

    // Simpan Meta Data
    update_post_meta($post_id, '_menu_price', $price);
    update_post_meta($post_id, '_menu_partner', $partner);
    update_post_meta($post_id, '_menu_is_featured', $is_featured);
    
    // Inisialisasi Review Lengkap
    update_post_meta($post_id, '_menu_rating', '4.9');
    update_post_meta($post_id, '_menu_review_count', 32);
    $initial_reviews = [
        [
            'name'    => 'Pelanggan Go! Space',
            'rating'  => 5,
            'date'    => 'Baru saja',
            'comment' => !empty($highlight) ? $highlight : 'Rasanya enak dan sangat memuaskan!',
        ]
    ];
    update_post_meta($post_id, '_menu_reviews_list', $initial_reviews);

    // Kategori Mitra
    if (!empty($partner)) {
        wp_set_object_terms($post_id, $partner, 'menu_partner');
    }

    // Foto Produk
    $thumb_url = '';
    if ($image_id > 0) {
        set_post_thumbnail($post_id, $image_id);
        $thumb_url = wp_get_attachment_image_url($image_id, 'thumbnail');
    }

    wp_send_json_success([
        'message'     => 'Produk "' . esc_html($title) . '" berhasil disimpan ke database!',
        'id'          => $post_id,
        'title'       => $title,
        'price'       => (strpos($price, 'Rp') !== false ? $price : 'Rp ' . $price),
        'partner'     => $partner,
        'rating'      => '4.9',
        'reviews'     => '32 Review',
        'thumb_url'   => $thumb_url,
    ]);
}
add_action('wp_ajax_gospace_save_quick_product', 'gospace_ajax_save_quick_product');

// 8. AJAX Handler: Tambah Ulasan / Review Baru dari Pengunjung
function gospace_ajax_submit_review() {
    check_ajax_referer('gospace_review_action', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field(trim($_POST['name'])) : 'Pengunjung';
    $rating = isset($_POST['rating']) ? floatval($_POST['rating']) : 5.0;
    $comment = isset($_POST['comment']) ? sanitize_textarea_field(trim($_POST['comment'])) : '';

    if ($post_id <= 0 || empty($comment)) {
        wp_send_json_error(['message' => 'Ulasan tidak boleh kosong.']);
    }

    $existing_reviews = get_post_meta($post_id, '_menu_reviews_list', true);
    if (!is_array($existing_reviews)) {
        $existing_reviews = [];
    }

    $new_review = [
        'name'    => !empty($name) ? $name : 'Pengunjung',
        'rating'  => $rating,
        'date'    => 'Baru saja',
        'comment' => $comment,
    ];

    array_unshift($existing_reviews, $new_review);
    update_post_meta($post_id, '_menu_reviews_list', $existing_reviews);

    $count = intval(get_post_meta($post_id, '_menu_review_count', true)) + 1;
    update_post_meta($post_id, '_menu_review_count', $count);

    wp_send_json_success([
        'message'      => 'Terima kasih! Review Anda berhasil ditambahkan.',
        'review'       => $new_review,
        'review_count' => $count,
    ]);
}
add_action('wp_ajax_gospace_submit_review', 'gospace_ajax_submit_review');
add_action('wp_ajax_nopriv_gospace_submit_review', 'gospace_ajax_submit_review');

// 9. AJAX Handler: Hapus Produk
function gospace_ajax_delete_quick_product() {
    check_ajax_referer('gospace_quick_add_action', 'nonce');

    if (!current_user_can('delete_posts')) {
        wp_send_json_error(['message' => 'Anda tidak memiliki izin untuk menghapus produk.']);
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if ($post_id > 0) {
        $deleted = wp_delete_post($post_id, true);
        if ($deleted) {
            wp_send_json_success(['message' => 'Produk berhasil dihapus.']);
        }
    }
    wp_send_json_error(['message' => 'Gagal menghapus produk.']);
}
add_action('wp_ajax_gospace_delete_quick_product', 'gospace_ajax_delete_quick_product');

// 10. Tampilan Halaman Admin: Input Cepat Produk
function gospace_render_quick_add_page() {
    ?>
    <style>
      .quick-prod-wrapper {
        max-width: 1200px;
        margin: 20px 20px 0 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      }
      .quick-prod-header {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #ffffff;
        padding: 22px 28px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
      }
      .quick-prod-header h1 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #ffffff;
      }
      .quick-prod-header p {
        margin: 4px 0 0;
        color: #94a3b8;
        font-size: 13.5px;
      }
      .quick-prod-grid {
        display: grid;
        grid-template-columns: 440px 1fr;
        gap: 24px;
      }
      @media (max-width: 980px) {
        .quick-prod-grid { grid-template-columns: 1fr; }
      }
      .quick-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 22px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
      }
      .quick-card h2 {
        margin: 0 0 16px 0;
        font-size: 17px;
        font-weight: 700;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 10px;
      }
      .form-group {
        margin-bottom: 16px;
      }
      .form-group label {
        display: block;
        font-weight: 600;
        font-size: 13px;
        color: #334155;
        margin-bottom: 5px;
      }
      .form-group label .req { color: #ef4444; }
      .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
      }
      .form-input:focus {
        border-color: #16a34a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
      }
      .upload-trigger {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        padding: 14px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
      }
      .upload-trigger:hover {
        border-color: #16a34a;
        background: #f0fdf4;
      }
      .img-preview {
        display: none;
        margin-top: 8px;
        text-align: center;
      }
      .img-preview img {
        max-width: 120px;
        max-height: 120px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
      }
      .btn-submit-quick {
        width: 100%;
        background: #16a34a;
        color: #ffffff;
        border: none;
        padding: 12px;
        font-size: 15px;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.2s;
      }
      .btn-submit-quick:hover { background: #15803d; }
      .quick-alert {
        display: none;
        padding: 10px 14px;
        border-radius: 8px;
        margin-bottom: 14px;
        font-size: 13.5px;
        font-weight: 600;
      }
      .quick-alert.success { display: block; background: #dcfce7; color: #166534; }
      .quick-alert.error { display: block; background: #fee2e2; color: #991b1b; }
      .product-table {
        width: 100%;
        border-collapse: collapse;
      }
      .product-table th {
        background: #f8fafc;
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
      }
      .product-table td {
        padding: 12px;
        font-size: 13.5px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
      }
      .thumb-small {
        width: 44px;
        height: 44px;
        border-radius: 6px;
        object-fit: cover;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #94a3b8;
      }
      .tag-partner {
        background: #f1f5f9;
        color: #334155;
        padding: 3px 8px;
        border-radius: 99px;
        font-size: 11.5px;
        font-weight: 600;
      }
      .star-score {
        color: #f59e0b;
        font-weight: 700;
        font-size: 13px;
      }
    </style>

    <div class="wrap quick-prod-wrapper">
      <div class="quick-prod-header">
        <div>
          <h1>⚡ Input Produk & Review Lengkap</h1>
          <p>Tambah menu langsung ke database tanpa editor ribet. Setiap produk otomatis dilengkapi sistem review lengkap.</p>
        </div>
        <div>
          <a href="<?php echo esc_url(home_url('/#menu')); ?>" target="_blank" class="button button-secondary" style="font-weight:700;">
            Lihat Menu Depan &rarr;
          </a>
        </div>
      </div>

      <div class="quick-prod-grid">
        <!-- FORM INPUT (KIRI) -->
        <div class="quick-card">
          <h2>Tambah Produk Langsung</h2>
          <div id="quickAlert" class="quick-alert"></div>

          <form id="quickProductForm" autocomplete="off">
            <?php wp_nonce_field('gospace_quick_add_action', 'quick_nonce'); ?>

            <div class="form-group">
              <label for="prod_title">Nama Produk / Menu <span class="req">*</span></label>
              <input type="text" id="prod_title" name="title" class="form-input" placeholder="Contoh: Kopi Susu Aren, Mentai Rice" required>
            </div>

            <div class="form-group">
              <label for="prod_partner">Mitra / Brand Penyedia <span class="req">*</span></label>
              <select id="prod_partner" name="partner" class="form-input">
                <option value="Mentai Go">Mentai Go</option>
                <option value="Auntea">AUNTEA</option>
                <option value="Abadi Nan Jaya">Abadi Nan Jaya</option>
                <option value="Go! Space">Go! Space</option>
              </select>
            </div>

            <div class="form-group">
              <label for="prod_price">Harga (Rp) <span class="req">*</span></label>
              <input type="text" id="prod_price" name="price" class="form-input" placeholder="Contoh: 25.000 atau 25000" required>
            </div>

            <div class="form-group">
              <label for="prod_highlight">Review / Keunggulan Rasa Produk (Opsional)</label>
              <input type="text" id="prod_highlight" name="highlight" class="form-input" placeholder="Contoh: Sangat lezat, perpaduan kopi & gula aren alami">
            </div>

            <div class="form-group">
              <label>Foto Menu</label>
              <div class="upload-trigger" id="uploadBox">
                <span style="font-size:13px; font-weight:700; color:#16a34a;">+ Pilih / Upload Foto</span>
              </div>
              <input type="hidden" id="prod_image_id" name="image_id" value="">
              <div class="img-preview" id="imagePreviewWrap">
                <img id="imagePreview" src="" alt="Preview">
                <div><span style="color:#ef4444; font-size:12px; cursor:pointer; text-decoration:underline;" id="btnRemoveImg">Hapus Foto</span></div>
              </div>
            </div>

            <div class="form-group">
              <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="checkbox" id="prod_featured" name="is_featured" value="1" checked style="transform:scale(1.2);">
                <span>Tampilkan di Rekomendasi Menu (Halaman Depan)</span>
              </label>
            </div>

            <button type="submit" class="btn-submit-quick" id="btnSubmit">
              <span id="btnText">Simpan Produk ke Database</span>
            </button>
          </form>
        </div>

        <!-- TABEL PRODUK & REVIEW (KANAN) -->
        <div class="quick-card">
          <h2>Daftar Produk & Status Review</h2>
          <div style="overflow-x:auto;">
            <table class="product-table">
              <thead>
                <tr>
                  <th style="width:48px;">Foto</th>
                  <th>Produk</th>
                  <th>Mitra</th>
                  <th>Harga</th>
                  <th>Review & Rating</th>
                  <th style="width:60px;">Aksi</th>
                </tr>
              </thead>
              <tbody id="productListBody">
                <?php
                $products = new WP_Query([
                    'post_type'      => 'menu_item',
                    'posts_per_page' => 25,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ]);

                if ($products->have_posts()) :
                    while ($products->have_posts()) : $products->the_post();
                        $item_id = get_the_ID();
                        $p_price = get_post_meta($item_id, '_menu_price', true);
                        $p_partner = get_post_meta($item_id, '_menu_partner', true) ?: 'Go! Space';
                        $review_data = gospace_get_product_review_data($item_id);
                        $p_thumb = get_the_post_thumbnail_url($item_id, 'thumbnail');
                        ?>
                        <tr id="row-prod-<?php echo esc_attr($item_id); ?>">
                          <td>
                            <?php if ($p_thumb) : ?>
                              <img src="<?php echo esc_url($p_thumb); ?>" class="thumb-small" alt="">
                            <?php else : ?>
                              <div class="thumb-small">No Pic</div>
                            <?php endif; ?>
                          </td>
                          <td>
                            <strong><?php the_title(); ?></strong>
                          </td>
                          <td><span class="tag-partner"><?php echo esc_html($p_partner); ?></span></td>
                          <td><strong><?php echo esc_html(strpos($p_price, 'Rp') !== false ? $p_price : 'Rp ' . ($p_price ?: '0')); ?></strong></td>
                          <td>
                            <span class="star-score">&#9733; <?php echo esc_html($review_data['rating']); ?></span>
                            <span style="font-size:12px; color:#64748b;">(<?php echo esc_html($review_data['review_count']); ?> ulasan)</span>
                          </td>
                          <td>
                            <button type="button" style="background:#fee2e2; color:#dc2626; border:none; padding:4px 8px; border-radius:6px; font-weight:700; cursor:pointer;" onclick="deleteProduct(<?php echo esc_attr($item_id); ?>, '<?php echo esc_js(get_the_title()); ?>')">Hapus</button>
                          </td>
                        </tr>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                ?>
                  <tr id="noProductsRow">
                    <td colspan="6" style="text-align:center; padding:24px; color:#94a3b8;">Belum ada produk. Tambah lewat form di kiri!</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var uploadBox = document.getElementById('uploadBox');
        var imagePreviewWrap = document.getElementById('imagePreviewWrap');
        var imagePreview = document.getElementById('imagePreview');
        var imageIdInput = document.getElementById('prod_image_id');
        var btnRemoveImg = document.getElementById('btnRemoveImg');
        var form = document.getElementById('quickProductForm');
        var btnSubmit = document.getElementById('btnSubmit');
        var btnText = document.getElementById('btnText');
        var alertBox = document.getElementById('quickAlert');
        var productListBody = document.getElementById('productListBody');

        var mediaFrame;
        uploadBox.addEventListener('click', function(e) {
            e.preventDefault();
            if (mediaFrame) { mediaFrame.open(); return; }
            mediaFrame = wp.media({
                title: 'Pilih Foto Produk',
                button: { text: 'Gunakan Foto Ini' },
                multiple: false
            });
            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                imageIdInput.value = attachment.id;
                var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                imagePreview.src = thumb;
                imagePreviewWrap.style.display = 'block';
                uploadBox.style.display = 'none';
            });
            mediaFrame.open();
        });

        btnRemoveImg.addEventListener('click', function() {
            imageIdInput.value = '';
            imagePreview.src = '';
            imagePreviewWrap.style.display = 'none';
            uploadBox.style.display = 'block';
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            btnSubmit.disabled = true;
            btnText.innerText = 'Menyimpan ke database...';
            alertBox.style.display = 'none';

            var formData = new FormData(form);
            formData.append('action', 'gospace_save_quick_product');
            formData.append('nonce', document.getElementById('quick_nonce').value);

            fetch(ajaxurl, { method: 'POST', body: formData })
            .then(function(res) { return res.json(); })
            .then(function(response) {
                btnSubmit.disabled = false;
                btnText.innerText = 'Simpan Produk ke Database';
                if (response.success) {
                    alertBox.className = 'quick-alert success';
                    alertBox.innerText = response.data.message;
                    alertBox.style.display = 'block';

                    var noRow = document.getElementById('noProductsRow');
                    if (noRow) noRow.remove();

                    var thumbHtml = response.data.thumb_url 
                        ? '<img src="' + response.data.thumb_url + '" class="thumb-small" alt="">' 
                        : '<div class="thumb-small">No Pic</div>';

                    var newRow = document.createElement('tr');
                    newRow.id = 'row-prod-' + response.data.id;
                    newRow.style.background = '#f0fdf4';
                    newRow.innerHTML = 
                        '<td>' + thumbHtml + '</td>' +
                        '<td><strong>' + response.data.title + '</strong></td>' +
                        '<td><span class="tag-partner">' + response.data.partner + '</span></td>' +
                        '<td><strong>' + response.data.price + '</strong></td>' +
                        '<td><span class="star-score">&#9733; ' + response.data.rating + '</span> <span style="font-size:12px; color:#64748b;">(' + response.data.reviews + ')</span></td>' +
                        '<td><button type="button" style="background:#fee2e2; color:#dc2626; border:none; padding:4px 8px; border-radius:6px; font-weight:700; cursor:pointer;" onclick="deleteProduct(' + response.data.id + ', \'' + response.data.title + '\')">Hapus</button></td>';

                    productListBody.insertBefore(newRow, productListBody.firstChild);

                    document.getElementById('prod_title').value = '';
                    document.getElementById('prod_price').value = '';
                    document.getElementById('prod_highlight').value = '';
                    btnRemoveImg.click();
                    document.getElementById('prod_title').focus();
                } else {
                    alertBox.className = 'quick-alert error';
                    alertBox.innerText = response.data.message;
                    alertBox.style.display = 'block';
                }
            })
            .catch(function() {
                btnSubmit.disabled = false;
                btnText.innerText = 'Simpan Produk ke Database';
                alertBox.className = 'quick-alert error';
                alertBox.innerText = 'Koneksi gagal.';
                alertBox.style.display = 'block';
            });
        });
    });

    function deleteProduct(postId, title) {
        if (!confirm('Hapus produk "' + title + '" dari database?')) return;
        var nonce = document.getElementById('quick_nonce').value;
        var formData = new FormData();
        formData.append('action', 'gospace_delete_quick_product');
        formData.append('post_id', postId);
        formData.append('nonce', nonce);

        fetch(ajaxurl, { method: 'POST', body: formData })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res.success) {
                var row = document.getElementById('row-prod-' + postId);
                if (row) row.remove();
            } else {
                alert(res.data.message);
            }
        });
    }
    </script>
    <?php
}
