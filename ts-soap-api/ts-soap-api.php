<?php
/**
 * Plugin Name: 3PL SOAP API
 * Description: A plugin to make 3PL SOAP API Structure.
 * Plugin URI: https://www.techsolitaire.com
 * Author: TechSolitaire
 * Author URI: https://www.techsolitaire.com
 * License: GPL2 or later
 * Text Domain: techsolitaire.com
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

//include main plugin file
require_once plugin_dir_path( __FILE__ ) . '/includes/class-ts-soap-api.php';

function ts_soap_api_run() {
    $instance = new TECH_SOAP_API();
    $instance->run();
}

ts_soap_api_run();
