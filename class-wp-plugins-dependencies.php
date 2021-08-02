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
	protected $plugins_to_activate_option_name = 'plugins_to_activate_with_unmet_dependencies';

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
		add_action( 'plugins_loaded', array( $this, 'run' ) );
		add_action( 'plugins_loaded', array( $this, 'cancel_activation_request' ) );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
		add_action( 'wp_ajax_plugin_dependencies_activate_plugin', [ $this, 'ajax_activate_dependency' ] );
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

		add_action( 'admin_footer', array( $this, 'the_script' ) );
	}

	/**
	 * Run dependencies.
	 *
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
			$this->maybe_process_plugin_dependencies( $file );
		}
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
			if ( ! $this->process_plugin_dependency( $file, $dependency ) ) {
				$dependencies_met = false;
			}
		}

		// Make sure plugin is deactivated when its dependencies are not met.
		if ( ! $dependencies_met ) {
			if ( $plugin_is_active ) {
				deactivate_plugins( $file );
			}

			// Add plugin to queue of plugins to be activated.
			$this->add_plugin_to_queue( $file );

			// Remove the "Activate" link from plugin,
			// and add a "Cancel activation request" link in its place.
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

		} elseif ( $plugin_awaiting_activation ) {
			activate_plugin( $file );
			$this->remove_plugin_from_queue( $file );
		}
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

		// If the plugin is already activated, disable its deactivation
		// and return true.
		if ( $dependency_is_active ) {
			$this->disallow_disabling_dependency( $plugin, $dependency );
			return true;
		}

		// If the dependency is not installed, install it, otherwise activate it.
		if ( ! $dependency_is_installed ) {
			$this->maybe_install_dependency( get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin ), $dependency );
		} else {
			$this->maybe_activate_dependency( get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin ), $dependency );
		}
		return false;
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
				if ( ! empty( $actions['deactivate'] ) ) {
					$actions['deactivate'] = sprintf(
						/* translators: %s: plugin name. */
						'<span style="color:#888;">' . __( 'Plugin can not be deactivated because it is a dependency for the "%s" plugin' ) . '</span>',
						get_plugin_data(  WP_PLUGIN_DIR . '/' . $plugin  )['Name']
					);
				}
				return $actions;
			}
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

		$this->notices[] = array(
			'content' => sprintf(
				/* translators: %1$s: The plugin we want to activate. %2$s: The name of the plugin to install. %3$s: "Install & Activate" button. */
				__( 'Plugin "%1$s" depends on plugin "%2$s" to be installed. %3$s' ),
				$plugin['Name'],
				$dependency->name,
				sprintf(
					'<button class="%s" onclick="%s">%s</button>',
					"button install-dependency-{$dependency->slug}",
					"window.installAndActivatePlugin('{$dependency->slug}');",
					__( 'Install and activate dependency' )
				)
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
		$this->notices[] = array(
			'content' => sprintf(
				/* translators: %1$s: The plugin we want to activate. %2$s: The name of the plugin to install. %3$s: "Activate" button. */
				__( 'Plugin "%1$s" depends on plugin "%2$s" to be activated. %3$s' ),
				$plugin['Name'],
				$dependency->name,
				sprintf(
					'<button class="%s" onclick="%s">%s</button>',
					"button activate-dependency-{$dependency->slug}",
					"window.activatePlugin('{$dependency->file}');",
					__( 'Activate dependency' )
				)
			),
		);
	}

	/**
	 * Activates the Gutenberg plugin.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function ajax_activate_dependency() {

		// Early exit if the user doesn't have the capability to activate plugins.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die();
		}

		// Security check.
		check_ajax_referer( 'plugin_dependencies', 'nonce' );

		$plugin_file = $_GET['dependencyFile'];

		// Activate plugin.
		$result = activate_plugin( $plugin_file );

		// Plugin was successfully activated. Exit with success message.
		if ( ! is_wp_error( $result ) ) {
			wp_die( 'success' );
		}

		// Something went wrong, exit with error message.
		wp_die( 'error' );
	}

	/**
	 * Print script for our notice.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function the_script() {
		?>
		<script>
		window.installAndActivatePlugin = ( slug ) => {

			jQuery( 'button.install-dependency-' + slug )
				.html( '<?php esc_html_e( 'Installing plugin, please wait. The page will refresh automatically when done' ); ?>' )
				.attr( 'disabled', true );

			// Install the plugin.
			wp.updates.installPlugin( {
				slug: slug,
				success: function( res ) {
					// Redirect to activation URL.
					window.location = res.activateUrl;
					// window.location.reload();
				},
				error: function( e ) {
					// TODO.
				}
			} );
		};
		window.activatePlugin = ( file ) => {
			jQuery( 'button.activate-dependency-' + file.split( '/' )[0] )
				.html( '<?php esc_html_e( 'Activating plugin, please wait. The page will refresh automatically when done' ); ?>' )
				.attr( 'disabled', true );

			// AJAX request to activate the plugin.
			jQuery.get( ajaxurl, {
				action: 'plugin_dependencies_activate_plugin',
				nonce: '<?php echo esc_html( wp_create_nonce( 'plugin_dependencies' ) ); ?>',
				dependencyFile: file,
			}, function( response ) {
				if ( 'success' === response ) {
					window.location.reload();
				} else {
					// TODO.
				}
			} );
		};
		</script>
		<?php
	}
}
