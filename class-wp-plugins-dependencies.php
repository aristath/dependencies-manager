<?php
/**
 * Dependencies manager for plugins.
 *
 * @package dependencies-manager.
 * @since 1.0
 */

/**
 * Plugins dependencies manager.
 *
 * @since 1.0.0
 */
class WP_Plugins_Dependencies {

	/**
	 * Constructor.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'run' ) );
	}

	/**
	 * Run dependencies.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function run() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get an array of all plugins.
		$plugins = get_plugins();

		// Loop plugins.
		foreach ( array_keys( $plugins ) as $file ) {
			$this->plugin_dependencies( $file );
		}
	}

	/**
	 * Check plugin dependencies.
	 *
	 * @access public
	 * @param string $file The plugin file.
	 * @return void
	 */
	public function plugin_dependencies( $file ) {

		// Early exit if this plugin is not active.
		if ( ! is_plugin_active( $file ) ) {
			return;
		}

		// Get the plugin directory.
		$plugin_dir = dirname( WP_PLUGIN_DIR . '/' . $file );

		// Early exit if a dependencies.json file does not exist in this plugin.
		if ( ! file_exists( "$plugin_dir/dependencies.json" ) ) {
			return;
		}

		// Get dependencies.
		$dependencies = json_decode( file_get_contents( "$plugin_dir/dependencies.json" ) );

		// Loop dependencies.
		foreach ( $dependencies as $dependency ) {
			new \Dependencies_Manager\Dependency\Plugin( $dependency );
			break;
		}
	}
}
