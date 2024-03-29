<?php

defined( 'ABSPATH' ) || exit;

/**
 * Core Plugin Class
 */

class TECH_SOAP_API{

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The instance of the TECH_SOAP_API_Dependencies Class
     *
     * @since    1.0.0
     * @access   protected
     * @var      obj    $dependency_check    instance of the TECH_SOAP_API_Dependencies Class
     */
    protected $dependency_check;

    /**
     * The collection of action hooks to be registered
     *
     * @since    1.0.0
     * @access   protected
     * @var      array   $actions    The collection of action hooks to be registered
     */
    protected $actions;

    /**
     * The collection of filter hooks to be registered
     *
     * @since    1.0.0
     * @access   protected
     * @var      array   $filters    The collection of filter hooks to be registered
     */
    protected $filters;

    public function __construct() {
        $this->plugin_name = __( 'WooCommerce 3PL SOAP API Creator', 'techsolitaire' );
        $this->version = '1.0.0';
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Execute registered hooks
     *
     * @since   1.0.0
     * @access  public
     */
    public function run() {
        $this->load_classes();
        $this->create_instances();

        // Dependency checker. If required plugins are not active, the plugin will stop execution here.
        try {
            $this->dependency_check->check();
        } catch ( TECH_SOAP_API_Dependencies_Exception $e ) {
            $this->report_missing_dependencies( $e->get_missing_plugin_names() );
            return;
        }

        $this->define_public_hooks();
        $this->define_admin_hooks();
        $this->register_hooks();
    }

    /**
     * Require all necessary files
     *
     * @since   1.0.0
     * @access  private
     */
    private function load_classes() {
        require_once plugin_dir_path( __FILE__ ) . 'custom-exceptions/class-ts-soap-api-dependencies-exception.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-ts-soap-api-dependencies.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-ts-soap-api-reporter.php';
    }

    /**
     * Prepare instances of external classes
     *
     * @since   1.0.0
     * @access  private
     */
    private function create_instances() {
        $this->dependency_check = new TECH_SOAP_API_Dependencies();
    }

    /**
     * Define all public facing hooks
     *
     * @since   1.0.0
     * @access  private
     */
    private function define_public_hooks() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ts-soap-api-public.php';

        $plugin_public = new TECH_SOAP_API_Public( $this->plugin_name, $this->version );
        $this->actions = $this->add_hook_to_collection( $this->actions, 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->actions = $this->add_hook_to_collection( $this->actions, 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->actions = $this->add_hook_to_collection( $this->actions, 'plugins_loaded', $plugin_public, 'load_template_functions' );
        $this->filters = $this->add_hook_to_collection( $this->filters, 'woocommerce_locate_template', $plugin_public, 'template_override', 10, 3 );
        $this->filters = $this->add_hook_to_collection( $this->filters, 'woocommerce_single_product_photoswipe_enabled', $plugin_public, 'disable_photo_swipe' );
        $this->filters = $this->add_hook_to_collection( $this->filters, 'woocommerce_single_product_carousel_options', $plugin_public, 'product_carousel_options', 10, 2 );
    }

    /**
     * Define all admin facing hooks
     *
     * @since   1.0.0
     * @access  private
     */
    private function define_admin_hooks() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ts-soap-api-admin.php';

        $plugin_admin = new TECH_SOAP_API_Admin( $this->plugin_name, $this->version );
        $this->filters = $this->add_hook_to_collection( $this->filters, 'woocommerce_product_settings', $plugin_admin, 'add_admin_settings' );
    }

    /**
     * Register all hooks with WordPress
     *
     * @since   1.0.0
     * @access  private
     */
    private function register_hooks() {
        //register the collection of actions
        foreach ( $this->actions as $action_hook ) {
            add_action( $action_hook['hook'], array( $action_hook['component'], $action_hook['callback'] ), $action_hook['priority'], $action_hook['args'] );
        }
        //register the collection of filters
        foreach ( $this->filters as $filter_hook ) {
            add_filter( $filter_hook['hook'], array( $filter_hook['component'], $filter_hook['callback'] ), $filter_hook['priority'], $filter_hook['args'] );
        }
    }

    /**
     * Utility function to add all filters and actions into its respective collection.
     *
     * @since    1.0.0
     * @access   private
     * @param    array                $hooks            The collection of hooks that is being registered (that is, actions or filters).
     * @param    string               $hook             The name of the WordPress filter that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the filter is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         The priority at which the function should be fired.
     * @param    int                  $accepted_args    The number of arguments that should be passed to the $callback.
     * @return   array                                  The collection of actions and filters registered with WordPress
     */
    private function add_hook_to_collection( $hooks, $hook, $component, $callback, $priority = 10, $args = 1 ) {
        $hooks[] = array(
            'hook'      => $hook,
            'component' => $component,
            'callback'  => $callback,
            'priority'  => $priority,
            'args'      => $args,
        );

        return $hooks;
    }

    /**
     * Handles reporting of dependencies error
     */
    private function report_missing_dependencies( $missing_plugin_names ) {
        $missing_dependency_reporter = new TECH_SOAP_API_Reporter( $missing_plugin_names, $this->plugin_name );
        $missing_dependency_reporter->bind_to_admin_hooks();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_plugin_version() {
        return $this->version;
    }
}