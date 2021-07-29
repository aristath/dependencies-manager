<?php
/**
 * Plugin Name: Dependencies Manager
 *
 * @package dependencies-manager
 */

require_once 'inc/Dependency.php';
require_once 'inc/Dependency/Plugin.php';

require_once 'class-wp-plugins-dependencies.php';

new WP_Plugins_Dependencies();
