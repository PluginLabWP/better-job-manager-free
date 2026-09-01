<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Company_Post_Type {
    public static function register() {
        register_post_type( 'bjm_company', array(
            'labels' => array(
                'name' => __( 'Companies', 'better-job-manager' ),
                'singular_name' => __( 'Company', 'better-job-manager' ),
            ),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => array( 'slug' => 'companies' ),
            'menu_icon' => 'dashicons-building',
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
            'capability_type' => array( 'bjm_company', 'bjm_companies' ),
            'map_meta_cap' => true,
        ) );
    }
}
