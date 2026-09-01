<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Job_Metabox {
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'register' ) );
        add_action( 'save_post_job_listing', array( __CLASS__, 'save' ) );
    }
    public static function register() {
        add_meta_box( 'bjm_job_details', 'Job Details', array( __CLASS__, 'render' ), 'job_listing', 'normal', 'default' );
    }
    public static function render( $post ) {
        wp_nonce_field( 'bjm_job_metabox', 'bjm_job_metabox_nonce' );
        $logo_id = (int) get_post_meta( $post->ID, '_bjm_company_logo_id', true );
        $preview = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
        $company_id = (int) get_post_meta( $post->ID, '_bjm_company_id', true );
        $companies = get_posts( array( 'post_type' => 'bjm_company', 'posts_per_page' => 200, 'post_status' => array( 'publish', 'draft' ) ) );
        echo '<p><label>Company Name</label><input type="text" class="widefat" name="bjm_company_name" value="' . esc_attr( get_post_meta( $post->ID, '_bjm_company_name', true ) ) . '"></p>';
        echo '<p><label>Company Profile</label><select name="bjm_company_id" class="widefat"><option value="0">— None —</option>';
        foreach ( $companies as $company ) { echo '<option value="' . esc_attr( $company->ID ) . '" ' . selected( $company_id, $company->ID, false ) . '>' . esc_html( $company->post_title ) . '</option>'; }
        echo '</select></p>';
        echo '<p><label>Company Logo</label><input type="hidden" id="bjm_company_logo_id" name="bjm_company_logo_id" value="' . esc_attr( $logo_id ) . '"> <button type="button" class="button bjm-media-pick">Choose Logo</button></p>';
        if ( $preview ) {
            echo '<p><img src="' . esc_url( $preview ) . '" alt="" style="max-width:120px;height:auto;border:1px solid #ddd;padding:4px;background:#fff;"></p>';
        }
        echo '<p><label>Apply Email</label><input type="email" name="bjm_apply_email" value="' . esc_attr( get_post_meta( $post->ID, '_bjm_apply_email', true ) ) . '" class="widefat"></p>';
        echo '<p><label>External Application URL</label><input type="url" name="bjm_apply_url" value="' . esc_attr( get_post_meta( $post->ID, '_bjm_apply_url', true ) ) . '" class="widefat" placeholder="https://example.com/apply"></p>';
        echo '<p><label>Expiry Date</label><input type="date" name="bjm_expiry_date" value="' . esc_attr( get_post_meta( $post->ID, '_bjm_expiry_date', true ) ) . '"></p>';
        echo '<p><label>Application Deadline</label><input type="datetime-local" name="bjm_application_deadline" value="' . esc_attr( str_replace( ' ', 'T', (string) get_post_meta( $post->ID, '_bjm_application_deadline', true ) ) ) . '" class="widefat"></p>';
        echo '<p><label>Closed Message</label><input type="text" name="bjm_deadline_closed_message" value="' . esc_attr( get_post_meta( $post->ID, '_bjm_deadline_closed_message', true ) ) . '" class="widefat"></p>';
        echo '<p><label><input type="checkbox" name="bjm_close_on_deadline" value="1" ' . checked( get_post_meta( $post->ID, '_bjm_close_on_deadline', true ), 1, false ) . '> Mark listing closed when deadline passes</label></p>';
        echo '<p><label><input type="checkbox" name="bjm_featured" value="1" ' . checked( get_post_meta( $post->ID, '_bjm_featured', true ), 1, false ) . '> Featured</label></p>';
    }
    public static function save( $post_id ) {
        if ( ! isset( $_POST['bjm_job_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bjm_job_metabox_nonce'] ) ), 'bjm_job_metabox' ) ) { return; }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
        update_post_meta( $post_id, '_bjm_company_name', sanitize_text_field( wp_unslash( $_POST['bjm_company_name'] ?? '' ) ) );
        update_post_meta( $post_id, '_bjm_company_id', absint( $_POST['bjm_company_id'] ?? 0 ) );
        update_post_meta( $post_id, '_bjm_company_logo_id', absint( $_POST['bjm_company_logo_id'] ?? 0 ) );
        update_post_meta( $post_id, '_bjm_apply_email', sanitize_email( wp_unslash( $_POST['bjm_apply_email'] ?? '' ) ) );
        update_post_meta( $post_id, '_bjm_apply_url', esc_url_raw( wp_unslash( $_POST['bjm_apply_url'] ?? '' ) ) );
        update_post_meta( $post_id, '_bjm_expiry_date', sanitize_text_field( wp_unslash( $_POST['bjm_expiry_date'] ?? '' ) ) );
        $deadline_raw = sanitize_text_field( wp_unslash( $_POST['bjm_application_deadline'] ?? '' ) );
        update_post_meta( $post_id, '_bjm_application_deadline', $deadline_raw ? str_replace( 'T', ' ', $deadline_raw ) . ':00' : '' );
        update_post_meta( $post_id, '_bjm_deadline_closed_message', sanitize_text_field( wp_unslash( $_POST['bjm_deadline_closed_message'] ?? '' ) ) );
        update_post_meta( $post_id, '_bjm_close_on_deadline', empty( $_POST['bjm_close_on_deadline'] ) ? 0 : 1 );
        update_post_meta( $post_id, '_bjm_featured', empty( $_POST['bjm_featured'] ) ? 0 : 1 );
    }
}
