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
	 * Process a plugin dependency.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function process_dependency() {

		// If the plugin is not installed, prompt to install it.
		if ( ! isset( $plugins[ $this->dependency->file ] ) ) {
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
		// TODO.
		return true;
	}
}
