<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Submit_Job_Form {
    public static function init() {
        add_shortcode( 'bjm_submit_job', array( __CLASS__, 'render' ) );
        add_action( 'init', array( __CLASS__, 'handle_submission' ) );
    }

    public static function render() {
        if ( ! is_user_logged_in() ) {
            $login_url    = wp_login_url( add_query_arg( array(), home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ?? '' ) ) ) );
            $register_url = bjm_get_page_url( 'advertiser_register_page_id' );
            $message = 'Please <a href="' . esc_url( $login_url ) . '">log in</a> to submit a job.';
            if ( $register_url ) { $message .= ' New advertisers can <a href="' . esc_url( $register_url ) . '">create an account</a> first.'; }
            return '<p>' . $message . '</p>';
        }

        $job_id = isset( $_GET['job_id'] ) ? absint( $_GET['job_id'] ) : 0;
        if ( $job_id && ! bjm_is_job_owner( $job_id ) && ! current_user_can( 'manage_all_jobs' ) ) { return '<p>You cannot edit this job.</p>'; }

        $job            = $job_id ? get_post( $job_id ) : null;
        $target_user_id = $job_id ? (int) get_post_meta( $job_id, '_bjm_employer_user_id', true ) : bjm_get_current_dashboard_advertiser_id();
        if ( ! $target_user_id ) { $target_user_id = get_current_user_id(); }
        $company_id          = $job_id ? (int) get_post_meta( $job_id, '_bjm_company_id', true ) : (int) get_user_meta( $target_user_id, '_bjm_default_company_id', true );
        $companies           = bjm_get_company_choices_for_user( $target_user_id );
        $credits             = bjm_get_user_package_credits( $target_user_id );
        $advertisers         = bjm_is_agency_user() ? bjm_get_agency_advertiser_choices() : array();
        $selected_categories = $job_id ? wp_get_post_terms( $job_id, 'job_category', array( 'fields' => 'ids' ) ) : array();
        $available_categories = bjm_get_available_job_categories();

        if ( empty( $companies ) && ! $job_id ) {
            $company_url = bjm_get_page_url( 'company_dashboard_page_id' );
            if ( ! $company_url ) { $company_url = bjm_get_page_url( 'dashboard_page_id' ); }
            return '<p>You need to create your company profile before posting a job.' . ( $company_url ? ' <a href="' . esc_url( $company_url ) . '">Create your company profile</a>.' : '' ) . '</p>';
        }

        ob_start();
        echo bjm_render_notices();
        ?>
        <div class="bjm-meta-strip">
            <div class="bjm-meta-box"><strong>Package credits:</strong> <?php echo esc_html( $credits ); ?></div>
            <?php if ( bjm_get_setting( 'enable_paid_listings', 0 ) ) : ?><div class="bjm-meta-box"><strong>Paid listings:</strong> enabled</div><?php endif; ?>
        </div>
        <form method="post" class="bjm-submit-job-form bjm-form-card" enctype="multipart/form-data">
            <?php wp_nonce_field( 'bjm_submit_job', 'bjm_submit_job_nonce' ); ?>
            <input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>">
            <input type="hidden" name="advertiser_user_id" value="<?php echo esc_attr( $target_user_id ); ?>">
            <?php if ( bjm_is_agency_user() ) : ?>
                <p><label>Advertiser Account</label><select name="agency_advertiser_view" onchange="if(this.value){window.location.href='?advertiser_id='+this.value;}"><?php foreach ( $advertisers as $advertiser ) : ?><option value="<?php echo esc_attr( $advertiser->ID ); ?>" <?php selected( $target_user_id, $advertiser->ID ); ?>><?php echo esc_html( $advertiser->display_name ); ?></option><?php endforeach; ?></select></p>
            <?php endif; ?>
            <div class="bjm-grid bjm-grid-2">
                <p><label>Job Title</label><input type="text" name="job_title" required value="<?php echo esc_attr( $job ? $job->post_title : '' ); ?>"></p>
                <p><label>Company Profile</label><select name="company_id" required><option value="0">— Select —</option><?php foreach ( $companies as $company ) : ?><option value="<?php echo esc_attr( $company->ID ); ?>" <?php selected( $company_id ? $company_id : ( count( $companies ) === 1 ? $companies[0]->ID : 0 ), $company->ID ); ?>><?php echo esc_html( $company->post_title ); ?></option><?php endforeach; ?></select></p>
            </div>
            <p><label>Description</label><textarea name="job_description" required rows="8"><?php echo esc_textarea( $job ? $job->post_content : '' ); ?></textarea></p>
            <div class="bjm-grid bjm-grid-2">
                <p><label>Industry / Category</label><select name="job_category_id"><option value="0">— Select —</option><?php foreach ( $available_categories as $term ) : ?><option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( ! empty( $selected_categories[0] ) ? (int) $selected_categories[0] : 0, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option><?php endforeach; ?></select></p>
                <p><label>Location</label><input type="text" name="location_text" value="<?php echo esc_attr( $job_id ? get_post_meta( $job_id, '_bjm_location_text', true ) : '' ); ?>"></p>
                <p><label>Work Mode</label><select name="work_mode"><?php foreach ( array( 'onsite' => 'Onsite', 'hybrid' => 'Hybrid', 'remote' => 'Remote' ) as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $job_id ? get_post_meta( $job_id, '_bjm_work_mode', true ) : 'onsite', $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></p>
                <p><label>Salary Min</label><input type="number" name="salary_min" value="<?php echo esc_attr( $job_id ? get_post_meta( $job_id, '_bjm_salary_min', true ) : '' ); ?>"></p>
                <p><label>Salary Max</label><input type="number" name="salary_max" value="<?php echo esc_attr( $job_id ? get_post_meta( $job_id, '_bjm_salary_max', true ) : '' ); ?>"></p>
                <p><label>Apply Email</label><input type="email" name="apply_email" value="<?php echo esc_attr( $job_id ? get_post_meta( $job_id, '_bjm_apply_email', true ) : '' ); ?>"><small>Leave blank to use the company profile email.</small></p>
                <p><label>External Application URL</label><input type="url" name="apply_url" placeholder="https://example.com/apply" value="<?php echo esc_attr( $job_id ? get_post_meta( $job_id, '_bjm_apply_url', true ) : '' ); ?>"></p>
                <p><label>Expiry Date</label><input type="date" name="expiry_date" value="<?php echo esc_attr( $job_id ? get_post_meta( $job_id, '_bjm_expiry_date', true ) : '' ); ?>"></p>
                <p><label>Application Deadline</label><input type="datetime-local" name="application_deadline" value="<?php echo esc_attr( $job_id ? str_replace( ' ', 'T', (string) get_post_meta( $job_id, '_bjm_application_deadline', true ) ) : '' ); ?>"></p>
                <p><label>Closed Message</label><input type="text" name="deadline_closed_message" value="<?php echo esc_attr( $job_id ? get_post_meta( $job_id, '_bjm_deadline_closed_message', true ) : 'Applications for this job are now closed.' ); ?>"></p>
            </div>
            <div class="bjm-checks">
                <label><input type="checkbox" name="featured" value="1" <?php checked( $job_id ? get_post_meta( $job_id, '_bjm_featured', true ) : 0, 1 ); ?>> Featured Job</label>
                <label><input type="checkbox" name="resume_required" value="1" <?php checked( $job_id ? get_post_meta( $job_id, '_bjm_resume_required', true ) : 0, 1 ); ?>> Resume Required</label>
                <label><input type="checkbox" name="close_on_deadline" value="1" <?php checked( $job_id ? get_post_meta( $job_id, '_bjm_close_on_deadline', true ) : 0, 1 ); ?>> Mark closed when deadline passes</label>
            </div>
            <p><button type="submit" class="button button-primary" name="bjm_submit_job">Save Job</button></p>
        </form>
        <?php return ob_get_clean();
    }

    public static function handle_submission() {
        if ( empty( $_POST['bjm_submit_job'] ) ) { return; }
        if ( ! isset( $_POST['bjm_submit_job_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bjm_submit_job_nonce'] ) ), 'bjm_submit_job' ) ) { return; }
        if ( ! is_user_logged_in() ) { return; }
        $job_id  = isset( $_POST['job_id'] ) ? absint( $_POST['job_id'] ) : 0;
        $is_edit = $job_id > 0;
        if ( $is_edit && ! bjm_is_job_owner( $job_id ) && ! current_user_can( 'manage_all_jobs' ) ) { return; }
        $payload = self::get_submission_payload();
        if ( is_wp_error( $payload ) ) { bjm_add_notice( $payload->get_error_message(), 'error' ); wp_safe_redirect( wp_get_referer() ? wp_get_referer() : bjm_get_page_url( 'submit_page_id' ) ); exit; }
        if ( ! $is_edit && bjm_get_setting( 'enable_paid_listings', 0 ) ) {
            if ( bjm_get_user_package_credits( $payload['employer_user_id'] ) > 0 ) {
                bjm_consume_user_package_credit( $payload['employer_user_id'] );
                $saved_id = self::save_submission( $payload, get_current_user_id(), 0, 'publish', true );
                bjm_add_notice( 'Job published using one package credit.' );
                wp_safe_redirect( add_query_arg( 'job_id', $saved_id, bjm_get_page_url( 'submit_page_id' ) ) ); exit;
            }
            if ( apply_filters( 'bjm_job_submission_requires_payment', false, get_current_user_id(), $job_id ) ) {
                $checkout_url = BJM_WooCommerce::queue_submission_for_checkout( $payload, get_current_user_id() );
                if ( is_wp_error( $checkout_url ) ) { bjm_add_notice( $checkout_url->get_error_message(), 'error' ); wp_safe_redirect( bjm_get_page_url( 'submit_page_id' ) ); exit; }
                bjm_add_notice( 'Complete checkout to publish your listing.' ); wp_safe_redirect( $checkout_url ); exit;
            }
        }
        $saved_id = self::save_submission( $payload, get_current_user_id(), $job_id, '', false );
        if ( $saved_id && ! is_wp_error( $saved_id ) ) { bjm_add_notice( 'Job saved successfully.' ); wp_safe_redirect( add_query_arg( 'job_id', $saved_id, bjm_get_page_url( 'submit_page_id' ) ) ); exit; }
    }

    public static function get_submission_payload() {
        $job_title       = sanitize_text_field( wp_unslash( $_POST['job_title'] ?? '' ) );
        $job_description = wp_kses_post( wp_unslash( $_POST['job_description'] ?? '' ) );
        if ( '' === $job_title || '' === trim( wp_strip_all_tags( $job_description ) ) ) { return new WP_Error( 'bjm_missing_fields', __( 'Job title and description are required.', 'better-job-manager' ) ); }
        $company_id = absint( $_POST['company_id'] ?? 0 );
        if ( ! $company_id ) { return new WP_Error( 'bjm_missing_company', __( 'Please choose a company profile for this job.', 'better-job-manager' ) ); }
        if ( ! bjm_user_can_manage_company( $company_id ) && ! current_user_can( 'edit_others_bjm_companies' ) ) { return new WP_Error( 'bjm_invalid_company', __( 'You cannot use that company profile.', 'better-job-manager' ) ); }
        $deadline_raw = sanitize_text_field( wp_unslash( $_POST['application_deadline'] ?? '' ) );
        $deadline     = $deadline_raw ? str_replace( 'T', ' ', $deadline_raw ) . ':00' : '';
        $company_title = get_the_title( $company_id );
        $company_logo_id = (int) get_post_meta( $company_id, '_bjm_company_logo_id', true );
        $company_email = sanitize_email( (string) get_post_meta( $company_id, '_bjm_company_email', true ) );
        return array(
            'post_title' => $job_title,'post_content' => $job_description,'company_name' => $company_title,'company_id' => $company_id,
            'location_text' => sanitize_text_field( wp_unslash( $_POST['location_text'] ?? '' ) ),'work_mode' => sanitize_text_field( wp_unslash( $_POST['work_mode'] ?? 'onsite' ) ),
            'salary_min' => floatval( $_POST['salary_min'] ?? 0 ),'salary_max' => floatval( $_POST['salary_max'] ?? 0 ),
            'apply_email' => sanitize_email( wp_unslash( $_POST['apply_email'] ?? '' ) ) ? sanitize_email( wp_unslash( $_POST['apply_email'] ?? '' ) ) : $company_email,
            'apply_url' => esc_url_raw( wp_unslash( $_POST['apply_url'] ?? '' ) ),'expiry_date' => sanitize_text_field( wp_unslash( $_POST['expiry_date'] ?? '' ) ),
            'application_deadline' => $deadline,'deadline_closed_message' => sanitize_text_field( wp_unslash( $_POST['deadline_closed_message'] ?? '' ) ),
            'close_on_deadline' => empty( $_POST['close_on_deadline'] ) ? 0 : 1,'featured' => empty( $_POST['featured'] ) ? 0 : 1,
            'resume_required' => empty( $_POST['resume_required'] ) ? 0 : 1,'company_logo_attachment_id' => $company_logo_id,
            'job_category_id' => absint( $_POST['job_category_id'] ?? 0 ),
            'employer_user_id' => bjm_is_agency_user() ? absint( $_POST['advertiser_user_id'] ?? get_current_user_id() ) : get_current_user_id(),
        );
    }

    public static function save_submission( $payload, $user_id, $job_id = 0, $forced_status = '', $mark_paid = false ) {
        $is_edit = $job_id > 0;
        $status = $forced_status ? $forced_status : bjm_get_setting( 'default_job_status', 'publish' );
        $data = array( 'post_type' => 'job_listing','post_title' => $payload['post_title'],'post_content' => $payload['post_content'],'post_status' => $status,'post_author' => ! empty( $payload['employer_user_id'] ) ? (int) $payload['employer_user_id'] : $user_id );
        if ( $is_edit ) { $data['ID'] = $job_id; $saved_id = wp_update_post( $data, true ); } else { $saved_id = wp_insert_post( $data, true ); }
        if ( is_wp_error( $saved_id ) || ! $saved_id ) { return $saved_id; }
        $fields = array(
            '_bjm_company_name' => $payload['company_name'],'_bjm_company_id' => $payload['company_id'],'_bjm_location_text' => $payload['location_text'],
            '_bjm_work_mode' => $payload['work_mode'],'_bjm_salary_min' => $payload['salary_min'],'_bjm_salary_max' => $payload['salary_max'],
            '_bjm_apply_email' => $payload['apply_email'],'_bjm_apply_url' => $payload['apply_url'],'_bjm_expiry_date' => $payload['expiry_date'],
            '_bjm_application_deadline' => $payload['application_deadline'],'_bjm_deadline_closed_message' => $payload['deadline_closed_message'],
            '_bjm_close_on_deadline' => $payload['close_on_deadline'],'_bjm_featured' => $payload['featured'],'_bjm_resume_required' => $payload['resume_required'],
            '_bjm_employer_user_id' => ! empty( $payload['employer_user_id'] ) ? (int) $payload['employer_user_id'] : $user_id,
        );
        foreach ( $fields as $key => $val ) { update_post_meta( $saved_id, $key, $val ); }
        if ( ! empty( $payload['job_category_id'] ) && in_array( (int) $payload['job_category_id'], bjm_get_allowed_job_category_ids(), true ) ) { wp_set_post_terms( $saved_id, array( (int) $payload['job_category_id'] ), 'job_category', false ); }
        if ( $mark_paid ) { update_post_meta( $saved_id, '_bjm_paid_submission', 1 ); }
        if ( ! empty( $payload['company_logo_attachment_id'] ) ) { update_post_meta( $saved_id, '_bjm_company_logo_id', (int) $payload['company_logo_attachment_id'] ); }
        return $saved_id;
    }
}
