<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Advertiser_Registration {
    public static function init() {
        add_shortcode( 'bjm_advertiser_register', array( __CLASS__, 'render' ) );
        add_action( 'init', array( __CLASS__, 'handle_submission' ) );
    }

    public static function render() {
        if ( is_user_logged_in() ) {
            $company_url = bjm_get_page_url( 'company_dashboard_page_id' );
            $submit_url  = bjm_get_page_url( 'submit_page_id' );
            $message = 'You are already logged in.';
            if ( $company_url ) {
                $message .= ' <a href="' . esc_url( $company_url ) . '">Manage your company profile</a>.';
            }
            if ( $submit_url ) {
                $message .= ' <a href="' . esc_url( $submit_url ) . '">Post a job</a>.';
            }
            return '<p>' . $message . '</p>';
        }
        ob_start();
        echo bjm_render_notices();
        ?>
        <form method="post" class="bjm-submit-job-form bjm-advertiser-register-form bjm-form-card" enctype="multipart/form-data">
            <?php wp_nonce_field( 'bjm_advertiser_register', 'bjm_advertiser_register_nonce' ); ?>
            <h3>Create advertiser account</h3>
            <div class="bjm-grid bjm-grid-2">
                <p><label>Your Name</label><input type="text" name="user_display_name" required></p>
                <p><label>Email</label><input type="email" name="user_email" required></p>
                <p><label>Password</label><input type="password" name="user_password" required></p>
                <p><label>Confirm Password</label><input type="password" name="user_password_confirm" required></p>
            </div>
            <h3>Create company profile</h3>
            <p><label>Company Name</label><input type="text" name="company_title" required></p>
            <p><label>Company Description</label><textarea name="company_description" rows="6"></textarea></p>
            <div class="bjm-grid bjm-grid-3">
                <p><label>Website</label><input type="url" name="company_website"></p>
                <p><label>Company Email</label><input type="email" name="company_email"></p>
                <p><label>Location</label><input type="text" name="company_location"></p>
                <p><label>Company Logo</label><input type="file" name="company_logo" accept="image/*"></p>
                <p><label>Feature Image</label><input type="file" name="company_featured_image" accept="image/*"></p>
            </div>
            <p><button type="submit" class="button button-primary" name="bjm_advertiser_register_submit">Create advertiser account</button></p>
        </form>
        <?php
        return ob_get_clean();
    }

    public static function handle_submission() {
        if ( empty( $_POST['bjm_advertiser_register_submit'] ) ) { return; }
        if ( is_user_logged_in() ) { return; }
        if ( ! isset( $_POST['bjm_advertiser_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bjm_advertiser_register_nonce'] ) ), 'bjm_advertiser_register' ) ) { return; }

        $display_name = sanitize_text_field( wp_unslash( $_POST['user_display_name'] ?? '' ) );
        $email        = sanitize_email( wp_unslash( $_POST['user_email'] ?? '' ) );
        $password     = (string) ( $_POST['user_password'] ?? '' );
        $password2    = (string) ( $_POST['user_password_confirm'] ?? '' );
        $company_name = sanitize_text_field( wp_unslash( $_POST['company_title'] ?? '' ) );

        if ( ! $display_name || ! is_email( $email ) || ! $password || ! $company_name ) {
            bjm_add_notice( 'Please complete all required fields.', 'error' );
            return;
        }
        if ( email_exists( $email ) ) {
            bjm_add_notice( 'An account with that email already exists.', 'error' );
            return;
        }
        if ( $password !== $password2 ) {
            bjm_add_notice( 'Passwords do not match.', 'error' );
            return;
        }

        $username_base = sanitize_user( current( explode( '@', $email ) ), true );
        if ( ! $username_base ) { $username_base = 'advertiser'; }
        $username = $username_base;
        $i = 1;
        while ( username_exists( $username ) ) { $username = $username_base . $i; $i++; }

        $user_id = wp_insert_user( array(
            'user_login'   => $username,
            'user_pass'    => $password,
            'user_email'   => $email,
            'display_name' => $display_name,
            'role'         => 'employer',
        ) );
        if ( is_wp_error( $user_id ) || ! $user_id ) {
            bjm_add_notice( is_wp_error( $user_id ) ? $user_id->get_error_message() : 'Could not create advertiser account.', 'error' );
            return;
        }

        $company_id = wp_insert_post( array(
            'post_type'    => 'bjm_company',
            'post_title'   => $company_name,
            'post_content' => wp_kses_post( wp_unslash( $_POST['company_description'] ?? '' ) ),
            'post_status'  => 'publish',
            'post_author'  => $user_id,
        ), true );

        if ( ! is_wp_error( $company_id ) && $company_id ) {
            update_post_meta( $company_id, '_bjm_company_website', esc_url_raw( wp_unslash( $_POST['company_website'] ?? '' ) ) );
            update_post_meta( $company_id, '_bjm_company_email', sanitize_email( wp_unslash( $_POST['company_email'] ?? '' ) ) );
            update_post_meta( $company_id, '_bjm_company_location', sanitize_text_field( wp_unslash( $_POST['company_location'] ?? '' ) ) );
            update_user_meta( $user_id, '_bjm_default_company_id', (int) $company_id );
            if ( ! empty( $_FILES['company_logo']['name'] ) ) {
                $logo_id = bjm_upload_file_to_media( $_FILES['company_logo'], (int) $company_id );
                if ( $logo_id ) { update_post_meta( $company_id, '_bjm_company_logo_id', (int) $logo_id ); }
            }
            if ( ! empty( $_FILES['company_featured_image']['name'] ) ) {
                $featured_id = bjm_upload_file_to_media( $_FILES['company_featured_image'], (int) $company_id );
                if ( $featured_id ) { set_post_thumbnail( (int) $company_id, (int) $featured_id ); }
            }
        }

        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );
        do_action( 'wp_login', $username, get_user_by( 'id', $user_id ) );

        bjm_add_notice( 'Your advertiser account and company profile have been created.' );
        $redirect = bjm_get_page_url( 'company_dashboard_page_id' );
        if ( ! $redirect ) { $redirect = bjm_get_page_url( 'dashboard_page_id' ); }
        if ( ! $redirect ) { $redirect = bjm_get_page_url( 'submit_page_id' ); }
        if ( ! $redirect ) { $redirect = home_url( '/' ); }
        wp_safe_redirect( $redirect );
        exit;
    }
}
