<?php
/**
 * Notices handler.
 *
 * @package dependencies-manager.
 * @since 1.0
 */

namespace Dependencies_Manager;

/**
 * Notices Handler.
 *
 * @since 1.0.0
 */
class Notice {

	/**
	 * The notice message.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $message;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $message The notice message.
	 * @since 1.0.0
	 */
	public function __construct( $message = '' ) {
		$this->message = $message;

		add_action( 'admin_notices', [ $this, 'the_notice' ] );
	}

	/**
	 * Prints the notice.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function the_notice() {
		?>
		<div class="notice notice-error">
			<p><?php echo esc_html( $this->message ); ?></p>
		</div>
		<?php
	}
}
