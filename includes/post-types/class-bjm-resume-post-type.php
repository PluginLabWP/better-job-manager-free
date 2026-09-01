<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Resume_Post_Type {
    public static function register() {
        $labels = array(
            'name'               => __( 'Resumes', 'better-job-manager' ),
            'singular_name'      => __( 'Resume', 'better-job-manager' ),
            'add_new_item'       => __( 'Add New Resume', 'better-job-manager' ),
            'edit_item'          => __( 'Edit Resume', 'better-job-manager' ),
            'new_item'           => __( 'New Resume', 'better-job-manager' ),
            'view_item'          => __( 'View Resume', 'better-job-manager' ),
            'search_items'       => __( 'Search Resumes', 'better-job-manager' ),
            'not_found'          => __( 'No resumes found', 'better-job-manager' ),
            'menu_name'          => __( 'Resumes', 'better-job-manager' ),
        );
        register_post_type( 'bjm_resume', array(
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'edit.php?post_type=job_listing',
            'show_in_rest'    => true,
            'supports'        => array( 'title', 'editor', 'author' ),
            'capability_type' => array( 'bjm_resume', 'bjm_resumes' ),
            'map_meta_cap'    => true,
            'menu_icon'       => 'dashicons-media-document',
        ) );
    }
}
