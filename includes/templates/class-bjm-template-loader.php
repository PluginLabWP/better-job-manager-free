<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Template_Loader {
    public static function init() {
        add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
    }
    public static function template_include( $template ) {
        if ( is_singular( 'job_listing' ) ) {
            $theme = locate_template( array( 'better-job-manager/single-job_listing.php' ) );
            return $theme ? $theme : BJM_PATH . 'templates/single-job_listing.php';
        }
        if ( is_post_type_archive( 'job_listing' ) ) {
            $theme = locate_template( array( 'better-job-manager/archive-job_listing.php' ) );
            return $theme ? $theme : BJM_PATH . 'templates/archive-job_listing.php';
        }
        if ( is_singular( 'bjm_company' ) ) {
            $theme = locate_template( array( 'better-job-manager/single-bjm_company.php' ) );
            return $theme ? $theme : BJM_PATH . 'templates/single-bjm_company.php';
        }
        return $template;
    }
}
