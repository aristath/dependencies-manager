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
	 * The notice title.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $title;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $title The notice title.
	 * @param string $message The notice message.
	 * @since 1.0.0
	 */
	public function __construct( $title = '', $message = '' ) {
		$this->title   = $title;
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
		<div class="notice warning">
			<h2 class="notice-title"><?php echo esc_html( $this->title ); ?></h2>
			<?php echo esc_html( $this->message ); ?>
		</div>
		<?php
	}
}
