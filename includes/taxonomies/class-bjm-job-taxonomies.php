<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Job_Taxonomies {
    public static function register() {
        register_taxonomy( 'job_category', 'job_listing', array( 'label' => __( 'Job Categories', 'better-job-manager' ), 'public' => true, 'hierarchical' => true, 'show_in_rest' => true ) );
        register_taxonomy( 'job_type', 'job_listing', array( 'label' => __( 'Job Types', 'better-job-manager' ), 'public' => true, 'hierarchical' => false, 'show_in_rest' => true ) );
        register_taxonomy( 'job_location_region', 'job_listing', array( 'label' => __( 'Regions', 'better-job-manager' ), 'public' => true, 'hierarchical' => true, 'show_in_rest' => true ) );
        if ( ! get_option( 'bjm_seeded_default_categories' ) ) {
            self::seed_default_categories();
        }
    }
    public static function seed_default_categories() {
        $terms = array(
            'Accounting','Administration','Advertising & Marketing','Agriculture & Farming','Architecture','Automotive','Banking & Financial Services','Call Centre & Customer Service','Construction','Consulting & Strategy','Design & Creative','Education & Training','Engineering','Government & Defence','Healthcare & Medical','Hospitality & Tourism','Human Resources','Information & Communication Technology','Insurance & Superannuation','Legal','Logistics & Supply Chain','Manufacturing','Media & Communications','Mining, Resources & Energy','Real Estate & Property','Retail','Sales','Science & Technology','Sport & Recreation','Trades & Services','Transport & Logistics','Utilities & Infrastructure','Warehousing','Community Services & Development','Procurement','Project Management'
        );
        foreach ( $terms as $term_name ) {
            if ( ! term_exists( $term_name, 'job_category' ) ) {
                wp_insert_term( $term_name, 'job_category' );
            }
        }
        update_option( 'bjm_seeded_default_categories', 1 );
    }
}
