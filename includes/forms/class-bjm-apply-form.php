<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Apply_Form {
    public static function init() {
        add_shortcode( 'bjm_apply_form', array( __CLASS__, 'render' ) );
        add_action( 'init', array( __CLASS__, 'handle_submission' ) );
    }
    public static function render() {
        $job_id = get_the_ID();
        if ( ! $job_id ) { return ''; }

        bjm_maybe_close_job_on_deadline( $job_id );
        $deadline = (string) get_post_meta( $job_id, '_bjm_application_deadline', true );

        $default_resume_id = is_user_logged_in() ? (int) get_user_meta( get_current_user_id(), '_bjm_default_resume_id', true ) : 0;
        $resume_choices = is_user_logged_in() ? bjm_get_resume_choices_for_user() : array();
        ob_start();
        if ( isset( $_GET['bjm_applied'] ) ) { echo '<div class="bjm-notice success">Application sent.</div>'; }

        if ( bjm_is_job_deadline_closed( $job_id ) ) {
            echo '<div class="bjm-notice error">' . esc_html( bjm_get_job_closed_message( $job_id ) ) . '</div>';
            return ob_get_clean();
        }

        if ( $deadline ) {
            echo '<div class="bjm-meta-box"><strong>Applications close:</strong> ' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $deadline ) ) . '</div>';
        }
        if ( ! is_user_logged_in() ) {
            echo '<div class="bjm-meta-box">No account is required to apply. Want faster repeat applications later? <a href= . esc_url( wp_login_url( get_permalink( $job_id ) ) ) . >Log in</a> or create an account first.</div>';
        }
        ?>
        <form method="post" enctype="multipart/form-data" class="bjm-apply-form bjm-form-card">
            <?php wp_nonce_field( 'bjm_apply_job', 'bjm_apply_job_nonce' ); ?>
            <input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>">
            <p><label>Name</label><input type="text" name="applicant_name" required></p>
            <p><label>Email</label><input type="email" name="applicant_email" required></p>
            <p><label>Phone</label><input type="text" name="applicant_phone"></p>
            <p><label>Cover Letter</label><textarea name="cover_letter"></textarea></p>
            <?php if ( $resume_choices ) : ?>
            <p><label>Use saved resume</label><select name="saved_resume_id"><option value="0">Upload a new file instead</option><?php foreach ( $resume_choices as $resume_item ) : ?><option value="<?php echo esc_attr( $resume_item->ID ); ?>" <?php selected( $default_resume_id, $resume_item->ID ); ?>><?php echo esc_html( $resume_item->post_title ); ?></option><?php endforeach; ?></select></p>
            <?php endif; ?>
            <p><label>Resume</label><input type="file" name="resume"></p>
            <p><button type="submit" name="bjm_apply_job">Apply</button></p>
        </form>
        <?php
        return ob_get_clean();
    }
    public static function handle_submission() {
        if ( empty( $_POST['bjm_apply_job'] ) ) { return; }
        if ( ! isset( $_POST['bjm_apply_job_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bjm_apply_job_nonce'] ) ), 'bjm_apply_job' ) ) { return; }
        global $wpdb;
        $table = $wpdb->prefix . 'bjm_applications';
        $job_id = absint( $_POST['job_id'] ?? 0 );
        if ( ! $job_id ) { return; }

        if ( bjm_is_job_deadline_closed( $job_id ) ) {
            bjm_add_notice( bjm_get_job_closed_message( $job_id ), 'error' );
            wp_safe_redirect( get_permalink( $job_id ) );
            exit;
        }

        $resume_attachment_id = null;
        $saved_resume_id = absint( $_POST['saved_resume_id'] ?? 0 );
        if ( $saved_resume_id && is_user_logged_in() && bjm_user_can_manage_resume( $saved_resume_id ) ) {
            $resume_attachment_id = (int) get_post_meta( $saved_resume_id, '_bjm_resume_attachment_id', true );
        }
        if ( ! empty( $_FILES['resume']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $resume_attachment_id = media_handle_upload( 'resume', $job_id );
            if ( is_wp_error( $resume_attachment_id ) ) { $resume_attachment_id = null; }
        }
        if ( get_post_meta( $job_id, '_bjm_resume_required', true ) && empty( $resume_attachment_id ) ) {
            bjm_add_notice( 'A resume is required for this role.', 'error' );
            wp_safe_redirect( get_permalink( $job_id ) );
            exit;
        }
        $application = array(
            'job_id'               => $job_id,
            'employer_user_id'     => (int) get_post_meta( $job_id, '_bjm_employer_user_id', true ),
            'applicant_name'       => sanitize_text_field( wp_unslash( $_POST['applicant_name'] ?? '' ) ),
            'applicant_email'      => sanitize_email( wp_unslash( $_POST['applicant_email'] ?? '' ) ),
            'applicant_phone'      => sanitize_text_field( wp_unslash( $_POST['applicant_phone'] ?? '' ) ),
            'cover_letter'         => wp_kses_post( wp_unslash( $_POST['cover_letter'] ?? '' ) ),
            'resume_attachment_id' => $resume_attachment_id,
            'status'               => 'new',
            'status_changed_at'    => current_time( 'mysql' ),
            'deadline_snapshot'    => (string) get_post_meta( $job_id, '_bjm_application_deadline', true ),
            'source'               => is_user_logged_in() ? 'site-logged-in' : 'site-guest',
            'created_at'           => current_time( 'mysql' ),
            'updated_at'           => current_time( 'mysql' ),
        );
        $wpdb->insert( $table, $application, array( '%d','%d','%s','%s','%s','%s','%d','%s','%s','%s','%s','%s' ) );
        $application_id = (int) $wpdb->insert_id;
        if ( $application_id ) {
            bjm_add_application_note( $application_id, 'Application received.', 0 );
            if ( class_exists( 'BJM_Job_Stats' ) ) { BJM_Job_Stats::track_application( $job_id ); }
        }
        bjm_maybe_send_application_email( $job_id, $application );
        wp_safe_redirect( add_query_arg( 'bjm_applied', '1', get_permalink( $job_id ) ) ); exit;
    }
}
