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
	 * The database option where we store the array of plugins that should be active
	 * but are not due to unmet dependencies.
	 *
	 * @access protected
	 * @since 1.0.0
	 * @var string
	 */
	protected $plugins_to_activate_option_name = 'plugins_to_activate_with_unmet_dependencies';

	/**
	 * Installed plugins.
	 *
	 * @access protected
	 * @since 1.0.0
	 * @var array
	 */
	protected $installed_plugins;

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
		$this->installed_plugins = get_plugins();
		// Loop installed plugins.
		foreach ( $this->installed_plugins as $file => $plugin ) {
			$this->process_plugin_dependencies( $file );
		}
	}

	/**
	 * Get an array of plugins that should be activated but are not,
	 * due to missing/unmet dependencies.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return array
	 */
	public function get_plugins_to_activate() {
		return get_option( $this->plugins_to_activate_option_name, array() );
	}

	/**
	 * Check plugin dependencies.
	 *
	 * @access public
	 * @param string $file The plugin file.
	 * @return void
	 */
	public function process_plugin_dependencies( $file ) {

		// Early exit if this plugin is not active, or we don't want to activate it.
		if ( ! is_plugin_active( $file ) && ! in_array( $file, $this->get_plugins_to_activate() ) ) {
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
			$this->process_plugin_dependency( $file, $dependency );
		}
	}

	/**
	 * Processes a plugin dependency.
	 *
	 * @access protected
	 * @since 1.0.0
	 * @param string   $plugin     The plugin defining the dependency.
	 * @param stdClass $dependency A dependency.
	 * @return void
	 */
	protected function process_plugin_dependency( $plugin, $dependency ) {
		$dependency_is_installed = false;
		$dependency_is_active    = false;

		foreach ( $this->installed_plugins as $file => $installed_plugin ) {
			if ( dirname( $file ) === $dependency->slug ) {
				$dependency_is_installed = true;
				if ( is_plugin_active( $file ) ) {
					$dependency_is_active = true;
				}
			}
		}

		if ( $dependency_is_active ) {
			return;
		}

		if ( $dependency_is_installed ) {
			$this->maybe_activate_dependency( $dependency->slug );
		}
	}

	/**
	 * Show a notice and activate an installed dependency.
	 *
	 * @access protected
	 * @since 1.0.0
	 * @return void
	 */
	protected function maybe_activate_dependency( $plugin_slug ) {
		//WIP
		foreach ( array_keys( $this->installed_plugins ) as $plugin_file ) {
			if ( 0 === strpos( $plugin_file, "$plugin_slug/" ) ) {
				activate_plugin( $plugin_file );
				return;
			}
		}
	}
}
