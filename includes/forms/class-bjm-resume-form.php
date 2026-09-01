<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Resume_Form {
    public static function init() {
        add_shortcode( 'bjm_resume_dashboard', array( __CLASS__, 'render' ) );
        add_action( 'init', array( __CLASS__, 'handle_submission' ) );
    }

    public static function render() {
        if ( ! is_user_logged_in() ) { return '<p>Please log in to manage your resume.</p>'; }
        $resume_id = isset( $_GET['resume_id'] ) ? absint( $_GET['resume_id'] ) : 0;
        if ( $resume_id && ! bjm_user_can_manage_resume( $resume_id ) ) { return '<p>You cannot edit this resume.</p>'; }
        $resume = $resume_id ? get_post( $resume_id ) : null;
        $resumes = bjm_get_resume_choices_for_user();
        $attachment_id = $resume_id ? (int) get_post_meta( $resume_id, '_bjm_resume_attachment_id', true ) : 0;
        $skills = $resume_id ? get_post_meta( $resume_id, '_bjm_resume_skills', true ) : '';
        $phone = $resume_id ? get_post_meta( $resume_id, '_bjm_resume_phone', true ) : '';
        $location = $resume_id ? get_post_meta( $resume_id, '_bjm_resume_location', true ) : '';
        $headline = $resume_id ? get_post_meta( $resume_id, '_bjm_resume_headline', true ) : '';
        $visibility = $resume_id ? get_post_meta( $resume_id, '_bjm_resume_visibility', true ) : 'private';
        ob_start();
        echo bjm_render_notices();
        echo '<div class="bjm-panel"><div class="bjm-panel-head"><h3>Your Resumes</h3></div>';
        if ( $resumes ) {
            echo '<ul class="bjm-company-list">';
            foreach ( $resumes as $item ) {
                echo '<li><strong>' . esc_html( $item->post_title ) . '</strong> <a href="' . esc_url( add_query_arg( array( 'resume_id' => $item->ID ), bjm_get_page_url( 'dashboard_page_id' ) ?: get_permalink() ) ) . '#bjm-resume-form">Edit</a>';
                if ( (int) get_user_meta( get_current_user_id(), '_bjm_default_resume_id', true ) === (int) $item->ID ) { echo ' <span class="bjm-pill">Default</span>'; }
                echo '</li>';
            }
            echo '</ul>';
        } else { echo '<p>No resumes yet. Add your first one below.</p>'; }
        echo '</div>';
        ?>
        <form id="bjm-resume-form" method="post" class="bjm-submit-job-form bjm-form-card" enctype="multipart/form-data">
            <?php wp_nonce_field( 'bjm_save_resume', 'bjm_save_resume_nonce' ); ?>
            <input type="hidden" name="resume_id" value="<?php echo esc_attr( $resume_id ); ?>">
            <p><label>Resume Title</label><input type="text" name="resume_title" required value="<?php echo esc_attr( $resume ? $resume->post_title : '' ); ?>"></p>
            <div class="bjm-grid bjm-grid-3">
                <p><label>Headline</label><input type="text" name="resume_headline" value="<?php echo esc_attr( $headline ); ?>"></p>
                <p><label>Phone</label><input type="text" name="resume_phone" value="<?php echo esc_attr( $phone ); ?>"></p>
                <p><label>Location</label><input type="text" name="resume_location" value="<?php echo esc_attr( $location ); ?>"></p>
            </div>
            <p><label>Professional Summary</label><textarea name="resume_summary" rows="6"><?php echo esc_textarea( $resume ? $resume->post_content : '' ); ?></textarea></p>
            <p><label>Skills</label><input type="text" name="resume_skills" value="<?php echo esc_attr( $skills ); ?>"><span class="description">Comma-separated, eg: Sales, CRM, Customer service</span></p>
            <div class="bjm-grid bjm-grid-2">
                <p><label>Visibility</label><select name="resume_visibility"><option value="private" <?php selected( $visibility, 'private' ); ?>>Private</option><option value="employers" <?php selected( $visibility, 'employers' ); ?>>Visible to employers only</option><option value="public" <?php selected( $visibility, 'public' ); ?>>Public</option></select></p>
                <p><label><input type="checkbox" name="resume_default" value="1" <?php checked( (int) get_user_meta( get_current_user_id(), '_bjm_default_resume_id', true ), $resume_id ); ?>> Use as my default resume for applications</label></p>
            </div>
            <p><label>Resume File</label><input type="file" name="resume_file" accept=".pdf,.doc,.docx,.rtf,.txt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"><?php if ( $attachment_id ) : ?><br><a href="<?php echo esc_url( wp_get_attachment_url( $attachment_id ) ); ?>">Download current file</a><?php endif; ?></p>
            <p><button type="submit" class="button button-primary" name="bjm_save_resume">Save Resume</button></p>
        </form>
        <?php return ob_get_clean();
    }

    public static function handle_submission() {
        if ( empty( $_POST['bjm_save_resume'] ) ) { return; }
        if ( ! is_user_logged_in() || ! isset( $_POST['bjm_save_resume_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bjm_save_resume_nonce'] ) ), 'bjm_save_resume' ) ) { return; }
        $resume_id = absint( $_POST['resume_id'] ?? 0 );
        if ( $resume_id && ! bjm_user_can_manage_resume( $resume_id ) ) { return; }
        $data = array(
            'post_type' => 'bjm_resume','post_title' => sanitize_text_field( wp_unslash( $_POST['resume_title'] ?? '' ) ),
            'post_content' => wp_kses_post( wp_unslash( $_POST['resume_summary'] ?? '' ) ),'post_status' => 'publish','post_author' => get_current_user_id(),
        );
        if ( $resume_id ) { $data['ID'] = $resume_id; $resume_id = wp_update_post( $data, true ); } else { $resume_id = wp_insert_post( $data, true ); }
        if ( is_wp_error( $resume_id ) || ! $resume_id ) { bjm_add_notice( 'Could not save resume.', 'error' ); wp_safe_redirect( wp_get_referer() ?: home_url( '/' ) ); exit; }
        update_post_meta( $resume_id, '_bjm_resume_headline', sanitize_text_field( wp_unslash( $_POST['resume_headline'] ?? '' ) ) );
        update_post_meta( $resume_id, '_bjm_resume_phone', sanitize_text_field( wp_unslash( $_POST['resume_phone'] ?? '' ) ) );
        update_post_meta( $resume_id, '_bjm_resume_location', sanitize_text_field( wp_unslash( $_POST['resume_location'] ?? '' ) ) );
        update_post_meta( $resume_id, '_bjm_resume_skills', sanitize_text_field( wp_unslash( $_POST['resume_skills'] ?? '' ) ) );
        update_post_meta( $resume_id, '_bjm_resume_visibility', sanitize_key( wp_unslash( $_POST['resume_visibility'] ?? 'private' ) ) );
        if ( ! empty( $_FILES['resume_file']['name'] ) ) {
            $attachment_id = bjm_upload_file_to_media( $_FILES['resume_file'], (int) $resume_id );
            if ( $attachment_id ) { update_post_meta( $resume_id, '_bjm_resume_attachment_id', $attachment_id ); }
        }
        if ( ! empty( $_POST['resume_default'] ) ) { update_user_meta( get_current_user_id(), '_bjm_default_resume_id', (int) $resume_id ); }
        elseif ( (int) get_user_meta( get_current_user_id(), '_bjm_default_resume_id', true ) === (int) $resume_id ) { delete_user_meta( get_current_user_id(), '_bjm_default_resume_id' ); }
        bjm_add_notice( 'Resume saved successfully.' );
        $redirect = bjm_get_page_url( 'dashboard_page_id' ); if ( ! $redirect ) { $redirect = wp_get_referer() ?: home_url( '/' ); }
        wp_safe_redirect( add_query_arg( 'resume_id', (int) $resume_id, $redirect ) . '#bjm-resume-form' ); exit;
    }
}
