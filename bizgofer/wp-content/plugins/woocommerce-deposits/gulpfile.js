"use strict";

/**
 * Packages.
 */
const gulp  = require( 'gulp' );
const wpPot = require( 'gulp-wp-pot' );

/**
 * Localization.
 */
gulp.task( 'makePOT', () => {
	return gulp.src(
		'**/*.php'
	)
	.pipe( wpPot(
		{
			domain: 'woocommerce-deposits',
			package: 'WooCommerce Deposits',
		}
	) )
	.pipe( gulp.dest( 'locale/woocommerce-deposits.pot' ) );
} );

gulp.task( 'build', [ 'makePOT' ] );
gulp.task( 'default', [ 'build' ] );