<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Activator {
    public static function activate() {
        require_once BJM_PATH . 'includes/post-types/class-bjm-job-post-type.php';
        require_once BJM_PATH . 'includes/post-types/class-bjm-company-post-type.php';
        require_once BJM_PATH . 'includes/post-types/class-bjm-resume-post-type.php';
        require_once BJM_PATH . 'includes/taxonomies/class-bjm-job-taxonomies.php';
        require_once BJM_PATH . 'includes/applications/class-bjm-applications-db.php';
        require_once BJM_PATH . 'includes/stats/class-bjm-job-stats-db.php';
        BJM_Job_Post_Type::register();
        BJM_Company_Post_Type::register();
        BJM_Resume_Post_Type::register();
        BJM_Job_Taxonomies::register();
        BJM_Job_Taxonomies::seed_default_categories();
        BJM_Applications_DB::create_table();
        BJM_Job_Stats_DB::create_table();
        update_option( 'bjm_stats_schema_version', BJM_VERSION );
        add_role( 'employer', 'Employer', array(
            'read' => true,'upload_files' => true,'edit_job_listings' => true,'publish_job_listings' => true,
            'edit_bjm_companies' => true,'publish_bjm_companies' => true,'edit_bjm_resumes' => true,'publish_bjm_resumes' => true,
        ) );
        add_role( 'agency', 'Agency', array(
            'read' => true,'upload_files' => true,'edit_job_listings' => true,'publish_job_listings' => true,
            'edit_bjm_companies' => true,'publish_bjm_companies' => true,
        ) );
        self::add_caps();
        if ( ! wp_next_scheduled( 'bjm_daily_expiry_event' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'bjm_daily_expiry_event' );
        }
        if ( ! wp_next_scheduled( 'bjm_daily_alert_event' ) ) {
            wp_schedule_event( time() + 2 * HOUR_IN_SECONDS, 'daily', 'bjm_daily_alert_event' );
        }
        set_transient( 'bjm_activation_redirect', 1, 60 );
        flush_rewrite_rules();
    }
    public static function add_caps() {
        $roles = array( 'administrator', 'employer', 'agency' );
        $caps  = array(
            'manage_job_board', 'manage_all_jobs', 'manage_all_applications', 'manage_job_board_settings',
            'read_job_listing', 'edit_job_listing', 'delete_job_listing', 'edit_job_listings',
            'edit_others_job_listings', 'publish_job_listings', 'read_private_job_listings',
            'edit_bjm_company', 'read_bjm_company', 'delete_bjm_company', 'edit_bjm_companies',
            'edit_others_bjm_companies', 'publish_bjm_companies', 'read_private_bjm_companies',
            'edit_bjm_resume', 'read_bjm_resume', 'delete_bjm_resume', 'edit_bjm_resumes',
            'edit_others_bjm_resumes', 'publish_bjm_resumes', 'read_private_bjm_resumes'
        );
        foreach ( $roles as $role_name ) {
            $role = get_role( $role_name ); if ( ! $role ) { continue; }
            foreach ( $caps as $cap ) {
                if ( in_array( $role_name, array( 'employer', 'agency' ), true ) && in_array( $cap, array( 'manage_job_board', 'manage_all_jobs', 'manage_all_applications', 'manage_job_board_settings', 'edit_others_job_listings', 'read_private_job_listings', 'edit_others_bjm_companies', 'read_private_bjm_companies', 'edit_others_bjm_resumes', 'read_private_bjm_resumes' ), true ) ) { continue; }
                if ( 'agency' === $role_name && in_array( $cap, array( 'edit_bjm_resume', 'read_bjm_resume', 'delete_bjm_resume', 'edit_bjm_resumes', 'publish_bjm_resumes' ), true ) ) { continue; }
                $role->add_cap( $cap );
            }
        }
    }
}
