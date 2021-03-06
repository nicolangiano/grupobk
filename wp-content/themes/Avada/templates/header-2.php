<?php
/**
 * Header-2 template.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       http://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}
?>
<div class="fusion-header-sticky-height"></div>
<div class="fusion-sticky-header-wrapper header-sticky" style="height:50px!important;position:sticky!important;"> <!-- start fusion sticky header wrapper -->
	<div class="fusion-header mobile-header">
		<div class="fusion-row">
			<?php avada_logo(); ?>
			<?php get_template_part( 'templates/menu-mobile-modern' ); ?>
		</div>
	</div>
