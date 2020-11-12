<?php
/**
 * Init dependencies manager.
 *
 * @package dependencies-manager.
 * @since 1.0
 */

namespace Dependencies_Manager;

/**
 * Init dependencies manager.
 *
 * @since 1.0.0
 */
class Init {

	/**
	 * Constructor.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->run();
	}

	/**
	 * Run dependencies.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function run() {
		new Dependencies\Plugins();
	}
}
