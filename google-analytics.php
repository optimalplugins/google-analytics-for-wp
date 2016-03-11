<?php
/*
	Plugin Name: Google Analytics for WP
	Plugin URI: http://www.OptimalPlugins.com/
    Description: Google Analytics plugin makes it easy to add and enable Google Analytics tracking code on your website.
	Version: 1.1.0
	Author: OptimalPlugins.com
	Author URI: http://www.OptimalPlugins.com/
	License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( 'class-google-analytics-admin-helper.php' );
require_once( 'class-google-analytics-admin.php' );

Google_Analytics_Admin::instance();

function optl_google_analytics_init() {
	$enable           = false;
	$user_ID          = get_current_user_id();
	$analytics_id     = get_option( 'optl_ga_google_analytics_id' );
	$exclude_users    = get_option( 'optl_ga_exclude_users' );
	$disable_tracking = get_option( 'optl_ga_disable_tracking' );

	if ( is_user_logged_in() ) {
		$user = new WP_User( $user_ID );
		if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
			foreach ( $user->roles as $role ) {
				$role;
			}
		}
	}

	if ( $analytics_id == '' ) {
		$enable = false;
	} else if ( $disable_tracking == 'on' ) {
		$enable = false;
	}

	if ( ! empty( $exclude_users ) && is_user_logged_in() ) {
		if ( in_array( 'administrator', $exclude_users ) && $role == 'administrator' ) {
			$enable = false;
		} else if ( in_array( 'author', $exclude_users ) && $role == 'author' ) {
			$enable = false;
		} else if ( in_array( 'contributor', $exclude_users ) && $role == 'contributor' ) {
			$enable = false;
		} else if ( in_array( 'editor', $exclude_users ) && $role == 'editor' ) {
			$enable = false;
		} else if ( in_array( 'subscriber', $exclude_users ) && $role == 'subscriber' ) {
			$enable = false;
		}
	}

	if ( $enable ) {
		?>
		<script>
			(function (i, s, o, g, r, a, m) {
				i['GoogleAnalyticsObject'] = r;
				i[r] = i[r] || function () {
						(i[r].q = i[r].q || []).push(arguments)
					}, i[r].l = 1 * new Date();
				a = s.createElement(o),
					m = s.getElementsByTagName(o)[0];
				a.async = 1;
				a.src = g;
				m.parentNode.insertBefore(a, m)
			})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

			ga('create', '<?php echo esc_attr( $analytics_id ); ?>', 'auto');
			ga('send', 'pageview');

		</script>
		<?php
	}
}

add_action( 'wp_head', 'optl_google_analytics_init', 10 );
?>