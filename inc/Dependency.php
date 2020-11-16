<?php
/**
 * An abstract class used as a base for plugin & theme dependencies.
 *
 * @package dependencies-manager
 * @since 1.0.0
 */

namespace Dependencies_Manager;

/**
 * An abstract class used as a base for plugin & theme dependencies.
 *
 * @since 1.0.0
 */
abstract class Dependency {

	/**
	 * The dependency.
	 *
	 * @access public
	 * @var stdClass
	 */
	public $dependency;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @since 1.0.0
	 * @param stdClass $dependency The dependency.
	 */
	public function __construct( $dependency ) {
		$this->dependency = $dependency;
		$this->process_dependency();
	}

	/**
	 * Process the dependency.
	 *
	 * @abstract
	 * @access public
	 * @since 1.0
	 * @return bool
	 */
	abstract public function process_dependency();

	/**
	 * Install the dependency.
	 *
	 * @abstract
	 * @access public
	 * @since 1.0
	 * @return bool
	 */
	abstract public function install();

	/**
	 * Activate the dependency.
	 *
	 * @abstract
	 * @access public
	 * @since 1.0
	 * @return bool
	 */
	abstract public function activate();

	/**
	 * Update the dependency.
	 *
	 * @abstract
	 * @access public
	 * @since 1.0
	 * @return bool
	 */
	abstract public function update();

	/**
	 * Check if the currently installed version satisfies the requirements.
	 *
	 * @access public
	 * @since 1.0
	 * @return bool
	 */
	public function check_version() {
		return true;
	}
}
