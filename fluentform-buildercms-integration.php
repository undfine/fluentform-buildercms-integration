<?php
/**
 * Plugin Name: Fluent Forms BuilderCMS Integration
 * Description: Used to import and sync contacts with BuilderCMS CRM. Supports both modern REST API and legacy ProspectImport endpoints.
 * Version:     1.3.1
 * Author:      Dustin Wight
 * Author URI: https://github.com/undfine/fluentform-builder_cms-integration
 * Text Domain: ff_builder_cms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action('init', function (){

    if (!defined('FLUENTFORM') ) {
        add_action( 'admin_notices', function(){
            
            $message = esc_html__( 'FluentForm BuilderCMS Integration Add-On Requires FluentForm', 'ff_builder_cms' );
            echo '<div class="notice notice-success is-dismissible"><p>'.$message.'</p></div>';
            
        });
        return;
    }
    
    // Check that class doesn't already exist
    if( !class_exists('FF_BuilderCMS')){

        require plugin_dir_path( __FILE__ ) . 'includes/BuilderCMS.php';
        require plugin_dir_path( __FILE__ ) . 'includes/BuilderCMS_API.php';
        
        $plugin = new FF_BuilderCMS();
    
    } else {
        add_action( 'admin_notices', function(){
            
            $message = esc_html__( 'The FF BuilderCMS Class is already in use, please deactivate or uninstall the conflicting plugin', 'ff_builder_cms' );
            echo '<div class="notice notice-success is-dismissible"><p>'.$message.'</p></div>';
            
        });
    }
});
