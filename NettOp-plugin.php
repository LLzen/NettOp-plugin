<?php
/*
Plugin Name: NettOp-plugin
Plugin URI: http://www.uis.no
Description: NettOp demo plugin

Adds 'NettOp settings' option to Dashboard->Settings
Options (see settings page for more info):
Google Fonts - load google font
CSS - site level css

Shortcodes:
NETTOP_getLinkIcons - see custom field 'NETTOP_getLinkIconTemplate'
NETTOP_getPreviousLink 
NETTOP_getNextLink
NETTOP_getConfigValue

Via Custom fields:
NETTOP_getLinkIconTemplate - combine with shortcode 'NETTOP_getLinkIcons'. This provides the template and should contain formatting around [link], [image] and [title]
NETTOP_pageCss - provides page level css


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

define('NETTOP_DATA_googleFont', 'googleFont');


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

    private static $nettop_settings_page;

    public function setup() {

        self::$nettop_settings_page = new NettopSettingsPage();

        add_shortcode(NETTOP_SHORTCODE . "getPreviousLink", array( $this, 'getPreviousLink'));
        add_shortcode(NETTOP_SHORTCODE . "getNextLink", array( $this, 'getNextLink'));
        add_shortcode(NETTOP_SHORTCODE . "getConfigValue", array( $this, 'getConfigValue'));
        add_shortcode(NETTOP_SHORTCODE . "getLinkIcons", array( $this, 'getLinkIcons'));

        //wordpress attempts to improve texts visually - which breaks the data for certain shortcodes, so we have to add some exemptions
        add_filter( 'no_texturize_shortcodes', array( $this, 'shortcodes_to_exempt_from_wptexturize'));
        
        //add_action('wp_head', array( $this, 'addJavascript'));
        if (is_null(self::getConfig(NETTOP_DATA_googleFont))==false) {
            add_action('wp_enqueue_scripts', array( $this, 'addGoogleFonts_loader'));
            add_action('wp_head', array( $this, 'addGoogleFonts_css'));
        }

        if (is_null(self::getConfig("css"))==false) {
            add_action('wp_head', array( $this, 'addplugincss'));
        }

        add_action('wp_head', array( $this, 'addpagecss'));

        


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


    //we want to stop wordpress from altering the data and text!
    function shortcodes_to_exempt_from_wptexturize( $shortcodes ) {
        $shortcodes[] = 'NETTOP_getLinkIcons';
        return $shortcodes;
    }
    

    //shortcode
    function getConfigValue($attsIn = [], $content = null, $tag) {
        //var_dump(self::$nettop_settings_page); //use to view the lot

        // normalize attribute keys, lowercase
        $attsIn = array_change_key_case((array)$attsIn, CASE_LOWER);
    
       // override default attributes with user attributes
        $atts = shortcode_atts([
                'id' => '',
            ], $attsIn, $tag);
        
        //var_dump($atts);
        
        //if ($atts["id"]=="") {$id="font_string";}
        if (empty($atts["id"])) {
            $ret="NettOp plugin error: getConfigValue called but no id set!";
        } else {
            //$ret=self::$nettop_settings_page->getOption($atts["id"]);
            $ret=self::getConfig($atts["id"]);
            if ($ret==null) {
                $ret="NettOp plugin error: config value '" . $atts["id"] . "' requested - but nothing by that name exists.";
            }
        }
        return $ret;
    }

    //called internally
    //nb! empty string is changed to null
    function getConfig($id) {
        switch ($id) {
            case "dummy":
                return "dummy";
            default:
                $ret=self::$nettop_settings_page->getOption($id);;
                if (empty($ret)) {$ret=null;}
                return $ret;
        }
    }

    function addGoogleFonts_loader() {
        wp_enqueue_style( 'UIS-google-fonts', 'http://fonts.googleapis.com/css?family=' . self::getConfig(NETTOP_DATA_googleFont), false, null );
    }
    
    function addGoogleFonts_css(){
        $fontToAdd=self::getConfig(NETTOP_DATA_googleFont);
      ?>
      <style type="text/css">
        body {font-family: <?php echo($fontToAdd . ", " . NETTOP_FALLBACK_FONTS);?>}
      </style>
      <?php
    }


    function addplugincss(){
        $css=self::getConfig("css");
      ?>
      <style type="text/css">
        <?php echo($css); ?>
      </style>
      <?php
    }   

    function addpagecss(){

        global $post;
        $css=get_post_meta($post->ID, NETTOP_SHORTCODE . 'pageCss', true);
        if ($css) {
            ?>
            <style type="text/css">
              <?php echo($css); ?>
            </style>
            <?php
        }
    }         

    function addJavascript(){ //in header
      ?>
      <script>alert( 'Hi Marmite' ); </script>
      <?php
    }

    function getNextLink( $atts, $content ) {
        $current = array_search(get_the_ID(), self::$pages);
        if ($current+1==count(self::$pages)) {
            return("");
        } else {
            return get_permalink(self::$pages[$current+1]);
        }
    }

    function getPreviousLink( $atts, $content ) {
        $current = array_search(get_the_ID(), self::$pages);
        if ($current==0) {
            return("");
        } else {
            return get_permalink(self::$pages[$current-1]);
        }
    }



    function getLinkIcons( $atts, $content ) {
        global $post;

        //defaults (lowercase!)
        $atts = shortcode_atts( array(
            'iconwidth' => '100%',
            'iconheight' => '100%',
        ), $atts );        

        $icons=self::get_shortcode_from_content($content, "icon", false);
        //var_dump($icons);

        //var_dump($atts["iconwidth"]);

        $template=get_post_meta($post->ID, NETTOP_SHORTCODE . 'getLinkIconTemplate', true);

        if ($template=="") {
            $ret="Error: shortcode '" . NETTOP_SHORTCODE . "getLinkIcons' being used - but not template has been defined!";
        } else {

            
            //var_dump($template);

            $ret="<div class=\"items_outer\" style=\"display: block;\">";
            foreach ($icons as $icon) {
                $build=$template;
                $text=$icon->atts["text"];
                $link=$icon->atts["link"];
                $image=$icon->atts["image"];
                
                
                if (substr($image, 0, 6)=="color:") {
                    $image=substr($image, 6);
                    
                    //see: http://png-pixel.com/
                    switch ($image) {
                        case "blue":
                            $image='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+H+1HgAFMAJVLPxbpAAAAABJRU5ErkJggg==';
                            break;
                        case "black":
                            $image='data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
                            break;
                        default:
                            $image="";
                    }
                }
                
                //var_dump($image);
                //if ($image==="") {$image='data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';}
                //if ($image==="") {$image='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+H+1HgAFMAJVLPxbpAAAAABJRU5ErkJggg==';}
                
                
                $build=str_replace("[text]",$text,$template);
                $build=str_replace("[link]",$link,$build);
                $build=str_replace("[image]",$image,$build);
                $build=str_replace("<img ","<img width=\"". $atts["iconwidth"] . "\" height=\"" . $atts["iconheight"] . "\" ",$build);
                $ret=$ret . $build;
                //var_dump($build);
                //echo("<br>");
            }
            $ret=$ret . "</div></div>";
        }
        return $ret;
    }

    //find specified shortcodes in content
    //return array of objects - finds shortcodes called $tag
    //td: parsePosh should now NOT be needed since adding shortcodes_to_exempt_from_wptexturize should fix the proper way!
    function get_shortcode_from_content( $content, $tag) {
        //$content=$post->post_content;
        $ret = array();
        $regex=get_shortcode_regex(array($tag)); //we only want to filter the supplied one
        //var_dump($regex);
        if ( preg_match_all( '/' . $regex . '/s', $content, $matches, PREG_SET_ORDER ) ) {
            //var_dump($matches);
            foreach ( $matches as $shortcode ) {
                if ( $shortcode[2] === $tag ) {
                    //$srcs = array();
                    //var_dump($shortcode[3]);
                    //echo(htmlspecialchars($shortcode[3]) . "<br>");
                    /*
                    if ($parsePosh) {
                        //need to find and replace the posh quotes with standard ones! 
                        $shortcode[3]=str_replace("=&#8221;","=\"",$shortcode[3]);
                        $shortcode[3]=str_replace("&#8221; ","\" ",$shortcode[3]);
                        //echo(htmlspecialchars(substr($shortcode[3], -7)) . "<br>");
                        if (substr($shortcode[3], -7)==="&#8221;") {
                            $shortcode[3]=substr($shortcode[3], 0, -7) . "\"";
                        };
                    }
                    */
                    //echo(htmlspecialchars($shortcode[3]) . "<br>");
                    $shortcode_attrs = shortcode_parse_atts( $shortcode[3] );
                    if ( ! is_array( $shortcode_attrs ) ) {
                        $shortcode_attrs = array();
                    }
                    //var_dump($shortcode_attrs);
                    //$ret[]=array($shortcode_attrs, $shortcode[5]);
    
                    $item = (object) [
                    'atts' => $shortcode_attrs,
                    'content' => $shortcode[5]
                    ];
    
                    $ret[]=$item;
                }
            }
        }
        return $ret;
    }
    
}

function nettop() {
	return nettop::instance();
}

add_action( 'init', 'nettop' );


