<?php

//ref: https://codex.wordpress.org/Creating_Options_Pages

define('NETTOP_SETTINGS_PAGE', 'nettop-plugin');

class NettopSettingsPage
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

            add_action('admin_footer', array( $this, 'addTabKeyToTextareas'));    
            //exit;
        }
        
        // Set class property
        $this->options = get_option( NETTOP_SETTINGS_PAGE );
        //var_dump($this->options);
    }

    function addJavascript(){ //in header
        ?>
        <script>alert( 'Hi Marmite' ); </script>
        <?php
      }
  

    public function addTabKeyToTextareas() {
        ?>
        <script>
            jQuery(document).delegate('textarea', 'keydown', function(e) {
            var keyCode = e.keyCode || e.which;
    
            if (keyCode == 9) {
                e.preventDefault();
                var start = this.selectionStart;
                var end = this.selectionEnd;
    
                // set textarea value to: text before caret + tab + text after caret
                jQuery(this).val(jQuery(this).val().substring(0, start)
                            + "\t"
                            + jQuery(this).val().substring(end));
    
                // put caret at right position again
                this.selectionStart =
                this.selectionEnd = start + 1;
            }
            });
        </script>
        <?php
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
            'Settings for NettOp plugin',
            array( $this, 'print_section_info' ), // Callback
            NETTOP_SETTINGS_PAGE // Page
        );  

        //NB td: maybe refactor to fall in line with the docs: add_settings_field can actually have args added in the 6th argument

        add_settings_field(
            'id_number', // ID
            'Id', 
            function() {echo self::addInputFieldString("id_number", "");},
            NETTOP_SETTINGS_PAGE, // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            NETTOP_DATA_googleFont, // ID
            'Google Fonts', 
            function() {echo self::addInputFieldString(NETTOP_DATA_googleFont, "Leave empty to use current theme default.<br>If set adds a 'font-family' css declaration to the header. NB: '" . NETTOP_FALLBACK_FONTS . "' are automatically added as fallbacks.");},
            NETTOP_SETTINGS_PAGE, // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            'css', 
            'CSS page', 
            function() {echo self::addInputTextField("css", "Type in custom CSS (it will be added in the header). NB! pageCss can override these settings.", 20 , 100);},
            NETTOP_SETTINGS_PAGE, 
            'setting_section_id'
        );      

        add_settings_field(
            'title', 
            'Title', 
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

        if( isset( $input['css'] ) )
            $new_input['css'] = sanitize_textarea_field( $input['css'] );

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

    public function addInputTextField($id, $txt, $rows, $columns) {
        printf(
            $txt . '<br><textarea rows="' . $rows . '" cols="' . $columns . '" id="' . $id . '" name="' . NETTOP_SETTINGS_PAGE . '[' . $id . ']" >%s</textarea>',
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

   