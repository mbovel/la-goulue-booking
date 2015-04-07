<?php

/*
Plugin Name: La Goulue Booking
Description: Gère les locations de l'orgue.
Version: 0.2
Author: Matthieu Bovel

Copyright 2012 - Matthieu Bovel (matthieu@bovel.net)
*/

define( 'GOU_ORGAN_MAIN', __FILE__ );
define( 'GOU_ORGAN_DIR', __DIR__ );
define( 'GOU_ORGAN_REL_DIR', str_replace( '/' . basename( GOU_ORGAN_MAIN ), '', plugin_basename( GOU_ORGAN_MAIN ) ) );
define( 'GOU_ORGAN_URL', WP_PLUGIN_URL . '/' .  GOU_ORGAN_REL_DIR );
define( 'GOU_ORGAN_VERSION', '1' );

class Goulue_Organ {
    
    /**
     * Main function
     *
     * First function called. Add filters, actions, sortcodes, etc.
     *
     * @since 1
     * @access public
     */
    public function run() {
        add_shortcode( 'gou_organ',							array( &$this, 'shortcode' ) );
        
        add_action( 'wp_loaded',							array( &$this, 'register' ) );

        // Administration
        add_action( 'admin_menu',							array( &$this, 'add_admin_page' ) );
        add_action( 'admin_enqueue_scripts',				array( &$this, 'admin_enqueue' ) );
        add_action( 'admin_head',							array( &$this, 'menu_icon' ) );
        add_action( 'wp_ajax_update_gou_organ',				array( &$this, 'ajax' ) );
    }
    
    public function register() {
        wp_register_script( 'yesSelector', GOU_ORGAN_URL . '/js/yesSelector.js', array( 'jquery' ) );
        wp_register_style( 'gouOrganAdmin', GOU_ORGAN_URL . '/css/admin.css' );
    }
    
    
    /** Administration */
    
    public function add_admin_page() {
        add_menu_page( "Location de l'Orgue", 'Orgue', 'edit_posts', 'gou_organ', array( &$this, 'admin_page' ), false, 30 );
    }
    
    public function admin_page() {
        global $title;
        
        $year = isset( $_GET['year'] ) ? $_GET['year'] : date('Y');
        $calendar = $this->get_year( $year, false );
        
        include GOU_ORGAN_DIR . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'admin.phtml';
    }
    
    public function get_year( $y, $echo = true ) {
        global $wp_locale;
        
        $saved = get_option( "gou_organ_{$y}", false );
        
        $start_of_week = intval(get_option('start_of_week'));
        
        $today = new DateTime();
        
        $t = array(
            'd'		=> $today->format( 'j' ),
            'm'		=> $today->format( 'm' ),
            'y'		=> $today->format( 'y' )
        );
        
        $thead = "\t<thead>\n\t\t<tr>\n";
        
        for( $w = $start_of_week; $w < $start_of_week + 7; $w++)
            $thead .= "\t\t\t<th>" . $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( $w%7 ) ) . ".</th>\n";
        
        $thead .= "\t\t</tr>\n\t</thead>";
        
        $output = '';
        
        for( $m = 1; $m <= 12; $m++) {
        
            $month_name = $wp_locale->get_month( $m );
            
            $output .= "<table class='gou_month widefat'>\n\t<caption>\n\t\t<h3>{$month_name} {$y}</h3>\n\t</caption>\n{$thead}\n\t<tbody>\n\t\t<tr>\n";
            
            $first_day = new DateTime( $y . '/' . $m . '/1' );
            
            if( $m == 2 )
                $nd = 28 + $first_day->format( 'L' );
            else
                $nd = 30 + ( ( $m - 1 ) % 7 + 1 ) % 2;
            
            $pad = ( 7 + $first_day->format( 'w' ) - $start_of_week ) % 7;
            
            if( $pad !== 0 )
                $output .= "\t\t\t".'<td colspan="'. esc_attr( $pad ) .'" class="pad">&nbsp;</td>' . "\n";
            
            $newrow = false;
            
            for ( $d = 1; $d <= $nd; $d++ ) {
                if ( $newrow )
                    $output .= "\t\t</tr>\n\t\t<tr>\n";
                $newrow = false;
                
                if( $saved && isset( $saved[$m.'-'.$d] ) )
                    $class = $saved[$m.'-'.$d];
                else
                    $class = 'available';
        
                $today = '';
                
                if ( $y == $t['y'] && $m == $t['m'] && $d == $t['d'] )
                    $today = " today";
                    
                $output .= "\t\t\t<td id=\"{$m}-{$d}\" class=\"{$class}{$today}\">";

                $output .= "$d</td>\n";
        
                if ( 0 == ( $pad + $d )%7 )
                    $newrow = true;
            }
            
            $pad = ( 7 - ( $pad + $d - 1 ) % 7 ) % 7;
            
            if( $pad !== 0 )
                $output .= "\t\t\t".'<td colspan="'. esc_attr( $pad ) .'" class="pad">&nbsp;</td>' . "\n";
            
            $output .= "\t\t<tr>\n\t</tbody>\n</table>\n";
        }
        
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
        
        check_ajax_referer( 'gou_organ' );
        
        if( isset( $_POST['year'] ) ) {
            update_option( 'gou_organ_' . $_POST['year'], $_POST['rentals'] );
            die();
        }
        
        die('-1');
    }
        
    public function admin_enqueue() {
        global $current_screen;
        
        if( $current_screen->id == 'toplevel_page_gou_organ' ) {
            wp_enqueue_style( 'gouOrganAdmin' );
            wp_enqueue_script( 'yesSelector' );
        }
    }
    
    public function menu_icon() {
        $iconURL = GOU_ORGAN_URL . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'book-open-list.png';
        
        echo "<style type='text/css' media='screen'>
                 li.toplevel_page_gou_organ .wp-menu-image {
                     background: url({$iconURL}) no-repeat 7px -17px !important;
                 }
                 li.toplevel_page_gou_organ:hover .wp-menu-image {
                     background-position:7px 7px!important;
                 }
                 li.toplevel_page_gou_organ .wp-menu-image img {
                     display: none;
                 }
             </style>";
    }
}

$gou_organ = new Goulue_Organ;
$gou_organ->run();

?>