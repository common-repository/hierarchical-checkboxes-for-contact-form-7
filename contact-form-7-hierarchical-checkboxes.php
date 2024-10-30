<?php
/*
Plugin Name: Contact Form 7 Hierarchical Checkboxes
Plugin URI: http://www.chromatix.com.au/hierarchical-checkboxes-for-contact-form-7-wordpress-plugin
Description: Adds a hierarchical list of checkboxes controlled via the wordpress menu interface to the popular Contact Form 7 plugin.
Author: Chromatix Web Design
Author URI: http://www.chromatix.com.au
Version: 1.03
*/

/*  Copyright 2013  Chromatix 

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
	
*/


 add_action('plugins_loaded', 'wpcf7_hierarchical_checkboxes_loader', 10);

function wpcf7_hierarchical_checkboxes_loader() {
	global $pagenow;
	if (function_exists('wpcf7_add_shortcode')) {
		wpcf7_add_shortcode( array( 'hierarchicalcheckboxes', 'hierarchicalcheckboxes*' ), 'wpcf7_hierarchical_checkboxes_shortcode_handler', true );		
		wp_enqueue_script('jquery');
		wp_register_script('hierarchical_checkboxes_script',  plugins_url('/js/script.js', __FILE__), array('jquery'), false);	
		wp_enqueue_script('hierarchical_checkboxes_script');
	} else {
		if ($pagenow != 'plugins.php') { return; }
		add_action('admin_notices', 'cfhiddenfieldserror');
		wp_enqueue_script('thickbox');
		function cfhiddenfieldserror() {
			$out = '<div class="error" id="messages"><p>';
			if(file_exists(WP_PLUGIN_DIR.'/contact-form-7/wp-contact-form-7.php')) {
				$out .= 'The Contact Form 7 is installed, but <strong>you must activate Contact Form 7</strong> below for the Hierarchical Checkboxes Module to work.';
			} else {
				$out .= 'The Contact Form 7 plugin must be installed for the Hierarchical Checkboxes Module to work. <a href="'.admin_url('plugin-install.php?tab=plugin-information&plugin=contact-form-7&from=plugins&TB_iframe=true&width=600&height=550').'" class="thickbox" title="Contact Form 7">Install Now.</a>';
			}
			$out .= '</p></div>';	
			echo $out;
		}
	}
}

function wpcf7_hierarchical_checkboxes_shortcode_handler( $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';
	
	global $wpcf7_contact_form;
	
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		$validation_error = $wpcf7_contact_form->validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type );
	
	if ( 'hierarchicalcheckboxes*' == $tag->type )
		$class .= ' wpcf7-validates-as-required';
		
	if ( $validation_error )
		$class .= ' wpcf7-not-valid';

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );	
	$atts['id'] = $tag->get_option( 'id', 'id', true );
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );
	$menu_list = $tag->get_option( 'menu_list', 'id', true );			
	$other_field = $tag->has_option( 'other_field' );
	
	$html = '';
	
	global $wpdb;
	$myrows = $wpdb->get_results( "SELECT p1.*, case p1.post_title when '' then p2.post_title else p1.post_title end as post_title, p2.guid FROM $wpdb->posts p1
inner join $wpdb->term_relationships tr on tr.object_id = p1.ID 
inner join $wpdb->postmeta pm on pm.post_id = p1.ID
inner join $wpdb->postmeta pm2 on pm2.post_id = p1.ID
inner join `wp_posts` p2 on p2.ID = pm.meta_value
where p1.post_type = 'nav_menu_item'
and tr.term_taxonomy_id = ( select t1.term_id from $wpdb->terms t1 where t1.slug = '" . $menu_list . "')
and pm.meta_key = '_menu_item_object_id'
and pm2.meta_key = '_menu_item_menu_item_parent'
and pm2.meta_value = 0
order by p1.menu_order ASC", ARRAY_A);

	foreach ($myrows as $row) {		
		$html .= wpcf7_hierarchical_checkboxes_get_checkbox_list($row['ID'], $menu_list, $tag->name, $row['post_title']);		
	}
	
	if ($other_field) 
	{
		$html .= '<span class="hierarchicalcheckboxes-other-wrapper" style="display:block;"><span class="hierarchicalcheckboxes-other-label">' . apply_filters('wpcf7_hierarchical_checkboxes_get_other_text', wpcf7_hierarchical_checkboxes_get_other_text() ) . '</span><input class="hierarchicalcheckboxes-other-field" type="text" name="' . $tag->name . '-text" id="' . $tag->name . '-text" /></span>' ;
	}
	
	$atts = wpcf7_format_atts( $atts );	
	
	$html = '<span class="wpcf7-form-control-wrap ' . $tag->name . ' hierarchicalcheckboxes" style="display:block;"><span ' . $atts . '  style="display:block;">'. $html .'</span>'. $validation_error .'</span>';
	
	return $html;
}

