<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Job_Meta {
    public static function register() {
        $fields = array(
            '_bjm_company_name'             => 'string',
            '_bjm_company_logo_id'          => 'integer',
            '_bjm_company_id'               => 'integer',
            '_bjm_salary_min'               => 'number',
            '_bjm_salary_max'               => 'number',
            '_bjm_salary_period'            => 'string',
            '_bjm_location_text'            => 'string',
            '_bjm_work_mode'                => 'string',
            '_bjm_apply_method'             => 'string',
            '_bjm_apply_email'              => 'string',
            '_bjm_apply_url'                => 'string',
            '_bjm_expiry_date'              => 'string',
            '_bjm_closing_date'             => 'string',
            '_bjm_featured'                 => 'boolean',
            '_bjm_filled'                   => 'boolean',
            '_bjm_employer_user_id'         => 'integer',
            '_bjm_resume_required'          => 'boolean',
            '_bjm_archived'                 => 'boolean',
            '_bjm_paid_submission'          => 'boolean',
            '_bjm_application_deadline'     => 'string',
            '_bjm_deadline_closed_message'  => 'string',
            '_bjm_close_on_deadline'        => 'boolean',
        );
        foreach ( $fields as $key => $type ) {
            register_post_meta( 'job_listing', $key, array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => $type,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
                'sanitize_callback' => array( __CLASS__, 'sanitize_meta' ),
            ) );
        }
    }

    public static function sanitize_meta( $value ) {
        if ( is_array( $value ) ) {
            return array_map( 'sanitize_text_field', $value );
        }
        if ( is_bool( $value ) || is_numeric( $value ) ) {
            return $value;
        }
        return is_string( $value ) ? sanitize_text_field( $value ) : $value;
    }
}
