<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Settings {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register' ) );
    }
    public static function menu() {
        add_submenu_page( 'edit.php?post_type=job_listing', 'Settings', 'Settings', 'manage_job_board_settings', 'bjm-settings', array( __CLASS__, 'render' ) );
    }
    public static function register() { register_setting( 'bjm_settings_group', 'bjm_settings' ); }
    public static function render() {
        if ( ! current_user_can( 'manage_job_board_settings' ) ) { wp_die( esc_html__( 'You do not have permission to access these settings.', 'better-job-manager' ) ); }
        $opt = get_option( 'bjm_settings', array() );
        $pages = get_pages();
        $categories = get_terms( array( 'taxonomy' => 'job_category', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
        if ( isset( $_GET['bjm_seed_categories'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'bjm_seed_categories' ) ) {
            BJM_Job_Taxonomies::seed_default_categories(); echo '<div class="notice notice-success"><p>Default industries seeded.</p></div>';
            $categories = get_terms( array( 'taxonomy' => 'job_category', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
        }
        ?>
        <div class="wrap"><h1>Better Job Manager Settings</h1>
        <p>Set your core pages here, then configure who can use which industries. Admin can add categories in the backend and then allow only selected ones for advertisers and agencies.</p>
        <p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'bjm_seed_categories', '1' ), 'bjm_seed_categories' ) ); ?>">Seed default industries</a></p>
        <form method="post" action="options.php"><?php settings_fields( 'bjm_settings_group' ); ?>
        <table class="form-table"><tbody>
        <tr><th>Jobs Page</th><td><select name="bjm_settings[jobs_page_id]"><option value="0">— Select —</option><?php foreach ( $pages as $page ) : ?><option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $opt['jobs_page_id'] ?? 0, $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th>Submit Page</th><td><select name="bjm_settings[submit_page_id]"><option value="0">— Select —</option><?php foreach ( $pages as $page ) : ?><option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $opt['submit_page_id'] ?? 0, $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th>Advertiser Registration Page</th><td><select name="bjm_settings[advertiser_register_page_id]"><option value="0">— Select —</option><?php foreach ( $pages as $page ) : ?><option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $opt['advertiser_register_page_id'] ?? 0, $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option><?php endforeach; ?></select><p class="description">Optional page for the <code>[bjm_advertiser_register]</code> shortcode.</p></td></tr>
        <tr><th>Company Dashboard Page</th><td><select name="bjm_settings[company_dashboard_page_id]"><option value="0">— Select —</option><?php foreach ( $pages as $page ) : ?><option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $opt['company_dashboard_page_id'] ?? 0, $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option><?php endforeach; ?></select><p class="description">Optional page for the <code>[bjm_company_dashboard]</code> shortcode.</p></td></tr>
        <tr><th>Dashboard Page</th><td><select name="bjm_settings[dashboard_page_id]"><option value="0">— Select —</option><?php foreach ( $pages as $page ) : ?><option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $opt['dashboard_page_id'] ?? 0, $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th>Candidate Dashboard Page</th><td><select name="bjm_settings[candidate_dashboard_page_id]"><option value="0">— Select —</option><?php foreach ( $pages as $page ) : ?><option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $opt['candidate_dashboard_page_id'] ?? 0, $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option><?php endforeach; ?></select><p class="description">Optional page for saved jobs, logged-in alerts, and guest alert signup/manage using the <code>[bjm_candidate_dashboard]</code> shortcode.</p></td></tr>
        <tr><th>Allowed industries/categories</th><td><select name="bjm_settings[allowed_job_category_ids][]" multiple size="12" style="min-width:340px;"><?php foreach ( $categories as $term ) : ?><option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( in_array( $term->term_id, array_map( 'absint', (array) ( $opt['allowed_job_category_ids'] ?? array() ) ), true ), true ); ?>><?php echo esc_html( $term->name ); ?></option><?php endforeach; ?></select><p class="description">Leave all selected to expose the full list. Add or edit categories under Jobs → Job Categories, then choose which ones advertisers and agencies are allowed to use here.</p></td></tr>
        <tr><th>Default Job Status</th><td><select name="bjm_settings[default_job_status]"><option value="publish" <?php selected( $opt['default_job_status'] ?? 'publish', 'publish' ); ?>>Publish</option><option value="pending" <?php selected( $opt['default_job_status'] ?? '', 'pending' ); ?>>Pending</option><option value="draft" <?php selected( $opt['default_job_status'] ?? '', 'draft' ); ?>>Draft</option></select></td></tr>
        <tr><th>Default Jobs Per Page</th><td><input type="number" min="1" max="100" name="bjm_settings[jobs_per_page]" value="<?php echo esc_attr( $opt['jobs_per_page'] ?? 12 ); ?>"></td></tr>
        <tr><th>Auto-unpublish expired jobs</th><td><label><input type="checkbox" name="bjm_settings[auto_unpublish_expired]" value="1" <?php checked( $opt['auto_unpublish_expired'] ?? 1, 1 ); ?>> Move expired jobs to draft during the daily expiry task.</label></td></tr>
        <tr><th>Application export row limit</th><td><input type="number" min="100" max="50000" name="bjm_settings[application_export_limit]" value="<?php echo esc_attr( $opt['application_export_limit'] ?? 5000 ); ?>"></td></tr>
        <tr><th>Import media during content import</th><td><label><input type="checkbox" name="bjm_settings[import_sideload_media]" value="1" <?php checked( $opt['import_sideload_media'] ?? 1, 1 ); ?>> Try to match or sideload logos and resumes from exported URLs when importing content or applications.</label></td></tr>
        <tr><th>Application Subject</th><td><input type="text" class="regular-text" name="bjm_settings[application_subject]" value="<?php echo esc_attr( $opt['application_subject'] ?? 'New application for {job_title}' ); ?>"></td></tr>
        <tr><th>Application Email Body</th><td><textarea class="large-text" rows="6" name="bjm_settings[application_body]"><?php echo esc_textarea( $opt['application_body'] ?? "A new application has been received for {job_title}.\n\nApplicant: {applicant_name}\nEmail: {applicant_email}\nPhone: {applicant_phone}" ); ?></textarea></td></tr>
        </tbody></table><?php submit_button(); ?></form></div>
        <?php
    }
}
