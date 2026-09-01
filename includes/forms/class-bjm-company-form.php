<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Company_Form {
    public static function init() {
        add_shortcode( 'bjm_company_dashboard', array( __CLASS__, 'render' ) );
        add_action( 'init', array( __CLASS__, 'handle_submission' ) );
        add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_standalone_page' ) );
    }

    public static function maybe_redirect_standalone_page() {
        if ( is_admin() || ! is_user_logged_in() || ! is_page() ) { return; }
        $settings = get_option( 'bjm_settings', array() );
        $company_page_id = absint( $settings['company_dashboard_page_id'] ?? 0 );
        $dashboard_page_id = absint( $settings['dashboard_page_id'] ?? 0 );
        if ( ! $company_page_id || ! $dashboard_page_id ) { return; }
        if ( get_queried_object_id() !== $company_page_id ) { return; }
        $should_redirect = isset( $_GET['bjm_dashboard_redirect'] ) ? sanitize_text_field( wp_unslash( $_GET['bjm_dashboard_redirect'] ) ) : '';
        if ( '1' !== $should_redirect ) { return; }
        $target = add_query_arg( array( 'bjm_tab' => 'company' ), get_permalink( $dashboard_page_id ) );
        wp_safe_redirect( $target );
        exit;
    }

    public static function render_standalone_notice() {
        if ( ! is_user_logged_in() ) { return ''; }
        $settings = get_option( 'bjm_settings', array() );
        $company_page_id = absint( $settings['company_dashboard_page_id'] ?? 0 );
        $dashboard_page_id = absint( $settings['dashboard_page_id'] ?? 0 );
        if ( ! $company_page_id || ! $dashboard_page_id ) { return ''; }
        if ( ! is_page( $company_page_id ) ) { return ''; }
        $dashboard_url = add_query_arg( array( 'bjm_tab' => 'company' ), get_permalink( $dashboard_page_id ) );
        $redirect_url = add_query_arg( 'bjm_dashboard_redirect', '1', get_permalink( $company_page_id ) );
        $html  = '<div class="bjm-card bjm-company-compat-notice">';
        $html .= '<div class="bjm-card-header"><h3>' . esc_html__( 'Manage this in Employer Dashboard', 'better-job-manager' ) . '</h3></div>';
        $html .= '<div class="bjm-card-body">';
        $html .= '<p>' . esc_html__( 'This standalone company page still works for compatibility, but company settings now live inside the Employer Dashboard for a cleaner workflow.', 'better-job-manager' ) . '</p>';
        $html .= '<p class="bjm-inline-actions"><a class="button button-primary" href="' . esc_url( $dashboard_url ) . '">' . esc_html__( 'Open Company Profile tab', 'better-job-manager' ) . '</a> <a class="button" href="' . esc_url( $redirect_url ) . '">' . esc_html__( 'Always take me there now', 'better-job-manager' ) . '</a></p>';
        $html .= '</div></div>';
        return $html;
    }

    public static function render() {
        if ( ! is_user_logged_in() ) { return '<p>Please log in to manage your companies.</p>'; }
        $company_id = isset( $_GET['company_id'] ) ? absint( $_GET['company_id'] ) : 0;
        if ( $company_id && ! bjm_user_can_manage_company( $company_id ) ) { return '<p>You cannot edit this company.</p>'; }
        $company = $company_id ? get_post( $company_id ) : null;
        $logo_id = $company_id ? (int) get_post_meta( $company_id, '_bjm_company_logo_id', true ) : 0;
        $target_user_id = $company_id ? (int) get_post_field( 'post_author', $company_id ) : bjm_get_current_dashboard_advertiser_id();
        if ( ! $target_user_id ) { $target_user_id = get_current_user_id(); }
        $advertisers = bjm_is_agency_user() ? bjm_get_agency_advertiser_choices() : array();
        ob_start();
        echo bjm_render_notices();
        echo self::render_standalone_notice();
        ?>
        <form method="post" class="bjm-submit-job-form bjm-form-card" enctype="multipart/form-data">
            <?php wp_nonce_field( 'bjm_save_company', 'bjm_save_company_nonce' ); ?>
            <input type="hidden" name="company_id" value="<?php echo esc_attr( $company_id ); ?>">
            <input type="hidden" name="advertiser_user_id" value="<?php echo esc_attr( $target_user_id ); ?>">
            <?php if ( bjm_is_agency_user() ) : ?>
                <p><label>Advertiser Account</label><select name="agency_company_advertiser" onchange="if(this.value){window.location.href='?advertiser_id='+this.value+'#bjm-company-form';}"><?php foreach ( $advertisers as $advertiser ) : ?><option value="<?php echo esc_attr( $advertiser->ID ); ?>" <?php selected( $target_user_id, $advertiser->ID ); ?>><?php echo esc_html( $advertiser->display_name ); ?></option><?php endforeach; ?></select></p>
            <?php endif; ?>
            <p><label>Company Name</label><input type="text" name="company_title" required value="<?php echo esc_attr( $company ? $company->post_title : '' ); ?>"></p>
            <p><label>Description</label><textarea name="company_description" rows="6"><?php echo esc_textarea( $company ? $company->post_content : '' ); ?></textarea></p>
            <div class="bjm-grid bjm-grid-3">
                <p><label>Website</label><input type="url" name="company_website" value="<?php echo esc_attr( $company_id ? get_post_meta( $company_id, '_bjm_company_website', true ) : '' ); ?>"></p>
                <p><label>Email</label><input type="email" name="company_email" value="<?php echo esc_attr( $company_id ? get_post_meta( $company_id, '_bjm_company_email', true ) : '' ); ?>"></p>
                <p><label>Location</label><input type="text" name="company_location" value="<?php echo esc_attr( $company_id ? get_post_meta( $company_id, '_bjm_company_location', true ) : '' ); ?>"></p>
            </div>
            <p><label>Company Logo</label><input type="file" name="company_logo" accept="image/*"><?php if ( $logo_id ) : ?><br><img src="<?php echo esc_url( wp_get_attachment_image_url( $logo_id, 'thumbnail' ) ); ?>" style="max-width:90px;height:auto;margin-top:8px;" alt=""><?php endif; ?></p>
            <p><label>Feature Image</label><input type="file" name="company_featured_image" accept="image/*"><?php if ( $company_id && has_post_thumbnail( $company_id ) ) : ?><br><?php echo get_the_post_thumbnail( $company_id, 'thumbnail', array( 'style' => 'max-width:120px;height:auto;margin-top:8px;' ) ); ?><?php endif; ?></p>
            <p><button type="submit" class="button button-primary" name="bjm_save_company">Save Company</button></p>
        </form>
        <?php
        return ob_get_clean();
    }

    public static function handle_submission() {
        if ( empty( $_POST['bjm_save_company'] ) ) { return; }
        if ( ! is_user_logged_in() || ! isset( $_POST['bjm_save_company_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bjm_save_company_nonce'] ) ), 'bjm_save_company' ) ) { return; }
        $company_id = absint( $_POST['company_id'] ?? 0 );
        if ( $company_id && ! bjm_user_can_manage_company( $company_id ) ) { return; }
        $target_user_id = absint( $_POST['advertiser_user_id'] ?? get_current_user_id() );
        if ( bjm_is_agency_user() ) { if ( ! bjm_agency_can_manage_user( $target_user_id ) ) { return; } } else { $target_user_id = get_current_user_id(); }
        $data = array(
            'post_type' => 'bjm_company',
            'post_title' => sanitize_text_field( wp_unslash( $_POST['company_title'] ?? '' ) ),
            'post_content' => wp_kses_post( wp_unslash( $_POST['company_description'] ?? '' ) ),
            'post_status' => 'publish',
            'post_author' => $target_user_id,
        );
        if ( $company_id ) { $data['ID'] = $company_id; $company_id = wp_update_post( $data, true ); }
        else { $company_id = wp_insert_post( $data, true ); }
        if ( is_wp_error( $company_id ) || ! $company_id ) {
            bjm_add_notice( 'Could not save company.', 'error' );
        } else {
            update_post_meta( $company_id, '_bjm_company_website', esc_url_raw( wp_unslash( $_POST['company_website'] ?? '' ) ) );
            update_post_meta( $company_id, '_bjm_company_email', sanitize_email( wp_unslash( $_POST['company_email'] ?? '' ) ) );
            update_post_meta( $company_id, '_bjm_company_location', sanitize_text_field( wp_unslash( $_POST['company_location'] ?? '' ) ) );
            if ( ! get_user_meta( $target_user_id, '_bjm_default_company_id', true ) ) { update_user_meta( $target_user_id, '_bjm_default_company_id', (int) $company_id ); }
            if ( ! empty( $_FILES['company_logo']['name'] ) ) {
                $logo_id = bjm_upload_file_to_media( $_FILES['company_logo'], (int) $company_id );
                if ( $logo_id ) { update_post_meta( $company_id, '_bjm_company_logo_id', $logo_id ); }
            }
            if ( ! empty( $_FILES['company_featured_image']['name'] ) ) {
                $featured_id = bjm_upload_file_to_media( $_FILES['company_featured_image'], (int) $company_id );
                if ( $featured_id ) { set_post_thumbnail( (int) $company_id, (int) $featured_id ); }
            }
            bjm_add_notice( 'Company saved successfully.' );
        }
        wp_safe_redirect( add_query_arg( array( 'company_id' => (int) $company_id, 'advertiser_id' => $target_user_id ), wp_get_referer() ? wp_get_referer() : bjm_get_page_url( 'dashboard_page_id' ) ) );
        exit;
    }
}
