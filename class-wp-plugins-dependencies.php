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
	 * @var string
	 */
	protected $plugins_to_activate_option_name = 'pending_plugin_activations';

	/**
	 * Installed plugins.
	 *
	 * @var array
	 */
	protected $installed_plugins;

	/**
	 * An array of admin notices to show.
	 *
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Constructor.
	 *
	 * Add hooks.
	 */
	public function __construct() {
		// Get an array of installed plugins and set it in the object's $installed_plugins prop.
		$this->get_plugins();

		// Add a hook to allow canceling an activation request.
		add_action( 'plugins_loaded', array( $this, 'cancel_activation_request' ) );

		// Go through installed plugins and process their dependencies.
		add_action( 'plugins_loaded', array( $this, 'loop_installed_plugins' ) );

		// Add the admin notices.
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	/**
	 * Get an array of installed plugins and set it in the object's $installed_plugins prop.
	 *
	 * @return void
	 */
	protected function get_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get an array of all plugins.
		$this->installed_plugins = get_plugins();
	}

	/**
	 * Loop installed plugins and process dependencies.
	 *
	 * @return void
	 */
	public function loop_installed_plugins() {
		// Loop installed plugins.
		foreach ( $this->installed_plugins as $file => $plugin ) {
			$this->maybe_process_plugin_dependencies( $file );
		}
	}

	/**
	 * Check plugin dependencies.
	 *
	 * @param string $file The plugin file.
	 *
	 * @return void
	 */
	public function maybe_process_plugin_dependencies( $file ) {

		$plugin_is_active           = is_plugin_active( $file );
		$plugin_awaiting_activation = in_array( $file, $this->get_plugins_to_activate() );

		// Early return if the plugin is not active or we don't want to activate it.
		if ( ! $plugin_is_active && ! $plugin_awaiting_activation ) {
			return;
		}

		// Get the dependencies.
		$dependencies = $this->get_plugin_dependencies( $file );

		// Early return if there are no dependencies.
		if ( empty( $dependencies ) ) {
			return;
		}

		// Loop dependencies.
		$dependencies_met = true;
		foreach ( $dependencies as $dependency ) {

			// Set $dependencies_met to false if one of the dependencies is not met.
			if ( ! $this->process_plugin_dependency( $file, $dependency ) ) {
				$dependencies_met = false;
			}
		}

		if ( ! $dependencies_met ) {

			// Make sure plugin is deactivated when its dependencies are not met.
			if ( $plugin_is_active ) {
				deactivate_plugins( $file );
			}

			// Add plugin to queue of plugins to be activated.
			$this->add_plugin_to_queue( $file );

			// Replace the plugin's "Activate" action.
			$this->replace_activation_action_link( $file );

		} elseif ( $plugin_awaiting_activation ) {
			activate_plugin( $file );
			$this->remove_plugin_from_queue( $file );
		}
	}

	/**
	 * Get an array of dependencies.
	 *
	 * @param string $file The plugin file.
	 *
	 * @return array
	 */
	public function get_plugin_dependencies( $file ) {
		// Get the plugin directory.
		$plugin_dir = dirname( WP_PLUGIN_DIR . '/' . $file );

		$dependencies = array();

		// Early exit if a dependencies.json file does not exist in this plugin.
		if ( file_exists( "$plugin_dir/dependencies.json" ) ) {
			$dependencies = json_decode( file_get_contents( "$plugin_dir/dependencies.json" ) );
		}

		return $dependencies;
	}

	/**
	 * Processes a plugin dependency.
	 *
	 * @param string   $plugin     The plugin defining the dependency.json
	 * @param stdClass $dependency A dependency.
	 *
	 * @return void
	 */
	protected function process_plugin_dependency( $plugin, $dependency ) {
		$dependency_is_installed = false;
		$dependency_is_active    = false;

		foreach ( $this->installed_plugins as $file => $installed_plugin ) {
			if ( dirname( $file ) === $dependency->slug ) {
				$dependency->file = $file;
				$dependency_is_installed = true;
				if ( is_plugin_active( $file ) ) {
					$dependency_is_active = true;
				}
				break;
			}
		}

		// If the dependency is not installed, install it, otherwise activate it.
		if ( ! $dependency_is_installed ) {
			$this->maybe_install_dependency( get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin ), $dependency );
			return false;
		}

		if ( ! $dependency_is_active ) {
			$this->maybe_activate_dependency( get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin ), $dependency );
			return false;
		}

		// If the plugin is already activated, disable its deactivation
		// and return true.
		$this->disallow_disabling_dependency( $plugin, $dependency );
		return true;
	}

	/**
	 * Add notices.
	 *
	 * @return void
	 */
	public function admin_notices() {
		// Early return if there are no notices to display.
		if ( empty( $this->notices ) ) {
			return;
		}

		foreach ( $this->notices as $notice ) {
			echo '<div class="notice notice-warning plugin-dependencies"><p>' . $notice['content'] . '</p></div>';
		}
	}

	/**
	 * Removes the activation link from a plugin's actions,
	 * and adds a "Cancel pending activation" link in its place.
	 *
	 * @param string $file The plugin file.
	 *
	 * @return void
	 */
	protected function replace_activation_action_link( $file ) {
		add_filter(
			"plugin_action_links_{$file}",
			function( $actions ) use ( $file ) {
				if ( ! empty( $actions['activate'] ) ) {
					unset( $actions['activate'] );
				}
				if ( current_user_can( 'activate_plugin', $file ) ) {
					$cancel_activation = sprintf(
						'<a href="%s" class="cancel-activate unmet-dependencies" aria-label="%s">%s</a>',
						wp_nonce_url( 'plugins.php?action=cancel-activate&amp;plugin=' . urlencode( $file ), 'cancel-activate-plugin_' . $file ),
						/* translators: %s: Plugin name. */
						esc_attr( sprintf( _x( 'Cancel activation of %s', 'plugin' ), get_plugin_data(  WP_PLUGIN_DIR . '/' . $file  )['Name'] ) ),
						__( 'Cancel activation request' )
					);

					$actions = array_merge( array( 'cancel-activation' => $cancel_activation ), $actions );
				}
				return $actions;
			}
		);
	}

	/**
	 * Cancel plugin's activation request.
	 *
	 * @return void
	 */
	public function cancel_activation_request() {
		if ( ! empty( $_GET['action'] ) && 'cancel-activate' === $_GET['action'] ) {
			$file = $_GET['plugin'];
			check_admin_referer( 'cancel-activate-plugin_' . $file );

			$this->remove_plugin_from_queue( $file );
		}
	}

	/**
	 * Disallow deactivating a plugin which is a dependency.
	 *
	 * @param string   $plugin     The plugin defining the dependency.json
	 * @param stdClass $dependency A dependency
	 *
	 * @return void
	 */
	protected function disallow_disabling_dependency( $plugin, $dependency ) {
		add_filter(
			"plugin_action_links_{$dependency->file}",
			function( $actions ) use ( $plugin ) {
				unset( $actions['deactivate'] );
				return $actions;
			}
		);

		add_action(
			"after_plugin_row",
			function( $plugin_file, $plugin_data, $status ) use ( $plugin, $dependency ) {
				if ( $dependency->file !== $plugin_file ) {
					return;
				}

				$style = is_rtl() ? 'border-top:none;border-left:none' : 'border-top:none;border-right:none';
				echo '<td colspan="5" class="notice notice-info notice-alt" style="' . $style . '">';
				printf(
					/* translators: %s: plugin name. */
					__( 'The %1$s Plugin is a dependency for the "%2$s" plugin' ),
					$dependency->name,
					get_plugin_data(  WP_PLUGIN_DIR . '/' . $plugin  )['Name']
				);
				echo '</td>';
			},
			10,
			3
		);
	}

	/**
	 * Show a notice to install a dependency.
	 *
	 * @param array    $plugin     The plugin calling the dependencies.
	 * @param stdClass $dependency The plugin slug.
	 *
	 * @return void
	 */
	protected function maybe_install_dependency( $plugin, $dependency ) {
		if ( ! function_exists( 'install_plugin_install_status' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		$this->notices[] = array(
			'content' => sprintf(
				/* translators: %1$s: The plugin we want to activate. %2$s: The name of the plugin to install. %3$s: "Install & Activate" button. */
				__( 'Plugin "%1$s" depends on plugin "%2$s" to be installed. %3$s' ),
				$plugin['Name'],
				$dependency->name,
				/* translators: %s: Plugin name. */
				'<a href="' . esc_url( install_plugin_install_status( array( 'slug'=>$dependency->name ) )['url'] ) . '">' . sprintf( __( 'Install and activate %s' ), $dependency->name ) . '</a>',
			),
		);
	}

	/**
	 * Show a notice to activate a dependency.
	 *
	 * @param array    $plugin     The plugin calling the dependencies.
	 * @param stdClass $dependency The plugin slug.
	 *
	 * @return void
	 */
	protected function maybe_activate_dependency( $plugin, $dependency ) {
		$activate_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . urlencode( $dependency->file ) . '&amp;plugin_status=all', 'activate-plugin_' . $dependency->file );

		$this->notices[] = array(
			'content' => sprintf(
				/* translators: %1$s: The plugin we want to activate. %2$s: The name of the plugin to install. %3$s: "Activate" button. */
				__( 'Plugin "%1$s" depends on plugin "%2$s" to be activated. %3$s' ),
				$plugin['Name'],
				$dependency->name,
				'<a href="' . $activate_url . '">' . __( 'Activate plugin' ) . '</a>'
			),
		);
	}

	/**
	 * Get an array of plugins that should be activated but are not,
	 * due to missing/unmet dependencies.
	 *
	 * @return array
	 */
	public function get_plugins_to_activate() {
		return get_option( $this->plugins_to_activate_option_name, array() );
	}

	/**
	 * Set plugin to the to-be-activated queue.
	 *
	 * @access protected
	 *
	 * @param string $plugin The plugin file.
	 *
	 * @return bool
	 */
	protected function add_plugin_to_queue( $plugin ) {
		$queue = $this->get_plugins_to_activate();
		if ( in_array( $plugin, $queue ) ) {
			return true;
		}
		$queue[] = $plugin;
		return update_option( $this->plugins_to_activate_option_name, $queue );
	}

	/**
	 * Remove plugin from the to-be-activated queue.
	 *
	 * @access protected
	 *
	 * @param string $plugin The plugin file.
	 *
	 * @return bool
	 */
	protected function remove_plugin_from_queue( $plugin ) {
		$queue = $this->get_plugins_to_activate();
		if ( ! in_array( $plugin, $queue ) ) {
			return true;
		}
		return update_option( $this->plugins_to_activate_option_name, array_diff( $queue, array( $plugin ) ) );
	}
}
