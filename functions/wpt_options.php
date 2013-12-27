<?php
class WPT_Settings
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
		$this->options = get_option( 'wp_theatre' );
		$this->admin_link = '<a href="options-general.php?page=theatre-admin">Settings</a>';
		
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		
   }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
		add_submenu_page( 'theatre', 'Theatre '.__('Settings'), __('Settings'), 'manage_options', 'theatre-admin', array( $this, 'create_admin_page' ));
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Theatre <?php echo __('Settings');?></h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'wp_theatre_group' );   
                do_settings_sections( 'theatre-admin' );
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
            'wp_theatre_group', // Option group
            'wp_theatre', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'display_section_id', // ID
            __('Display'), // Title
            array( $this, 'print_section_info' ), // Callback
            'theatre-admin' // Page
        );  

        add_settings_field(
            'lijstnummer', // ID
            __('Show events on production page.'), // Title 
            array( $this, 'show_events_callback' ), // Callback
            'theatre-admin', // Page
            'display_section_id' // Section           
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
        if( isset( $input['show_events'] ) )
            $new_input['show_events'] = $input['show_events'];

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
    }

    public function show_events_callback()
    {
        printf(
            '<input type="checkbox" id="show_events" name="wp_theatre[show_events]" value="yes" %s />',
    		(isset( $this->options['show_events'] ) && (esc_attr( $this->options['show_events'])=='yes')) ? 'checked="checked"' : ''
        );
    }

}

if( is_admin() )
    $wpt_settings = new WPT_Settings();