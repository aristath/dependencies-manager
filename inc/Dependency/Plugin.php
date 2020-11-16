<?php
/**
 * Handles plugin dependencies.
 *
 * @package dependencies-manager.
 * @since 1.0
 */

namespace Dependencies_Manager\Dependency;

use Dependencies_Manager\Notice;

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
	 * An array of all plugins slugs.
	 *
	 * @static
	 * @access protected
	 * @since 1.0.0
	 * @var array
	 */
	protected static $plugins_slugs;

	/**
	 * Get all plugins and set the object props.
	 *
	 * @access protected
	 * @since 1.0.0
	 * @return void
	 */
	protected function init_props() {
		if ( self::$plugins ) {
			return;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		self::$plugins = get_plugins();

		// Set the slugs.
		self::$plugins_slugs = [];
		foreach ( array_keys( self::$plugins ) as $plugin ) {
			self::$plugins_slugs[ explode( '/', $plugin )[0] ] = $plugin;
		}
	}

	/**
	 * Process a plugin dependency.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function process_dependency() {

		// If the plugin is not installed, prompt to install it.
		if ( ! isset( self::$plugins_slugs[ $this->dependency->slug ] ) ) {
			$this->install();
			return;
		}

		if ( $this->check_version() ) {
			$this->update();
			return;
		}

		// If the plugin is not active, prompt to activate it.
		if ( ! is_plugin_active( self::$plugins_slugs[ $this->dependency->slug ] ) ) {
			$this->activate();
			return;
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
		new Notice(
			sprintf(
				/* translators: The plugin name. */
				__( 'Missing dependency: Please install the "%s" plugin' ),
				$this->dependency->name
			)
		);
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
		new Notice(
			sprintf(
				/* translators: The plugin name. */
				__( 'Inactive dependency: Please activate the "%s" plugin' ),
				$this->dependency->name
			)
		);
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
		new Notice(
			sprintf(
				/* translators: The plugin name. */
				__( 'Outdated dependency: Please update the "%s" plugin' ),
				$this->dependency->name
			)
		);
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
		$installed_version = self::$plugins[ self::$plugins_slugs[ $this->dependency->slug ] ]['Version'];
		$required_version  = $this->dependency->version;
		return version_compare( $installed_version, $required_version ) >= 0;
	}
}
