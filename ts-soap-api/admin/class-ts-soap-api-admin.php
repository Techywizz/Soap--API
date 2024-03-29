<?php

/**
 * The admin-facing functionality of the plugin
 */
class TECH_SOAP_API_Admin {

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
     * Add Product Gallery Slider settings to Woocommerce Product Settings page
     */
    public function add_admin_settings( $options ) {
        $slider_options = array(
            array(
                'title' => __( '3PL SOAP API CREATOR', 'woocommerce' ),
                'type'  => 'title',
                'desc'  => 'For Adding and Creating SOAP API XML Structure',
                'id'    => 'tech_soap_api_options',
            ),
            array(
                'title'    => __( 'Slides To Show', 'techsolitaire' ),
                'desc'     => __( 'Number of thumbnails to display per slide', 'techsolitaire' ),
                'id'       => 'tech_slides_to_show',
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => '3',
                'type'     => 'select',
                'options'  => array(
                    '3'   => __( '3', 'techsolitaire' ),
                    '4'   => __( '4', 'techsolitaire' ),
                    '5'   => __( '5', 'techsolitaire' ),
                ),
                'desc_tip' => true,
            ),
            array(
                'type' 	=> 'sectionend',
                'id' 	=> 'tech_soap_api_options'
            ),
        );

        return array_merge( $options, $slider_options );

    }
}