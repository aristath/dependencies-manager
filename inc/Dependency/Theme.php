<?php
/**
 * Handles theme dependencies.
 *
 * @package dependencies-manager.
 * @since 1.0
 */

namespace Dependencies_Manager\Dependency;

/**
 * Handles theme dependencies.
 *
 * @since 1.0
 */
class Theme extends \Dependencies_Manager\Dependency {

	/**
	 * An array of all themes.
	 *
	 * @static
	 * @access protected
	 * @since 1.0.0
	 * @var array
	 */
	protected static $themes;

	/**
	 * Process a theme dependency.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function process_dependency() {

		if ( ! self::$themes ) {
			if ( ! function_exists( 'wp_get_themes' ) ) {
				require_once ABSPATH . 'wp-icludes/theme.php';
			}
			self::$themes = wp_get_themes();
		}

		// If the theme is not installed, prompt to install it.
		if ( ! isset( self::$themes[ $this->dependency->slug ] ) ) {
			$this->install();
			return;
		}

		$current_theme = wp_get_theme();
		// If the theme is not active, prompt to activate it.
		if (
			$current_theme->get( 'stylesheet' ) !== $this->dependency->slug ||
			$current_theme->get( 'template' ) !== $this->dependency->slug
		) {
			if ( $this->check_version() ) {
				$this->activate();
			} else {
				$this->update();
			}
		}
	}

	/**
	 * Install the theme.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return bool
	 */
	public function install() {
		var_dump( 'Install the theme' );
		// TODO.
		return true;
	}

	/**
	 * Activate the theme.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return bool
	 */
	public function activate() {
		var_dump( 'Activate the theme' );
		// TODO.
		return true;
	}

	/**
	 * Update the theme.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return bool
	 */
	public function update() {
		var_dump( 'Update the theme' );
		// TODO.
		return true;
	}

	/**
	 * Check the theme version and determine if it satisfies the minimum required version.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return bool
	 */
	public function check_version() {
		$installed_version = self::$themes[ $this->dependency->slug ]->get( 'Version' );
		$required_version  = $this->dependency->version;
		return version_compare( $installed_version, $required_version ) >= 0;
	}
}
