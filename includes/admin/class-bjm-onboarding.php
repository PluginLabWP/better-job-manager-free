<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BJM_Onboarding {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'maybe_redirect' ) );
        add_action( 'admin_post_bjm_run_setup', array( __CLASS__, 'handle_setup' ) );
        add_action( 'admin_post_bjm_send_test_email', array( __CLASS__, 'handle_test_email' ) );
        add_action( 'admin_notices', array( __CLASS__, 'setup_notice' ) );
    }
    public static function menu() { add_submenu_page( 'edit.php?post_type=job_listing', __( 'Setup & Health', 'better-job-manager' ), __( 'Setup & Health', 'better-job-manager' ), 'manage_job_board_settings', 'bjm-setup-health', array( __CLASS__, 'render' ) ); }
    public static function maybe_redirect() {
        if ( ! current_user_can( 'manage_job_board_settings' ) || ! get_transient( 'bjm_activation_redirect' ) ) { return; }
        delete_transient( 'bjm_activation_redirect' ); if ( wp_doing_ajax() || isset( $_GET['activate-multi'] ) ) { return; }
        wp_safe_redirect( admin_url( 'edit.php?post_type=job_listing&page=bjm-setup-health&welcome=1' ) ); exit;
    }
    public static function setup_notice() {
        if ( ! current_user_can( 'manage_job_board_settings' ) ) { return; }
        $settings = get_option( 'bjm_settings', array() ); $required = array( 'jobs_page_id','submit_page_id','dashboard_page_id','candidate_dashboard_page_id','advertiser_register_page_id' );
        foreach ( $required as $key ) { if ( empty( $settings[$key] ) ) { $url=admin_url('edit.php?post_type=job_listing&page=bjm-setup-health'); echo '<div class="notice notice-info"><p><strong>Better Job Manager:</strong> '.esc_html__('Finish setup to create and assign the recommended frontend pages.','better-job-manager').' <a href="'.esc_url($url).'">'.esc_html__('Open Setup','better-job-manager').'</a></p></div>'; break; } }
    }
    public static function page_definitions() {
        return array(
            'jobs_page_id'=>array('title'=>'Jobs','slug'=>'jobs','content'=>'[bjm_jobs]'),
            'submit_page_id'=>array('title'=>'Post a Job','slug'=>'post-a-job','content'=>'[bjm_submit_job]'),
            'dashboard_page_id'=>array('title'=>'Employer Dashboard','slug'=>'employer-dashboard','content'=>'[bjm_employer_dashboard]'),
            'candidate_dashboard_page_id'=>array('title'=>'Candidate Dashboard','slug'=>'candidate-dashboard','content'=>'[bjm_candidate_dashboard]'),
            'advertiser_register_page_id'=>array('title'=>'Advertiser Registration','slug'=>'advertiser-registration','content'=>'[bjm_advertiser_register]'),
            'company_dashboard_page_id'=>array('title'=>'Company Dashboard','slug'=>'company-dashboard','content'=>'[bjm_company_dashboard]'),
        );
    }
    public static function handle_setup() {
        if ( ! current_user_can( 'manage_job_board_settings' ) ) { wp_die( esc_html__( 'Permission denied.', 'better-job-manager' ) ); }
        check_admin_referer( 'bjm_run_setup' ); $settings=get_option('bjm_settings',array());
        foreach(self::page_definitions() as $key=>$def){ $existing=absint($settings[$key]??0); if($existing&&get_post_status($existing)){continue;} $page=get_page_by_path($def['slug']); if($page&&'trash'!==$page->post_status){$page_id=$page->ID;}else{$page_id=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>$def['title'],'post_name'=>$def['slug'],'post_content'=>$def['content']));} if($page_id&&!is_wp_error($page_id)){$settings[$key]=absint($page_id);} }
        update_option('bjm_settings',$settings); update_option('bjm_setup_complete',1); flush_rewrite_rules(); wp_safe_redirect(add_query_arg('setup','complete',admin_url('edit.php?post_type=job_listing&page=bjm-setup-health'))); exit;
    }
    public static function handle_test_email() {
        if ( ! current_user_can( 'manage_job_board_settings' ) ) { wp_die( esc_html__( 'Permission denied.', 'better-job-manager' ) ); }
        check_admin_referer('bjm_send_test_email'); $user=wp_get_current_user(); $sent=wp_mail($user->user_email,'Better Job Manager test email',"Your Better Job Manager site can call wp_mail().\n\nThis does not guarantee inbox delivery; SMTP is recommended for production sites."); wp_safe_redirect(add_query_arg('mail_test',$sent?'sent':'failed',admin_url('edit.php?post_type=job_listing&page=bjm-setup-health'))); exit;
    }
    private static function status_row( $label,$ok,$detail,$action='' ) { echo '<tr><td><strong>'.esc_html($label).'</strong></td><td><span class="bjm-health-status '.($ok?'is-good':'is-bad').'">'.($ok?'✓ Good':'⚠ Needs attention').'</span></td><td>'.wp_kses_post($detail); if($action){echo '<div class="bjm-health-action">'.wp_kses_post($action).'</div>';} echo '</td></tr>'; }
    public static function render() {
        if ( ! current_user_can( 'manage_job_board_settings' ) ) { return; }
        $settings=get_option('bjm_settings',array()); $defs=self::page_definitions(); ?>
        <div class="wrap bjm-setup-wrap"><h1><?php esc_html_e('Better Job Manager Setup & Health','better-job-manager'); ?></h1><p class="description"><?php esc_html_e('Get the core pages in place, then use the health checks below to spot common configuration problems.','better-job-manager'); ?></p>
        <?php if(isset($_GET['setup'])&&'complete'===$_GET['setup']): ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e('Setup complete. Recommended pages have been created or assigned.','better-job-manager'); ?></p></div><?php endif; ?>
        <?php if(isset($_GET['mail_test'])): ?><div class="notice <?php echo 'sent'===$_GET['mail_test']?'notice-success':'notice-error'; ?> is-dismissible"><p><?php echo 'sent'===$_GET['mail_test']?esc_html__('WordPress accepted the test email for sending.','better-job-manager'):esc_html__('WordPress could not send the test email. Check your mail/SMTP configuration.','better-job-manager'); ?></p></div><?php endif; ?>
        <div class="bjm-setup-grid"><section class="bjm-admin-card"><h2><?php esc_html_e('1. Create recommended pages','better-job-manager'); ?></h2><p><?php esc_html_e('This creates any missing pages and assigns them automatically. Existing configured pages are left untouched.','better-job-manager'); ?></p><ul class="bjm-setup-page-list">
        <?php foreach($defs as $key=>$def): $page_id=absint($settings[$key]??0); $ok=$page_id&&get_post_status($page_id); ?><li><span><?php echo esc_html($def['title']); ?></span><strong class="<?php echo $ok?'is-good':'is-bad'; ?>"><?php echo $ok?'Ready':'Missing'; ?></strong></li><?php endforeach; ?></ul>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bjm_run_setup"><?php wp_nonce_field('bjm_run_setup'); submit_button(__('Create / Assign Recommended Pages','better-job-manager'),'primary','submit',false); ?></form></section>
        <section class="bjm-admin-card"><h2><?php esc_html_e('2. Quick links','better-job-manager'); ?></h2><p><a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=job_listing&page=bjm-settings')); ?>"><?php esc_html_e('Open Settings','better-job-manager'); ?></a></p><p><a class="button" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=job_category&post_type=job_listing')); ?>"><?php esc_html_e('Manage Industries','better-job-manager'); ?></a></p><p><a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=job_listing&page=bjm-tools')); ?>"><?php esc_html_e('Demo Data / Tools','better-job-manager'); ?></a></p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bjm_send_test_email"><?php wp_nonce_field('bjm_send_test_email'); submit_button(__('Send Test Email to Me','better-job-manager'),'secondary','submit',false); ?></form></section></div>
        <section class="bjm-admin-card bjm-health-card"><h2><?php esc_html_e('System health','better-job-manager'); ?></h2><table class="widefat striped"><thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead><tbody><?php
        $all_pages=true; foreach($defs as $key=>$def){$pid=absint($settings[$key]??0);if(!$pid||!get_post_status($pid)){$all_pages=false;break;}}
        self::status_row('Frontend pages',$all_pages,$all_pages?'All recommended pages are assigned.':'One or more recommended pages are missing.');
        $permalink_ok=''!==get_option('permalink_structure'); self::status_row('Permalinks',$permalink_ok,$permalink_ok?'Pretty permalinks are enabled.':'Plain permalinks are enabled. Job URLs work best with a pretty permalink structure.','<a href="'.esc_url(admin_url('options-permalink.php')).'">Open Permalink Settings</a>');
        $expiry_ok=(bool)wp_next_scheduled('bjm_daily_expiry_event'); $alert_ok=(bool)wp_next_scheduled('bjm_daily_alert_event'); self::status_row('Expiry cron',$expiry_ok,$expiry_ok?'Daily job expiry task is scheduled.':'Daily job expiry task is not currently scheduled.'); self::status_row('Job alerts cron',$alert_ok,$alert_ok?'Daily job alert task is scheduled.':'Daily job alert task is not currently scheduled.');
        $uploads=wp_upload_dir(); self::status_row('Uploads',empty($uploads['error']),empty($uploads['error'])?'WordPress uploads directory is available.':esc_html($uploads['error'])); $wc_active=class_exists('WooCommerce'); self::status_row('WooCommerce',true,$wc_active?'WooCommerce is active and optional paid-listing integrations can be used.':'WooCommerce is not active. This is fine unless you want paid listings/packages.'); $php_ok=version_compare(PHP_VERSION,'7.4','>='); self::status_row('PHP version',$php_ok,'Running PHP '.esc_html(PHP_VERSION).'.'); ?>
        </tbody></table></section></div><?php
    }
}
