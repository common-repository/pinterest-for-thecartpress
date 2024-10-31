<?php
/*
Plugin Name: TheCartPress Pinterest
Plugin URI: http://extend.thecartpress.com/ecommerce-plugins/pinterest/
Description: Pinterest button for TheCartPress
Version: 1.0.1
Author: TheCartPress team
Author URI: http://thecartpress.com
License: GPL
Parent: thecartpress
*/

/**
 * This file is part of TheCartPress-Pinterest.
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

define( 'TCP_PINTEREST_FOLDER'	, dirname( __FILE__ ) . '/' );

class TCPPinterest {

	function __construct() {
		add_action( 'wp_head', array( &$this, 'wp_head' ) );
		add_filter( 'the_content', array( &$this, 'the_content' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ), 90 );
	}

	function wp_head() { ?>
		<script type="text/javascript" src="//assets.pinterest.com/js/pinit.js"></script>
	<?php }

	function admin_head() { ?>
<style type="text/css">
div#icon-tcp-pinterest {
	background: url("<?php echo plugins_url( 'images/pinterest_settings_32.png', __FILE__ ); ?>") no-repeat center;
}
</style>
	<?php }

	function the_content( $content ) {
		if ( is_single() ) {
			$settings = get_option( 'tcp_pinterest', array() );
			$post_types		= isset( $settings['post_types'] ) ? $settings['post_types'] : array();
			if ( in_array( get_post_type(), $post_types ) ) {
				$thumbnail_size	= isset( $settings['thumbnail_size'] ) ? $settings['thumbnail_size'] : 'large';
				$position		= isset( $settings['position'] ) ? $settings['position'] : 'north';
				$add_price		= isset( $settings['add_price'] ) ? $settings['add_price'] : false;
				global $post;
				$url = add_query_arg( 'url', get_permalink(), 'http://pinterest.com/pin/create/button' );
				$image_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), $thumbnail_size );
				$image_src = urlencode( $image_src[0] );
				$url = add_query_arg( 'media', $image_src, $url );
				$description = $post->post_excerpt;
				if ( strlen( $description ) == 0 ) $description = wp_trim_excerpt( $post->post_content );
				if ( $add_price && tcp_is_saleable( get_the_ID() ) ) $description .= ' ' . sprintf( __( 'And only for %s', 'tcp-pinterest' ), tcp_get_the_price_label() );
				$url = add_query_arg( 'description', $description, $url );
				$html = '<a href="' . $url . '" class="pin-it-button" count-layout="horizontal" target="_blank"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a>';
				if ( 'north' == $position ) return $html . $content;
				else return $content . $html;
			}
		}
		return $content;
	}

	function admin_init() {
		global $tcp_miranda;
		if ( $tcp_miranda ) $tcp_miranda->add_item( 'settings', 'default_settings', __( 'Pinterest', 'tcp-pinterest' ), false, array( 'TCPPinterest', __FILE__ ), plugins_url( 'thecartpress-pinterest/images/pinterest_settings_48.png' ) );
	}

	function admin_menu() {
		if ( ! current_user_can( 'tcp_edit_settings' ) ) return;
		global $thecartpress;
		if ( $thecartpress ) {
			$base = $thecartpress->get_base_appearance();
			$page = add_submenu_page( $base, __( 'Pinterest', 'tcp-pinterest' ), __( 'Pinterest', 'tcp-pinterest' ), 'tcp_edit_settings', 'pinterest_settings', array( &$this, 'admin_page' ) );
			add_action( "load-$page", array( &$this, 'admin_load' ) );
			add_action( "load-$page", array( &$this, 'admin_action' ) );
			add_action( 'admin_head-'. $page, array( &$this, 'admin_head' ) );
		}
	}

	function admin_load() {
		get_current_screen()->add_help_tab( array(
		    'id'      => 'overview',
		    'title'   => __( 'Overview' ),
		    'content' =>
	            '<p>' . __( 'You can customize Pinterest for TheCartPress.', 'tcp-pinterest' ) . '</p>'
		) );

		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'tcp-pinterest' ) . '</strong></p>' .
			'<p>' . __( '<a href="http://thecartpress.com" target="_blank">Documentation on TheCartPress</a>', 'tcp-pinterest' ) . '</p>' .
			'<p>' . __( '<a href="http://community.thecartpress.com/" target="_blank">Support Forums</a>', 'tcp-pinterest' ) . '</p>' .
			'<p>' . __( '<a href="http://extend.thecartpress.com/" target="_blank">Extend site</a>', 'tcp-pinterest' ) . '</p>'
		);
	}

	function admin_page() {
		$settings = get_option( 'tcp_pinterest', array() );
		$thumbnail_size	= isset( $settings['thumbnail_size'] ) ? $settings['thumbnail_size'] : 'large';
		$position		= isset( $settings['position'] ) ? $settings['position'] : 'north';
		$post_types		= isset( $settings['post_types'] ) ? $settings['post_types'] : array(); //array( TCP_PRODUCT_POST_TYPE );
		$add_price		= isset( $settings['add_price'] ) ? $settings['add_price'] : false;
	?>
<div class="wrap">
	<?php screen_icon( 'tcp-pinterest' ); ?><h2><?php _e( 'Pinterest for TheCartPress', 'tcp-pinterest' ); ?></h2>

<?php if ( ! empty( $this->updated ) ) : ?>
	<div id="message" class="updated">
	<p><?php _e( 'Settings updated', 'tcp-pinterest' ); ?></p>
	</div>
<?php endif; ?>

<form method="post">

<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row">
				<label for="post_types"><?php _e( 'Display in', 'tcp' ); ?></label>
			</th>
			<td>
			<?php $types = get_post_types( array( 'public' => true ), 'objects' );
			foreach( $types as $type ) : ?>
				<input type="checkbox" name="post_types[]" id="post_types_<?php echo $type->name; ?>" value="<?php echo $type->name; ?>" <?php tcp_checked_multiple( $post_types, $type->name ); ?> />
				&nbsp;<?php echo $type->labels->name; ?><br/>
			<?php endforeach; ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="thumbnail_size"><?php _e( 'Thumbnail Size', 'tcp' ); ?></label>
			</th>
			<td>
				<select name="thumbnail_size" id="thumbnail_size">
				<?php $image_sizes = get_intermediate_image_sizes();
				foreach( $image_sizes as $image_size) : ?>
					<option value="<?php echo $image_size; ?>" <?php selected( $image_size, $thumbnail_size ); ?>><?php echo $image_size; ?></option>
				<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="button_position"><?php _e( 'Button Position', 'tcp' ); ?></label>
			</th>
			<td>
				<select name="position" id="position">
					<option value="north" <?php selected( 'north', $position ); ?>><?php _e( 'North', 'tcp-pinterest' ); ?></option>
					<option value="south" <?php selected( 'south', $position ); ?>><?php _e( 'South', 'tcp-pinterest' ); ?></option>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="add_price"><?php _e( 'Add Price', 'tcp' ); ?></label>
			</th>
			<td>
				<input type="checkbox" name="add_price" id="add_price" value="yes" <?php checked( $add_price ); ?> />
				<p class="description"><?php _e( 'Allows to add the price in the description.', 'tcp-pinterest' ); ?></p>
			</td>
		</tr>
	</tbody>
</table>
<?php wp_nonce_field( 'tcp_pinterest_settings' ); ?>
<?php submit_button( null, 'primary', 'save-pinterest-settings' ); ?>
</form>
</div>
<?php
	}

	function admin_action() {
		if ( empty( $_POST ) ) return;
		check_admin_referer( 'tcp_pinterest_settings' );	
		$values = array(
			'thumbnail_size'	=> isset( $_POST['thumbnail_size'] ) ? $_POST['thumbnail_size'] : 'large',
			'position'			=> isset( $_POST['position'] ) ? $_POST['position'] : 'north',
			'post_types'		=> isset( $_POST['post_types'] ) ? $_POST['post_types'] : array(),
			'add_price'			=> isset( $_POST['add_price'] ),
		);
		update_option( 'tcp_pinterest', $values );
		$this->updated = true;
	}
}

new TCPPinterest();
?>