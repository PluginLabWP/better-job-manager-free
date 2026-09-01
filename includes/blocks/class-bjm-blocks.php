<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BJM_Blocks {
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_blocks' ) );
    }

    public static function register_blocks() {
        if ( ! function_exists( 'register_block_type' ) ) { return; }
        register_block_type( 'better-job-manager/jobs', array(
            'api_version' => 2,
            'render_callback' => array( __CLASS__, 'render_jobs' ),
            'attributes' => array(
                'perPage' => array( 'type' => 'number', 'default' => bjm_get_jobs_per_page( 12 ) ),
                'featuredOnly' => array( 'type' => 'boolean', 'default' => false ),
                'showFilters' => array( 'type' => 'boolean', 'default' => true ),
                'showExcerpt' => array( 'type' => 'boolean', 'default' => true ),
                'workMode' => array( 'type' => 'string', 'default' => '' ),
                'category' => array( 'type' => 'string', 'default' => '' ),
                'type' => array( 'type' => 'string', 'default' => '' ),
                'region' => array( 'type' => 'string', 'default' => '' ),
                'orderby' => array( 'type' => 'string', 'default' => 'date' ),
                'order' => array( 'type' => 'string', 'default' => 'DESC' ),
            ),
        ) );
        foreach ( array(
            'submit-job' => array( __CLASS__, 'render_submit_job' ),
            'employer-dashboard' => array( __CLASS__, 'render_employer_dashboard' ),
            'apply-form' => array( __CLASS__, 'render_apply_form' ),
            'company-dashboard' => array( __CLASS__, 'render_company_dashboard' ),
            'resume-dashboard' => array( __CLASS__, 'render_resume_dashboard' ),
            'candidate-dashboard' => array( __CLASS__, 'render_candidate_dashboard' ),
            'advertiser-register' => array( __CLASS__, 'render_advertiser_register' ),
        ) as $name => $callback ) {
            register_block_type( 'better-job-manager/' . $name, array( 'api_version' => 2, 'render_callback' => $callback, 'attributes' => array() ) );
        }
    }
    public static function render_jobs( $attributes = array() ) {
        $atts = array(
            'per_page' => max( 1, absint( $attributes['perPage'] ?? bjm_get_jobs_per_page( 12 ) ) ),
            'featured_only' => ! empty( $attributes['featuredOnly'] ) ? '1' : '0',
            'show_filters' => ! empty( $attributes['showFilters'] ) ? '1' : '0',
            'show_excerpt' => ! empty( $attributes['showExcerpt'] ) ? '1' : '0',
            'work_mode' => sanitize_key( $attributes['workMode'] ?? '' ),
            'category' => sanitize_title( $attributes['category'] ?? '' ),
            'type' => sanitize_title( $attributes['type'] ?? '' ),
            'region' => sanitize_title( $attributes['region'] ?? '' ),
            'orderby' => in_array( $attributes['orderby'] ?? 'date', array( 'date', 'title' ), true ) ? $attributes['orderby'] : 'date',
            'order' => strtoupper( $attributes['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC',
        );
        $pairs = array();
        foreach ( $atts as $key => $value ) { $pairs[] = sprintf( '%s="%s"', $key, esc_attr( $value ) ); }
        return do_shortcode( '[bjm_jobs ' . implode( ' ', $pairs ) . ']' );
    }
    public static function render_submit_job() { return do_shortcode( '[bjm_submit_job]' ); }
    public static function render_employer_dashboard() { return do_shortcode( '[bjm_employer_dashboard]' ); }
    public static function render_apply_form() { return do_shortcode( '[bjm_apply_form]' ); }
    public static function render_company_dashboard() { return do_shortcode( '[bjm_company_dashboard]' ); }
    public static function render_resume_dashboard() { return do_shortcode( '[bjm_resume_dashboard]' ); }
    public static function render_candidate_dashboard() { return do_shortcode( '[bjm_candidate_dashboard]' ); }
    public static function render_advertiser_register() { return do_shortcode( '[bjm_advertiser_register]' ); }
}
