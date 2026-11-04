<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'gospacesa_wp299' );

/** Database username */
define( 'DB_USER', 'gospacesa_wp299' );

/** Database password */
define( 'DB_PASSWORD', 'T40pE(-8SF' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

define('DISABLE_WP_CRON', true);

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '9ldkf8wjdzwr9icwmudshbidtpqkrlvpswzv81cpq1ejfyysves99mnvdvzu9k9a' );
define( 'SECURE_AUTH_KEY',  'jcc8is3hysoitjwrdfm9mjkhigmt9p6bahnit5rlutxhtc8pec3hc2exd7kfkzbj' );
define( 'LOGGED_IN_KEY',    'dol44ffadl4cb7unzup4ygvbzpfwpmdwjtitieyhs53op7gtuvmqpcsdldhq3719' );
define( 'NONCE_KEY',        'pxhirfhctptheblks1nbqd1trd1hjjpliyqjk8nqtp2nwetdtgnnkptsaqksmmgk' );
define( 'AUTH_SALT',        'bsnsnb1lfhcf1gp459kgvmhhkfycswadhblrwu64qhy39gfwpccvaijodfevjawz' );
define( 'SECURE_AUTH_SALT', 'ns0mg2r2qqjw8cvmkud5b7mok1bt44lkicttofhgqco4obefgbsj1n1l9wuyzvoa' );
define( 'LOGGED_IN_SALT',   'j6i6nadqmc2m7gpturhurtdtzqi4fsddiqckepftc56yhgmi30fjsx5g4xlcys3f' );
define( 'NONCE_SALT',       '2qjyelcvrwnsfdvgs4rri50eonkebumok9g6zc46eux6mdyr8xn8qtmymukez6fg' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wpks_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';