<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'eoi_wp5');

/** MySQL database username */
define('DB_USER', 'eoi_wp5');

/** MySQL database password */
define('DB_PASSWORD', 'E@d1e)y[ZfftasPyVx(26^[9');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'XFv2Uwp9Bej8kSYS7nZp9dQqlKzR4EWDFTqpt7yfsrhogx3z48xtqaaZK4yrUc8O');
define('SECURE_AUTH_KEY',  'NVuyFQcFQJIizUGF6RjOVMiP6QkokEtt5tOxbGlCjJkWduXJYC8dQG8M8P9mRwl1');
define('LOGGED_IN_KEY',    'SYumJdj0y0UTXnu4XyDigR4sHqh5UjPb2GmIAY1d8ZUsRSryX9vBkNCgQCDi0Vf7');
define('NONCE_KEY',        'ifbsQ7chBxyWtAd6HZOQMimPcIIsN8z11k4VNcZDd6H8NwSfnrlPmwd9kTPIYcAy');
define('AUTH_SALT',        'KUX7xqjX8CyOLtLTgB3U9JajEI1EtamObU00MVtBJODLCbz9i9kQh9moHJ12c6xA');
define('SECURE_AUTH_SALT', 'fGLINTQHm3HaLeHehSG1za0LpQFv0nH7k2ox4qUqIw0Dg6zaok13v42PWg7etLma');
define('LOGGED_IN_SALT',   'p0tyQLFJPt6ZslPIPqxrcSw8LasJPE6jJfvc6jE6ZOLaavwuQRs9wKhxqc42kq2g');
define('NONCE_SALT',       'LuY2rW5XaFAE3JwPWXBSEOdLDu9YkLUopPlhqgyEm7STj2lKtFivDonKQGt4TEqt');

/**
 * Other customizations.
 */
define('FS_METHOD','direct');define('FS_CHMOD_DIR',0755);define('FS_CHMOD_FILE',0644);
define('WP_TEMP_DIR',dirname(__FILE__).'/wp-content/uploads');

/**
 * Turn off automatic updates since these are managed upstream.
 */
define('AUTOMATIC_UPDATER_DISABLED', true);


/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
