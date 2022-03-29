<?php

/**
 * The public-facing functionality of the plugin
 */
class TECH_SOAP_API_Public {
    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    private $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of the plugin.
     */
    private $version;

    public function __construct( $plugin_name, $plugin_version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $plugin_version;
    }

    /**
     * Include WC template override within plugin
     */
    public function template_override( $template, $template_name, $template_path ) {
        $plugin_path = plugin_dir_path( __FILE__ ) . $template_path . $template_name;

        return file_exists( $plugin_path ) ? $plugin_path : $template;
    }

    public function load_template_functions() {
        require_once plugin_dir_path( __FILE__ ) . 'class-ts-soap-api-template-functions.php';
    }

}