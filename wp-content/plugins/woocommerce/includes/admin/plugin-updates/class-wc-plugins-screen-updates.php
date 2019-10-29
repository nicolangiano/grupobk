<?php
/**
 * Manages WooCommerce plugin updating on the Plugins screen.
 *
 * @author      Automattic
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Plugin_Updates' ) ) {
	include_once( dirname( __FILE__ ) . '/class-wc-plugin-updates.php' );
}

class WC_Plugins_Screen_Updates extends WC_Plugin_Updates {

	/**
	 * The upgrade notice shown inline.
	 * @var string
	 */
	protected $upgrade_notice = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'in_plugin_update_message-woocommerce/woocommerce.php', array( $this, 'in_plugin_update_message' ), 10, 2 );
	}

	/**
	 * Show plugin changes on the plugins screen. Code adapted from W3 Total Cache.
	 *
	 * @param array $args
	 */
	public function in_plugin_update_message( $args, $response ) {
		$this->new_version            = $response->new_version;
		$this->upgrade_notice         = $this->get_upgrade_notice( $response->new_version );
		$this->major_untested_plugins = $this->get_untested_plugins( $response->new_version, 'major' );
		$this->minor_untested_plugins = $this->get_untested_plugins( $response->new_version, 'minor' );

		$current_version_parts = explode( '.', WC_VERSION );
		$new_version_parts     = explode( '.', $this->new_version );

		// If user has already moved to the minor version, we don't need to flag up anything.
		if ( version_compare( $current_version_parts[0] . '.' . $current_version_parts[1], $new_version_parts[0] . '.' . $new_version_parts[1], '=' ) ) {
			return;
		}

		if ( ! empty( $this->major_untested_plugins ) ) {
			$this->upgrade_notice .= $this->get_extensions_inline_warning_major();
		}

		if ( ! empty( $this->minor_untested_plugins ) ) {
			$this->upgrade_notice .= $this->get_extensions_inline_warning_minor();
		}

		if ( ! empty( $this->major_untested_plugins ) ) {
			$this->upgrade_notice .= $this->get_extensions_modal_warning();
			add_action( 'admin_print_footer_scripts', array( $this, 'plugin_screen_modal_js' ) );
		}

		echo apply_filters( 'woocommerce_in_plugin_update_message', $this->upgrade_notice ? '</p>' . wp_kses_post( $this->upgrade_notice ) . '<p class="dummy">' : '' );
	}

	/**
	 * Get the upgrade notice from WordPress.org.
	 *
	 * @param  string $version
	 * @return string
	 */
	protected function get_upgrade_notice( $version ) {
		$transient_name = 'wc_upgrade_notice_' . $version;

		if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {
			$response = wp_safe_remote_get( 'https://plugins.svn.wordpress.org/woocommerce/trunk/readme.txt' );

			if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
				$upgrade_notice = $this->parse_update_notice( $response['body'], $version );
				set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
			}
		}
		return $upgrade_notice;
	}

	/**
	 * Parse update notice from readme file.
	 *
	 * @param  string $content
	 * @param  string $new_version
	 * @return string
	 */
	private function parse_update_notice( $content, $new_version ) {
		$version_parts     = explode( '.', $new_version );
		$check_for_notices = array(
			$version_parts[0] . '.0', // Major
			$version_parts[0] . '.0.0', // Major
			$version_parts[0] . '.' . $version_parts[1], // Minor
			$version_parts[0] . '.' . $version_parts[1] . '.' . $version_parts[2], // Patch
		);
		$notice_regexp     = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( $new_version ) . '\s*=|$)~Uis';
		$upgrade_notice    = '';

		foreach ( $check_for_notices as $check_version ) {
			if ( version_compare( WC_VERSION, $check_version, '>' ) ) {
				continue;
			}

			$matches = null;
			if ( preg_match( $notice_regexp, $content, $matches ) ) {
				$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

				if ( version_compare( trim( $matches[1] ), $check_version, '=' ) ) {
					$upgrade_notice .= '<p class="wc_plugin_upgrade_notice">';

					foreach ( $notices as $index => $line ) {
						$upgrade_notice .= preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line );
					}

					$upgrade_notice .= '</p>';
				}
				break;
			}
		}
		return wp_kses_post( $upgrade_notice );
	}

	/**
	 * JS for the modal window on the plugins screen.
	 */
	public function plugin_screen_modal_js() {
		?>
		<script>
			( function( $ ) {
				var $update_box = $( '#woocommerce-update' );
				var $update_link = $update_box.find('a.update-link').first();
				var update_url = $update_link.attr( 'href' );

				// Set up thickbox.
				$update_link.removeClass( 'update-link' );
				$update_link.addClass( 'wc-thickbox' );
				$update_link.attr( 'href', '#TB_inline?height=600&width=550&inlineId=wc_untested_extensions_modal' );

				// Trigger the update if the user accepts the modal's warning.
				$( '#wc_untested_extensions_modal .accept' ).on( 'click', function( evt ) {
					evt.preventDefault();
					tb_remove();
					$update_link.removeClass( 'wc-thickbox open-plugin-details-modal' );
					$update_link.addClass( 'update-link' );
					$update_link.attr( 'href', update_url );
					$update_link.click();
				});

				$( '#wc_untested_extensions_modal .cancel' ).on( 'click', function( evt ) {
					evt.preventDefault();
					tb_remove();
				});
			})( jQuery );
		</script>
		<?php
		$this->generic_modal_js();
	}
}
new WC_Plugins_Screen_Updates();
