<?php
/**
 * Go! Space Samarinda Theme Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

function gospace_setup() {
    // Add default posts and comments RSS feed links to head.
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title.
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages.
    add_theme_support('post-thumbnails');

    // Register navigation menus.
    register_nav_menus([
        'primary' => __('Menu Utama', 'gospace'),
        'footer'  => __('Menu Footer', 'gospace'),
    ]);

    // Switch default core markup to output valid HTML5.
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
}
add_action('after_setup_theme', 'gospace_setup');

function gospace_scripts() {
    // Google Fonts: Plus Jakarta Sans & Caveat for doodles
    wp_enqueue_style('gospace-fonts', 'https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap', [], null);

    // Main stylesheet
    wp_enqueue_style('gospace-style', get_stylesheet_uri(), ['gospace-fonts'], '1.1.0');

    // Main JavaScript
    wp_enqueue_script('gospace-script', get_template_directory_uri() . '/assets/js/main.js', [], '1.1.0', true);
}
add_action('wp_enqueue_scripts', 'gospace_scripts');

/**
 * 1. Register Custom Post Type: Menu Kuliner
 */
function gospace_register_menu_cpt() {
    $labels = [
        'name'               => __('Menu Kuliner', 'gospace'),
        'singular_name'      => __('Menu', 'gospace'),
        'menu_name'          => __('Menu Kuliner', 'gospace'),
        'name_admin_bar'     => __('Menu Kuliner', 'gospace'),
        'add_new'            => __('⚡ Input Cepat Produk', 'gospace'),
        'add_new_item'       => __('Input Cepat Produk', 'gospace'),
        'new_item'           => __('Produk Baru', 'gospace'),
        'edit_item'          => __('Edit Produk', 'gospace'),
        'view_item'          => __('Lihat Produk', 'gospace'),
        'all_items'          => __('Semua Produk Kuliner', 'gospace'),
        'search_items'       => __('Cari Produk', 'gospace'),
        'not_found'          => __('Belum ada produk.', 'gospace'),
        'not_found_in_trash' => __('Tidak ada produk di tong sampah.', 'gospace'),
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => ['slug' => 'menu'],
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-food',
        'supports'           => ['title', 'thumbnail', 'editor', 'excerpt'],
        'show_in_rest'       => false,
    ];

    register_post_type('menu_item', $args);

    // Register Taxonomy: Mitra / Kategori Brand
    $tax_labels = [
        'name'              => __('Mitra Kuliner', 'gospace'),
        'singular_name'     => __('Mitra', 'gospace'),
        'search_items'      => __('Cari Mitra', 'gospace'),
        'all_items'         => __('Semua Mitra', 'gospace'),
        'edit_item'         => __('Edit Mitra', 'gospace'),
        'update_item'       => __('Update Mitra', 'gospace'),
        'add_new_item'      => __('Tambah Mitra Baru', 'gospace'),
        'new_item_name'     => __('Nama Mitra Baru', 'gospace'),
        'menu_name'         => __('Mitra Brand', 'gospace'),
    ];

    register_taxonomy('menu_partner', ['menu_item'], [
        'hierarchical'      => true,
        'labels'            => $tax_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => ['slug' => 'mitra'],
        'show_in_rest'      => true,
    ]);
}
add_action('init', 'gospace_register_menu_cpt');

/**
 * 2. Add Meta Boxes for Menu Details (Price, Partner, Featured)
 */
