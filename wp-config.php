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
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',         'E7X1h02Y@x5gCL^{H-Xn~OGS&ULfqtTR<2ZSKZGAuYr,ug=6Nj^ZTNuYs6jM/oT+' );
define( 'SECURE_AUTH_KEY',  '/+n_*_t_uBDRMT)M3|xIvk3$8U.?#rSVK-#kV/I/`B+hq-g=8cR<Q)%lR+xQdE!V' );
define( 'LOGGED_IN_KEY',    ',u%gNKbdYz?C|{Z:g|y/n[cT+x^XtYI,}J/N/[IA*Gw8`QZY18oOc-%*-;GzX*V}' );
define( 'NONCE_KEY',        'lI|h!4k[=]WS!H*%JsG/Mq!y&W~)a1>&|6&-UQt{bP!IlA|qWVpDW)77)8lU!Jtf' );
define( 'AUTH_SALT',        'lNDh 4#%Y1-oFwl.e<@qLuNR+fmjc%s45;Z^I5e9}uX*l4Y%u)vc@(oScK@3i[ZJ' );
define( 'SECURE_AUTH_SALT', 'Ptz1@|={o15fQ8]-yMYG8x&[Z?L^I9u]Q3O&c<Qi}VJ$)1bOP|0!wGY%QS:L7UUT' );
define( 'LOGGED_IN_SALT',   'd|Sf<L!Rua7^UEF#}=j|i/n5]9zyLu5+fzuW%WJrA[wHSS@|!*nXJ(j!dv8:@]#F' );
define( 'NONCE_SALT',       'W9C@dA 2e+:m.&]U&)T5K9Z{&_]W5g$kJ!Y,^}f7L9#~pX_{sxIx]M[Z-D|0]AHu' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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

define( 'WP_HOME', 'http://localhost/sellersbay' );
define( 'WP_SITEURL', 'http://localhost/sellersbay' );


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';