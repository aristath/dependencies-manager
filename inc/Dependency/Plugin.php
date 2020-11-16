<?php
/**
 * Handles plugin dependencies.
 *
 * @package dependencies-manager.
 * @since 1.0
 */

namespace Dependencies_Manager\Dependency;

/**
 * Handles plugin dependencies.
 *
 * @since 1.0
 */
class Plugin extends \Dependencies_Manager\Dependency {

	/**
	 * An array of all plugins.
	 *
	 * @static
	 * @access protected
	 * @since 1.0.0
	 * @var array
	 */
	protected static $plugins;

	/**
	 * Process a plugin dependency.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function process_dependency() {

		if ( ! self::$plugins ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			self::$plugins = get_plugins();
		}

		// If the plugin is not installed, prompt to install it.
		if ( ! isset( self::$plugins[ $this->dependency->file ] ) ) {
			$this->install();
			return;
		}

		// If the plugin is not active, prompt to activate it.
		if ( ! is_plugin_active( $this->dependency->file ) ) {
			if ( $this->check_version() ) {
				$this->activate();
			}
		}
	}

	/**
	 * Install the plugin.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return bool
	 */
	public function install() {
		var_dump( 'Install the plugin' );
		// TODO.
		return true;
	}

	/**
	 * Activate the plugin.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return bool
	 */
	public function activate() {
		var_dump( 'Activate the plugin' );
		// TODO.
		return true;
	}

	/**
	 * Update the plugin.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return bool
	 */
	public function update() {
		var_dump( 'Update the plugin' );
		// TODO.
		return true;
	}

	/**
	 * Check the plugin version and determine if it satisfies the minimum required version.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return bool
	 */
	public function check_version() {
		$installed_version = self::$plugins[ $this->dependency->file ]['Version'];
		$required_version  = $this->dependency->version;
		return version_compare( $installed_version, $required_version ) >= 0;
	}
}