function gospace_add_menu_meta_box() {
    add_meta_box(
        'gospace_menu_details_box',
        __('Informasi Harga & Mitra Menu', 'gospace'),
        'gospace_render_menu_meta_box',
        'menu_item',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'gospace_add_menu_meta_box');

function gospace_render_menu_meta_box($post) {
    wp_nonce_field('gospace_save_menu_meta', 'gospace_menu_meta_nonce');

    $price = get_post_meta($post->ID, '_menu_price', true);
    $partner_custom = get_post_meta($post->ID, '_menu_partner', true);
    $is_featured = get_post_meta($post->ID, '_menu_is_featured', true);
    ?>
    <div style="padding: 12px 0;">
        <table class="form-table">
            <tr>
                <th style="width:200px;"><label for="menu_price"><strong>Harga Menu (Rp)</strong></label></th>
                <td>
                    <input type="text" id="menu_price" name="menu_price" value="<?php echo esc_attr($price ? $price : '28.000'); ?>" class="regular-text" placeholder="Contoh: 28.000 atau Rp 28.000" style="padding:8px 12px; font-size:14px; border-radius:6px;">
                    <p class="description">Tuliskan harga menu, misalnya: <code>28.000</code> atau <code>Rp 28.000</code></p>
                </td>
            </tr>
            <tr>
                <th><label for="menu_partner"><strong>Nama Mitra / Brand</strong></label></th>
                <td>
                    <select id="menu_partner" name="menu_partner" style="padding:8px 12px; font-size:14px; border-radius:6px; min-width: 260px;">
                        <option value="Mentai Go" <?php selected($partner_custom, 'Mentai Go'); ?>>Mentai Go</option>
                        <option value="Auntea" <?php selected($partner_custom, 'Auntea'); ?>>AUNTEA</option>
                        <option value="Abadi Nan Jaya" <?php selected($partner_custom, 'Abadi Nan Jaya'); ?>>Abadi Nan Jaya</option>
                        <option value="Go! Space" <?php selected($partner_custom, 'Go! Space'); ?>>Go! Space</option>
                        <option value="Lainnya" <?php selected($partner_custom, 'Lainnya'); ?>>Lainnya / Brand Baru</option>
                    </select>
                    <p class="description">Pilih mitra penyedia menu ini (atau bisa juga gunakan kategori Mitra di panel sebelah kanan).</p>
                </td>
            </tr>
            <tr>
                <th><label for="menu_is_featured"><strong>Rekomendasi Menu</strong></label></th>
                <td>
                    <label style="display:flex; align-items:center; gap:8px; font-weight:600;">
                        <input type="checkbox" id="menu_is_featured" name="menu_is_featured" value="1" <?php checked($is_featured, '1', true); ?> style="transform:scale(1.2);">
                        Tampilkan di bagian "Rekomendasi Menu" pada Halaman Depan
                    </label>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

function gospace_save_menu_meta($post_id) {
    if (!isset($_POST['gospace_menu_meta_nonce']) || !wp_verify_nonce($_POST['gospace_menu_meta_nonce'], 'gospace_save_menu_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['menu_price'])) {
        update_post_meta($post_id, '_menu_price', sanitize_text_field($_POST['menu_price']));
    }

    if (isset($_POST['menu_partner'])) {
        update_post_meta($post_id, '_menu_partner', sanitize_text_field($_POST['menu_partner']));
    }

    $is_featured = isset($_POST['menu_is_featured']) ? '1' : '0';
    update_post_meta($post_id, '_menu_is_featured', $is_featured);
}
add_action('save_post_menu_item', 'gospace_save_menu_meta');

/**
 * 3. Custom Admin Columns in WordPress Dashboard
 */
function gospace_menu_custom_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['thumb'] = __('Foto', 'gospace');
    $new_columns['title'] = __('Nama Menu', 'gospace');
    $new_columns['menu_partner_col'] = __('Mitra', 'gospace');
    $new_columns['menu_price_col'] = __('Harga', 'gospace');
    $new_columns['menu_featured_col'] = __('Rekomendasi Depan', 'gospace');
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter('manage_menu_item_posts_columns', 'gospace_menu_custom_columns');

function gospace_menu_custom_columns_data($column, $post_id) {
    switch ($column) {
        case 'thumb':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, [50, 50], ['style' => 'border-radius:6px; object-fit:cover;']);
            } else {
                echo '<span style="color:#aaa;">Tanpa Foto</span>';
            }
            break;
        case 'menu_partner_col':
            $partner = get_post_meta($post_id, '_menu_partner', true);
            $terms = get_the_terms($post_id, 'menu_partner');
            if ($terms && !is_wp_error($terms)) {
                $term_names = wp_list_pluck($terms, 'name');
                echo esc_html(implode(', ', $term_names));
            } elseif ($partner) {
                echo esc_html($partner);
            } else {
                echo '-';
            }
            break;
        case 'menu_price_col':
            $price = get_post_meta($post_id, '_menu_price', true);
            echo esc_html($price ? (strpos($price, 'Rp') !== false ? $price : 'Rp ' . $price) : '-');
            break;
        case 'menu_featured_col':
            $featured = get_post_meta($post_id, '_menu_is_featured', true);
            if ($featured === '1') {
                echo '<span style="color:#16a34a; font-weight:700;">&#10004; Tampil</span>';
            } else {
                echo '<span style="color:#9ca3af;">Tidak</span>';
            }
            break;
    }
}
add_action('manage_menu_item_posts_custom_column', 'gospace_menu_custom_columns_data', 10, 2);

/**
 * 4. Load Quick Add Product System
 */
require_once get_template_directory() . '/inc/quick-add-menu.php';