function wpcf7_hierarchical_checkboxes_get_checkbox_list($parent_id, $menu_list, $title, $value) {
	
	global $wpdb;
	$myrows2 = $wpdb->get_results( "SELECT p1.*, case p1.post_title when '' then p2.post_title else p1.post_title end as post_title, p2.guid FROM $wpdb->posts p1
inner join $wpdb->term_relationships tr on tr.object_id = p1.ID 
inner join $wpdb->postmeta pm on pm.post_id = p1.ID
inner join $wpdb->postmeta pm2 on pm2.post_id = p1.ID
inner join `wp_posts` p2 on p2.ID = pm.meta_value
where p1.post_type = 'nav_menu_item'
and tr.term_taxonomy_id = ( select t1.term_id from $wpdb->terms t1 where t1.slug = '" . $menu_list . "')
and pm.meta_key = '_menu_item_object_id'
and pm2.meta_key = '_menu_item_menu_item_parent'
and pm2.meta_value = " . $parent_id . "
order by p1.menu_order ASC", ARRAY_A);
		$submenu = '';
		if (count($myrows2) > 0) {
			$submenu .= '<span class="wpcf7-list-item" style="display:block"><a href="#">' . $value .'</a><span class="wpcf7-list-item-parent" style="display:block;">';
			foreach ($myrows2 as $row2) {
				$submenu .= wpcf7_hierarchical_checkboxes_get_checkbox_list($row2['ID'], $menu_list, $title, $row2['post_title']); 
			}
			$submenu .= '</span></span>';
		}
		else 
		{
			$submenu .= '<span class="wpcf7-list-item" style="display:block;"><input name="' . $title . '[]" type="checkbox" value="' . $value .'">' . $value .'</input></span>';
		}
	
	return $submenu;
}

function wpcf7_hierarchical_checkboxes_get_other_text() {
	return 'Other:';
}


/* Tag generator */

add_action( 'admin_init', 'wpcf7_add_tag_generator_hierarchical_checkboxes', 30 );

function wpcf7_add_tag_generator_hierarchical_checkboxes() {
	if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
		return;

	wpcf7_add_tag_generator( 'hierarchicalcheckboxes', __( 'Hierarchical Checkboxes', 'wpcf7' ),
		'wpcf7-tg-pane-hierarchicalcheckboxes', 'wpcf7_tg_pane_hierarchicalcheckboxes' );
}

function wpcf7_tg_pane_hierarchicalcheckboxes( &$contact_form ) {
?>
<div id="wpcf7-tg-pane-hierarchicalcheckboxes" class="hidden">
<form action="">
<table>
<tr><td><input type="checkbox" name="required" />&nbsp;<?php echo esc_html( __( 'Required field?', 'wpcf7' ) ); ?></td></tr>

<tr><td><?php echo esc_html( __( 'Name', 'wpcf7' ) ); ?><br /><input type="text" name="name" class="tg-name oneline" /></td><td></td></tr>
</table>

<table>
<tr>
<td><code>id</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="id" class="idvalue oneline option" /></td>

<td><code>class</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="class" class="classvalue oneline option" /></td>
</tr>
<tr><td><code>Wordpress menu slug</code><br />
<input type="text" name="menu_list" class="classvalue oneline option" /></td>
<td><input type="checkbox" name="other_field" class="option" />&nbsp;<?php echo esc_html( __( 'Add Other field?', 'wpcf7' ) ); ?></td></tr>
</table>

<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'wpcf7' ) ); ?><br /><input type="text" name="hierarchicalcheckboxes" class="tag" readonly="readonly" onfocus="this.select()" /></div>

<div class="tg-mail-tag"><?php echo esc_html( __( "And, put this code into the Mail fields below.", 'wpcf7' ) ); ?><br /><span class="arrow">&#11015;</span>&nbsp;<input type="text" class="mail-tag" readonly="readonly" onfocus="this.select()" /></div>
</form>
</div>
<?php
}

add_filter( 'wpcf7_validate_hierarchicalcheckboxes', 'wpcf7_hierarchical_checkboxes_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_hierarchicalcheckboxes*', 'wpcf7_hierarchical_checkboxes_validation_filter', 10, 2 );

function wpcf7_hierarchical_checkboxes_validation_filter( $result, $tag ) {		
	$tag = new WPCF7_Shortcode( $tag );
	
	$type = $tag->type;
	$name = $tag->name;

	$value = isset( $_POST[$name] ) ? (array) $_POST[$name] : array();	
	$text_value = $_POST[$name . "-text"];

	if ( 'hierarchicalcheckboxes*' == $type ) {
		if ( empty($value) && trim($text_value) == '' ) {		
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );		
		}
	}			
	
	return $result;
}

?>