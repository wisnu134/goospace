<?php
/**
 * The base configuration for WordPress
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings ** //
define( 'DB_NAME', 'gospacesa_gospacesa' );
define( 'DB_USER', 'gospacesa_gospacesa' );
define( 'DB_PASSWORD', 'gospacesa' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 * Generate yang baru di: https://api.wordpress.org/secret-key/1.1/salt/
 */
define( 'AUTH_KEY',         'TEMPEL_SALT_BARU_DI_SINI' );
define( 'SECURE_AUTH_KEY',  'TEMPEL_SALT_BARU_DI_SINI' );
define( 'LOGGED_IN_KEY',    'TEMPEL_SALT_BARU_DI_SINI' );
define( 'NONCE_KEY',        'TEMPEL_SALT_BARU_DI_SINI' );
define( 'AUTH_SALT',        'TEMPEL_SALT_BARU_DI_SINI' );
define( 'SECURE_AUTH_SALT', 'TEMPEL_SALT_BARU_DI_SINI' );
define( 'LOGGED_IN_SALT',   'TEMPEL_SALT_BARU_DI_SINI' );
define( 'NONCE_SALT',       'TEMPEL_SALT_BARU_DI_SINI' );
/**#@-*/

$table_prefix = 'wp_';

define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

define( 'WP_HOME', 'https://gospacesamarinda.my.id' );
define( 'WP_SITEURL', 'https://gospacesamarinda.my.id' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';