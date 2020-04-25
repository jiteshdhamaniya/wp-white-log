<?php
/**
 * Plugin Name: WP White Log
 * Description: Logs all login and logout events.
 * Version: 1.0
 * 
 * Author: Jitesh Dhamaniya
 * Author URI: http://twitter.com/jiteshdhamaniya
 */

 
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check for required PHP version
if ( version_compare( PHP_VERSION, '5.1', '<' ) )
{
    exit( sprintf( 'WP White Log requires PHP 5.1 or higher. You’re still on %s.', PHP_VERSION ) );
}

// Check for required PHP version
if ( version_compare( PHP_VERSION, '5.1', '<' ) )
{
    exit( sprintf( 'WP White Log requires PHP 5.1 or higher. You’re still on %s.', PHP_VERSION ) );
}

if( version_compare (get_bloginfo('version'),'5.0', '<') ) {
    exit( sprintf( 'WP White Log requires WordPress version 5.0 or higher. You’re still on %s.', get_bloginfo('version') ) );
}

if (!class_exists('WPLLOG')) : 

Class WPLLOG{

    protected 
        $log, 
        $ipaddress, 
        $base_dir, 
        $logfile, 
        $file_name,
        $setting_group,
        $upload_url;
   
    public 
        $page_title, 
        $menu_title, 
        $plugin_slug, 
        $plugin_hook,
        $plugin_domain;

    /**
     * 
     *  __construct
     */

    public function __construct()
    {   
        // lets set some variables 
        $this->file_name = "wpl.log";
        $this->page_title = "WPL Log";
        $this->menu_title = "WPL Log";
        $this->plugin_slug = "wpl-log";
        $this->plugin_domain = "wplog";
        $this->setting_group = "WPL_settings";
        $this->option_name = "logpath";

        $up_dir = wp_upload_dir();
        $this->base_dir = $up_dir['basedir'];
        $this->upload_url = $up_dir['baseurl'];

        // logging actions
        add_action( 'wp_login', [ $this, 'log' ], 10, 2 );
        add_action( 'wp_logout', [ $this, 'log' ], 10, 2 );

        // Add menu page 
        add_action( 'admin_menu', [ $this, 'menu_page' ] );
       
        // settings
        add_action( 'admin_init', [$this, 'WPL_settings'] );
        
        // admin_notices
        add_action( 'admin_notices', [ $this, 'add_admin_notices' ] );
  
    }
   
    /**
     *  @function Activate
     */
    public function activate(){

       exit ( wp_redirect( admin_url('admin.php?page=wpl-log') ) );

    }

    /**
     *  @return string $logfile
     */

   public function pre_file_path($file_path){
        $logfile = $this->base_dir;
        $logfile .= "/"; 
        return $logfile .= $file_path;
    }

    /**
     *  @return string 
     */

    public function file_path(){
           
        $logfile = $this->base_dir;
        $logfile .= "/"; 

        if(get_option( $this->option_name ) !=''){
            $logfile .= get_option( $this->option_name ). "/";
        }

        return $logfile.$this->file_name;

    }
    
    /**
     *  @return string $file_path
     */

    public function log_file_url(){
           
        $logfile = $this->upload_url;
        $logfile .= "/"; 

        if(get_option( $this->option_name ) !=''){
            $logfile .= get_option( $this->option_name ). "/";
        }

        return $logfile.$this->file_name;

    }

    /**
     *  @param $input
     *  @return string filtered $input
    */

    public function process_inputs( $input ){

        $input = str_replace("/"," ",$input);
        $input = sanitize_file_name($input);
        $input = str_replace("-","/",$input);
        
        if( empty($input) ){

            add_settings_error(
                'wplog-empty', 
                'wplog-empty', 
                __("Please fill in something before submitting."),
                'error'
            );

        }
       
        elseif( !is_dir( $this->pre_file_path($input) ) && !mkdir( $this->pre_file_path($input),0777,true ) ) {
            
            add_settings_error(
                'wplog-dir-error', 
                'wplog-dir-error', 
                __( 'Could not make Directory, Please make sure user have writing permission to wp-content/uploads folder. ' ), 
                'error'
            );
        }
        
        else{   
                $file_name = $this->pre_file_path($input)."/".$this->file_name;

                if(!file_exists($file_name)){
                          // Error Log
                        error_log("", 3, $file_name);
                    }
                
                add_settings_error(
                    'settings-updated', 
                    'settings-updated', 
                    __( 'Settings Updated!'), 
                    'updated' 
                );

                return $input;
            }
            
        
    }


    /**
    *  Register Settings
    * 
    **/
    public function WPL_settings() {
        register_setting ( $this->setting_group, $this->option_name, [$this, 'process_inputs'] ); 
    } 

    /**
     * Add Admin notices
     */

    public function add_admin_notices(){

         //get the current screen
         $screen = get_current_screen();
    
         $screen_id = "toplevel_page_".$this->plugin_slug;
     
         //return if not plugin settings page 
         if ( $screen->id !== $screen_id ) return;

        settings_errors( 'wplog-dir-error' );
        settings_errors( 'wplog-empty' );     
        settings_errors( 'settings-updated' );

    }


    /**
    *   Add Menu Page
    **/

    public function menu_page(){

        $this->plugin_hook = add_menu_page( 
            __( $this->page_title, $this->plugin_domain ),
            $this->menu_title,
            'manage_options',
            $this->plugin_slug,
            [ $this, 'form' ],
            'dashicons-media-default',
            3
        );
    }

    /**
        *   @function log
        *   @param $user_login @user
        *   @return error_log
    **/

    public function log($user_login=null,$user=null){

        if($user) {
            $log = "User Login";
            $log .= "\n";    
            $log .= "Login: " . $user->data->user_login;
            $log .= "\n";
            $log .= "Role: " . $user->roles[0];
            $log .= "\n";
            $log .= "User IP: " . $this->client_ip();
            $log .= "\n";
            $log .= "Date and Time: " . date('l jS \of F Y h:i:s A');
            $log .= "\n";
            $log .= "\n";
        }

        else {
            $log = "User Logout";
            $log .= "\n";
            $log .= "Date and Time: " . date('l jS \of F Y h:i:s A');
            $log .= "\n";
            $log .= "\n";
        }

        // Error Log
        error_log($log, 3, $this->file_path());
    }


    /**
        *   @function client_ip
        *   @return $ipaddress
    **/

    public function client_ip(){

        if (isset($_SERVER['HTTP_CLIENT_IP']))
        
                $this->ipaddress = $_SERVER['HTTP_CLIENT_IP'];
                
                    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
                
                 $this->ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];

                else if(isset($_SERVER['HTTP_X_FORWARDED']))
                
                 $this->ipaddress = $_SERVER['HTTP_X_FORWARDED'];
                
                else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
                
                 $this->ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
                
                else if(isset($_SERVER['HTTP_FORWARDED']))
                
                 $this->ipaddress = $_SERVER['HTTP_FORWARDED'];
                
                else if(isset($_SERVER['REMOTE_ADDR']))

                 $this->ipaddress = $_SERVER['REMOTE_ADDR'];
                
                else $this->ipaddress = 'UNKNOWN';
        
        return $this->ipaddress;

    }
 
    /**
        *   @function form
        *   @return $html
    **/    
    
    public function form(){
        // check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) { return; }
     ?>
        <h1> <?php _e($this->page_title , 'wplog'); ?> </h1>

        <form method="post" action="options.php">
            <?php settings_fields( $this->setting_group ) ?>
            <table>
            <tr>
                <td><h3><?php _e('Set directory of Log file folder.','wplog'); ?></h3>
                <p>
                <?php _e('You can choose any directory in wp-cotent/uploads folder. if folder does not exists already it will be created automatically. Space would be converted to recursive directory.','wplog'); ?>
                </p>
                </td>
            </tr>
            <tr>
                <td>
                    <input 
                        type="text" value="<?php echo get_option( $this->option_name ); ?>" size="50" 
                        name="<?php echo $this->option_name; ?>"
                    />
                    <?php submit_button(); ?>
                </td> 
            </tr>
           
            <!-- <tr>
                <td>
                <a target="_blank" href="<?php // echo $this->log_file_url(); ?>">
                    <?php // _e('Click here to read Log file','wplog'); ?>
                </a>
                </td> 
            </tr> -->

            <tr>
            <td>
            <p>You log file path is </p>
                <?php echo $this->file_path(); ?>
            </td> 
            </tr>
            </table>
        </form>

       <?php 

    } // form

}

endif; // Class

// Load Plugin
new WPLLOG;

/**
 * Activation Hook
 */

if(!function_exists('wpllog_register')){

    function wpllog_register() {
        $app = new WPLLOG;
        $app->activate();
    }
    
    add_action( 'activated_plugin' , 'wpllog_register' );
}

