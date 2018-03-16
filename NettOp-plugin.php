<?php
/*
Plugin Name: NettOp-plugin
Plugin URI: http://www.uis.no
Description: NettOp demo plugin 
*/

require( dirname( __FILE__ ) . '/settings.php' ); //include the settings menu option

if ( ! defined( 'ABSPATH' ) ) exit; //check we are running in wordpress

define('NETTOP_VERSION', '0.01');

define('NETTOP_FALLBACK_FONTS', 'Roboto, Arial, Helvetica, sans-serif'); //reminder: used in settings.php

define('NETTOP_PATH', dirname(__FILE__));
define('NETTOP_URL', plugins_url('', __FILE__));
define('NETTOP_FILE', __FILE__);
define('NETTOP_PPATH', dirname(plugin_basename(__FILE__)));
define('NETTOP_PLUGIN_PATH', NETTOP_PATH . '/plugin');

define('NETTOP_SHORTCODE', 'NETTOP_'); //define the shortcode prefix




class nettop {
    private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new nettop;
			self::$instance->setup();
		}
		return self::$instance;
    }

    private static $pages=array();

    private static $my_settings_page;

    public function setup() {

        self::$my_settings_page = new MySettingsPage();

        add_shortcode(NETTOP_SHORTCODE . "getParentLink", array( $this, 'getParentLink'));
        add_shortcode(NETTOP_SHORTCODE . "getNextLink", array( $this, 'getNextLink'));
        add_shortcode(NETTOP_SHORTCODE . "getConfigValue", array( $this, 'getConfigValue'));
        add_action('wp_enqueue_scripts', array( $this, 'addGoogleFonts_loader'));
        add_action('wp_head', array( $this, 'addGoogleFonts_css'));

        //create a simple list of all pages - in the menu sequence
        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'menu_order,post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pagelist = get_pages($args);
        
        foreach ($pagelist as $page) {
            self::$pages[] += $page->ID;
        }
    }

    //called from a shortcode
    function getConfigValue($id) {
        //var_dump(self::$my_settings_page); //use to view the lot
        if ($id=="") {$id="font_string";}
        $ret=self::$my_settings_page->getOption($id);
        if ($ret==null) {
            $ret="NettOp plugin error: config value '" . $id . "' requested - but nothing by that name exists.";
        }
        return $ret;
    }

    //called internally
    function getConfig($id) {
        switch ($id) {
            case "googleFont":
                //return "Limelight";
                return "";
            default:
                return "";
        }
    }
    function addGoogleFonts_loader() {
        wp_enqueue_style( 'UIS-google-fonts', 'http://fonts.googleapis.com/css?family=' . self::getConfig("googleFont"), false );
    }
    
    function addGoogleFonts_css(){
        $fontToAdd=self::getConfig("googleFont");
      ?>
      <style type="text/css">
        body {font-family: <?php echo($fontToAdd);?>}
      </style>
      <?php
    }

        
/*
    function addJavascript(){ //in header
      ?>
      <script>alert( 'Hi Marmite' ); </script>
      <?php
    }
*/


    function getNextLink( $atts, $content ) {
        $current = array_search(get_the_ID(), self::$pages);
        if ($current+1==count(self::$pages)) {
            return("");
        } else {
            return get_permalink(self::$pages[$current+1]);
        }
    }

    function getParentLink( $atts, $content ) {
        $current = array_search(get_the_ID(), self::$pages);
        if ($current==0) {
            return("");
        } else {
            return get_permalink(self::$pages[$current-1]);
        }
    }
}

function nettop() {
	return nettop::instance();
}

add_action( 'init', 'nettop' );


