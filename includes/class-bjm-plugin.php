<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Plugin {
    public function run() {
        $this->load_dependencies();
        $this->register_hooks();
    }
    private function load_dependencies() {
        $files = array(
            'post-types/class-bjm-job-post-type.php',
            'post-types/class-bjm-company-post-type.php',
            'post-types/class-bjm-resume-post-type.php',
            'taxonomies/class-bjm-job-taxonomies.php',
            'meta/class-bjm-job-meta.php',
            'applications/class-bjm-applications-db.php',
            'stats/class-bjm-job-stats-db.php',
            'stats/class-bjm-job-stats.php',
            'applications/class-bjm-application-admin.php',
            'companies/class-bjm-company-admin.php',
            'forms/class-bjm-submit-job-form.php',
            'forms/class-bjm-apply-form.php',
            'forms/class-bjm-company-form.php',
            'forms/class-bjm-advertiser-registration.php',
            'forms/class-bjm-resume-form.php',
            'alerts/class-bjm-job-alerts.php',
            'candidates/class-bjm-candidate-dashboard.php',
            'employers/class-bjm-employer-dashboard.php',
            'admin/class-bjm-settings.php',
            'admin/class-bjm-job-metabox.php',
            'admin/class-bjm-tools.php',
            'admin/class-bjm-user-admin.php',
            'admin/class-bjm-onboarding.php',
            'ajax/class-bjm-ajax-listings.php',
            'templates/class-bjm-template-loader.php',
            'helpers/functions.php',
            'woocommerce/class-bjm-woocommerce.php',
            'blocks/class-bjm-blocks.php',
        );
        foreach ( $files as $file ) {
            require_once BJM_PATH . 'includes/' . $file;
        }
    }
    private function register_hooks() {
        add_action( 'init', array( 'BJM_Job_Post_Type', 'register' ) );
        add_action( 'init', array( 'BJM_Company_Post_Type', 'register' ) );
        add_action( 'init', array( 'BJM_Resume_Post_Type', 'register' ) );
        add_action( 'init', array( 'BJM_Job_Taxonomies', 'register' ) );
        add_action( 'init', array( 'BJM_Job_Meta', 'register' ) );
        add_action( 'init', array( 'BJM_Activator', 'add_caps' ) );
        add_action( 'admin_init', array( $this, 'maybe_upgrade_schema' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'bjm_daily_expiry_event', 'bjm_process_expired_jobs' );
        add_action( 'bjm_daily_alert_event', array( 'BJM_Job_Alerts', 'process_scheduled_alerts' ) );

        BJM_Submit_Job_Form::init();
        BJM_Apply_Form::init();
        BJM_Company_Form::init();
        BJM_Advertiser_Registration::init();
        BJM_Resume_Form::init();
        BJM_Job_Stats::init();
        BJM_Job_Alerts::init();
        BJM_Candidate_Dashboard::init();
        BJM_Employer_Dashboard::init();
        BJM_Settings::init();
        BJM_Job_Metabox::init();
        BJM_Application_Admin::init();
        BJM_Company_Admin::init();
        BJM_Tools::init();
        BJM_User_Admin::init();
        BJM_Onboarding::init();
        BJM_Ajax_Listings::init();
        BJM_Template_Loader::init();
        BJM_WooCommerce::init();
        BJM_Blocks::init();
    }

    public function maybe_upgrade_schema() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        BJM_Applications_DB::create_table();
        BJM_Job_Stats_DB::create_table();
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style( 'bjm-frontend', BJM_URL . 'assets/css/frontend.css', array(), BJM_VERSION );
        wp_enqueue_script( 'bjm-frontend', BJM_URL . 'assets/js/frontend.js', array( 'jquery' ), BJM_VERSION, true );
        wp_localize_script( 'bjm-frontend', 'bjmData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'bjm_nonce' ),
        ) );
    }
    public function enqueue_admin_assets( $hook ) {
        wp_enqueue_style( 'bjm-admin', BJM_URL . 'assets/css/admin.css', array(), BJM_VERSION );
        wp_enqueue_script( 'bjm-admin', BJM_URL . 'assets/js/admin.js', array( 'jquery' ), BJM_VERSION, true );
        if ( in_array( $hook, array( 'post.php', 'post-new.php', 'user-edit.php', 'profile.php' ), true ) ) {
            wp_enqueue_media();
        }
    }
}
