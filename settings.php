<?php

//ref: https://codex.wordpress.org/Creating_Options_Pages

define('NETTOP_SETTINGS_PAGE', 'nettop-plugin');

class MySettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {

        if( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
            add_action( 'admin_init', array( $this, 'page_init' ) );
            //exit;
        }

        // Set class property
        $this->options = get_option( NETTOP_SETTINGS_PAGE );
        //var_dump($this->options);
    }

    //externally called - to retrieve one of the config settings
    public function getOption($id) {
        if (isset($this->options[$id])) {
            return $this->options[$id];
        } else {
            return null;
        }
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'NettOp settings', 
            'manage_options', 
            NETTOP_SETTINGS_PAGE, 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        ?>
        <div class="wrap">
            <h1>Nettop</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'nettop_group' );
                do_settings_sections(NETTOP_SETTINGS_PAGE);
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'nettop_group', // Option group
            NETTOP_SETTINGS_PAGE, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Settings for NettOp plugin', // Title
            array( $this, 'print_section_info' ), // Callback
            NETTOP_SETTINGS_PAGE // Page
        );  

        //NB todo: maybe refactor to fall in line with the docs: add_settings_field can actually have args added in the 6th argument

        add_settings_field(
            'id_number', // ID
            'Id', // Title 
            function() {echo self::addInputFieldString("id_number", "");},
            NETTOP_SETTINGS_PAGE, // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            NETTOP_DATA_googleFont, // ID
            'Google Fonts', // Title 
            function() {echo self::addInputFieldString(NETTOP_DATA_googleFont, "Leave empty to use current theme default.<br>If set adds a 'font-family' css declaration to the header. NB: '" . NETTOP_FALLBACK_FONTS . "' are automatically added as fallbacks.");},
            NETTOP_SETTINGS_PAGE, // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            'title', 
            'Title', 
            //array( $this, 'title_callback' ), 
            function() {echo self::addInputFieldString("title", "");},
            NETTOP_SETTINGS_PAGE, 
            'setting_section_id'
        );      
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['id_number'] ) )
            $new_input['id_number'] = absint( $input['id_number'] );

        if( isset( $input[NETTOP_DATA_googleFont] ) )
            $new_input[NETTOP_DATA_googleFont] = sanitize_text_field( $input[NETTOP_DATA_googleFont] );

        if( isset( $input['title'] ) )
            $new_input['title'] = sanitize_text_field( $input['title'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your NettOp specific settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */

    public function addInputFieldString($id, $txt) {
        printf(
            '<input type="text" id="' . $id . '" name="' . NETTOP_SETTINGS_PAGE . '[' . $id . ']" value="%s" /> ' . $txt,
            isset( $this->options[$id] ) ? esc_attr( $this->options[$id]) : ''
        );
    }



    public function id_number_callback()
    {
        printf(
            '<input type="text" id="id_number" name="' . NETTOP_SETTINGS_PAGE . '[id_number]" value="%s" />',
            isset( $this->options['id_number'] ) ? esc_attr( $this->options['id_number']) : ''
        );
    }


}

   