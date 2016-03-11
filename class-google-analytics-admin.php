<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Google_Analytics_Admin {

	private static $_instance = null;
	public $base = '';
	public $settings = array();

	public function __construct() {
		$this->base         = 'optl_ga_';
		$this->admin_helper = new Google_Analytics_Admin_Helper();
		add_action( 'init', array( $this, 'init_settings' ), 10 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	private function settings_fields() {

		$settings['standard'] = array(
			'title'       => __( '', 'google-analytics-for-wp' ),
			'description' => __( '', 'google-analytics-for-wp' ),
			'fields'      => array(
				array(
					'id'          => 'google_analytics_id',
					'label'       => __( 'Google Analytics Property ID', 'google-analytics-for-wp' ),
					'description' => __( '', 'google-analytics-for-wp' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'UA-########-#', 'google-analytics-for-wp' )
				),

				array(
					'id'          => 'exclude_users',
					'label'       => __( 'Exclude Roles From Tracking', 'google-analytics-for-wp' ),
					'description' => __( '', 'google-analytics-for-wp' ),
					'type'        => 'checkbox_multi',
					'options'     => array(
						'administrator' => 'Administrator',
						'author'        => 'Author',
						'contributor'   => 'Contributor',
						'editor'        => 'Editor',
						'subscriber'    => 'Subscriber'
					),
					'default'     => array( 'administrator' )
				),

				array(
					'id'          => 'disable_tracking',
					'label'       => __( 'Disable Tracking Code', 'google-analytics-for-wp' ),
					'description' => __( '', 'google-analytics-for-wp' ),
					'type'        => 'checkbox',
					'default'     => ''
				)
			)
		);

		$settings = apply_filters( $this->base . '_settings_fields', $settings );

		return $settings;
	}

	public function add_menu_item() {
		add_menu_page( __( 'Google Analytics', 'google-analytics-for-wp' ),
			__( 'Google Analytics', 'google-analytics-for-wp' ), 'manage_options',
			$this->base . '_settings', array( $this, 'settings_page' ), 'dashicons-chart-area', 88 );

	}

	public function add_settings_link( $links ) {
		$settings_link = array(
			'<a href="' . admin_url( 'options-general.php?page=' . $this->base . '_settings' ) . '">' . __( 'Settings', 'google-analytics-for-wp' ) . '</a>');

		//$settings_link = '<a href="options-general.php?page=' . $this->base . '_settings">' . __( 'Settings', 'google-analytics-for-wp' ) . '</a>';

		return array_merge( $links, $settings_link );
	}

	public function register_settings() {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) {
					continue;
				}

				// Add section to page
				add_settings_section( $section, $data['title'], array(
					$this,
					'settings_section'
				), $this->base . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->base . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array(
						$this->admin_helper,
						'display_field'
					), $this->base . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	public function settings_section( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	public function settings_page() {

		// Build page HTML
		$html = '<div class="wrap" id="' . $this->base . '_settings">' . "\n";
		$html .= '<h2>' . __( 'Google Analytics', 'google-analytics-for-wp' ) . '</h2>' . "\n";

		$tab = '';
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$tab .= $_GET['tab'];
		}

		// Show page tabs
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) {
					if ( 0 == $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link
				$tab_link = add_query_arg( array( 'tab' => $section ) );
				if ( isset( $_GET['settings-updated'] ) ) {
					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				// Output tab
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

				++ $c;
			}

			$html .= '</h2>' . "\n";
		}

		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

		ob_start();
		settings_fields( $this->base . '_settings' );
		do_settings_sections( $this->base . '_settings' );
		$html .= ob_get_clean();

		$html .= '<p class="submit">' . "\n";
		$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
		$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'google-analytics-for-wp' ) ) . '" />' . "\n";
		$html .= '</p>' . "\n";
		$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}
}

?>