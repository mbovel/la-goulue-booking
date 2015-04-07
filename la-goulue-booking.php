<?php

/*
Plugin Name: La Goulue Booking
Description: Booking agenda for www.lagoulue.net.
Author: Matthieu Bovel
Author URI: http://matthieu.bovel.net
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.txt
Version: 0.4
*/

class Goulue_Booking {
	
	/**
	 * Main function
	 *
	 * First function called. Add filters, actions, sortcodes, etc.
	 *
	 * @since 1
	 * @access public
	 */
	public function run() {
		add_shortcode('gou_booking',                        [&$this, 'shortcode']);
		
		if(!is_admin()) return;
		
		add_action('wp_loaded',                             [&$this, 'register']);

		// Administration
		add_action('admin_menu',                            [&$this, 'add_admin_page']);
		add_action('admin_enqueue_scripts',                 [&$this, 'admin_enqueue']);
		add_action('wp_ajax_update_gou_booking',            [&$this, 'ajax']);
		add_action('wp_loaded',                             [&$this, 'register_post_types']);
		
		add_filter('acf/load_value/name=gou_booking_start', [&$this, 'post_edit_start_value'], 10, 3);
		add_filter('acf/load_value/name=gou_booking_end',   [&$this, 'post_edit_end_value'], 10, 3);
	}
	
	public function post_edit_start_value($value) {
		return isset($_GET['gou_booking_start']) ? $_GET['gou_booking_start'] : $value;
	}
	
	public function post_edit_end_value($value) {
		return isset($_GET['gou_booking_end']) ? $_GET['gou_booking_end'] : $value;
	}
	
	public function register() {
		wp_register_script('drag-select-js', plugins_url('js/drag-select.js', __FILE__), [], null);
		wp_register_style('gouOrganAdmin', plugins_url('css/admin.css', __FILE__), [], null);
	}
	
	public function add_admin_page() {
		add_submenu_page('edit.php?post_type=gou_reservation', "Location de l'Orgue", 'Grille', 'edit_posts', 'gou_booking_grid', [&$this, 'admin_page']);
	}
	
	public function admin_page() {
		global $title;
		
		$year = isset( $_GET['year'] ) ? $_GET['year'] : date('Y');
		$calendar = $this->get_year($year, false, true);
		
		require('html' . DIRECTORY_SEPARATOR . 'admin.phtml');
	}
	
	public function register_post_types() {
		register_post_type('gou_reservation', [
		    'label'					=> 'Réservations',
		    'public'				=> false,
		    'publicly_queryable'	=> false,
		    'show_ui'				=> true,
		    'has_archive'			=> false,
		    'hierarchical'			=> false,
		    'show_in_menu'			=> true,
		    'menu_icon'			    => 'dashicons-book-alt',
		    'menu_position'			=> 30
		]);
	}
	
	public function get_year($y, $echo = true, $edit_links = false) {
		global $wp_locale;
		
		$reservations = get_posts([
			'post_type'      => 'gou_reservation',
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'meta_value',
			'meta_type'      => 'DATE',
 			'meta_key'       => 'gou_booking_start',
			'meta_query'     => [
				'relation'      => 'AND',
				[
					'key'        => 'gou_booking_start',
					'value'      => $y + 1 . '-' . date('m'),
					'type'       => 'DATE',
					'compare'    => '<',
				],
				[
					'key'        => 'gou_booking_end',
					'value'      => $y . '-' . date('m'),
					'type'       => 'DATE',
					'compare'    => '>=',
				],
			]
		]);
		
		array_map(function($i) {
			$i->start = get_post_meta($i->ID, 'gou_booking_start', true);
			$i->end   = get_post_meta($i->ID, 'gou_booking_end', true);
			$i->state = get_post_meta($i->ID, 'gou_booking_state', true);
		}, $reservations);
		
		$reservation = current($reservations);
		
		$start_of_week = intval(get_option('start_of_week'));
		
		$thead = "<thead>\n<tr>\n";
		
		for($w = $start_of_week; $w < $start_of_week + 7; ++$w) {
			$thead .= "<th>" . $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( $w%7 ) ) . ".</th>\n";
		}
		
		$thead .= "</tr>\n</thead>";
		
		$output = '';
		
		$max_m = (int)date('n');
		$m = $max_m;
		
		do {
			$month_name = $wp_locale->get_month($m);
			
			$output .= "<table class='gou_month widefat'>\n<caption>\n<h3>{$month_name} {$y}</h3>\n</caption>\n{$thead}\n<tbody>\n<tr>\n";
			
			$first_day = new DateTime($y . '/' . $m . '/1');
			$max_d = 1 + $first_day->format('t');
			$pad = (7 + $first_day->format('w') - $start_of_week) % 7;
			
			if($pad !== 0)
				$output .= "".'<td colspan="'. esc_attr( $pad ) . '" class="pad">&nbsp;</td>' . "\n";
			
			$newrow = false;
			
			for($d = 1; $d !== $max_d; ++$d) {
				if($newrow) {
					$output .= "</tr>\n<tr>\n";
				}
				
				$newrow = false;
				
				$today = $y . '-' . str_pad($m, 2, 0, STR_PAD_LEFT) . '-' . str_pad($d, 2, 0, STR_PAD_LEFT);
				
				if($reservation !== false && $today >= $reservation->start) {
					$output .= "<td class=\"day {$reservation->state} disabled\" data-date=\"$today\">\n";
					
					if($edit_links) {
						$output .= "<a href=\"post.php?post={$reservation->ID}&action=edit\" title=\"{$reservation->post_title}\">";
					}
					
					$output .= $d;
					
					if($edit_links) {
						$output .= "</a>\n";
					}
					
					$output .="</td>\n";
					
					while($today >= $reservation->end) {
						$reservation = next($reservations);
					}
				}
				else {
					$output .= "<td class=\"day available\" data-date=\"$today\">$d</td>\n";
				}
		
				if(0 === ( $pad + $d ) % 7) {
					$newrow = true;
				}
			}
			
			$pad = ( 7 - ( $pad + $d - 1 ) % 7 ) % 7;
			
			if( $pad !== 0 )
				$output .= "".'<td colspan="'. esc_attr( $pad ) .'" class="pad">&nbsp;</td>' . "\n";
			
			$output .= "<tr>\n</tbody>\n</table>\n";
			
			++$m;
			
			if($m === 13) {
				$m = 1;
				++$y;
			}
			
		} while($m !== $max_m);
		
		if( $echo )
			echo $output;
		else
			return $output;
	}
	
	public function shortcode( $atts ) {
		extract( shortcode_atts( array(
			'year' => date('Y')
		), $atts ) );
		
		return $this->get_year( $year );
	}
	
	public function ajax() {
		if( !isset( $_POST['_wpnonce'] ) )
			die('-1');
		
		check_ajax_referer('gou_booking');
		
		if( isset( $_POST['year'] ) ) {
			update_option('gou_booking' . $_POST['year'], $_POST['rentals']);
			die();
		}
		
		die('-1');
	}
		
	public function admin_enqueue() {
		global $current_screen;
		
		if($current_screen->id == 'gou_reservation_page_gou_booking_grid') {
			wp_enqueue_style('gouOrganAdmin');
			wp_enqueue_script('drag-select-js');
		}
	}
}

$gou = new Goulue_Booking;
$gou->run();

?>