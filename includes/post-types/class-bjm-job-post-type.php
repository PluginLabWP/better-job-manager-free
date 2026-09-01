<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Job_Post_Type {
    public static function register() {
        register_post_type( 'job_listing', array(
            'labels' => array(
                'name' => __( 'Jobs', 'better-job-manager' ),
                'singular_name' => __( 'Job', 'better-job-manager' ),
                'menu_name' => __( 'Jobs', 'better-job-manager' ),
            ),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => array( 'slug' => 'jobs' ),
            'menu_icon' => 'dashicons-id-alt',
            'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' ),
            'capability_type' => array( 'job_listing', 'job_listings' ),
            'map_meta_cap' => true,
        ) );
    }
}
