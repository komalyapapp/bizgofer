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
define('DB_NAME', 'bizgofer');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '1234');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');


define('FS_METHOD', 'direct');
/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '3bemx<Tv6!A9cE6Riavb~<ZsPP$+LT8H+S>LmM*ybYG>OtU#)pt.!Ok`th5}I6MS');
define('SECURE_AUTH_KEY',  '^9b9-E,`pj+*4M$Y,aQ%UGS&P<fFFjIQon}H!vcm$X(`HH4U}9YV23<vH@C;^F~:');
define('LOGGED_IN_KEY',    'U<+St!6~7kA~p5nM^t{Hh1Ia#T)$z t)H-Ahh66d6 o[DLKhy*2Un~[[Y,|3ML$Y');
define('NONCE_KEY',        ':K] Wes6=Z=T!O{*(UyG0z9T[|M>f}@:Puni;;?RS486j:k<n9I~%d>5OZ7_)3>o');
define('AUTH_SALT',        'ya8H{c2]*i:j ]S?%q}H6B YmwizF/_V*v1xu[S837rd<ijq<.8swzmM4Kg?0&]j');
define('SECURE_AUTH_SALT', 'eFTIi|$iznFYcD +stc=d2F4A`ONk9 u7,:{ns.DE_/I242Xt@0i8SS3NeOMiZ<<');
define('LOGGED_IN_SALT',   'b}ULtSv~^tIc/#ELY2F9{:Ih{+acJ(=#V:u MoZ<N.yB<N;}.lY#Ws]oc4_@0W*%');
define('NONCE_SALT',       'q<wfE^>64S*B%$!TUs!@4h~(<F+&rDw2aPq7pI{oY-n/O$y.2Jn@_EOFe]{[EtaC');

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
define('JWT_KEY',"5f6aa01da5ddb387462c7eaf61bb78ad740f4707bebcf74f9b7c25d48e335894");
/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
