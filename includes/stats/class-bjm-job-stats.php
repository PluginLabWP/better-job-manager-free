<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Job_Stats {
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );
        add_action( 'template_redirect', array( __CLASS__, 'maybe_track_job_view' ) );
        add_action( 'init', array( __CLASS__, 'handle_external_apply_redirect' ) );
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
    }
    public static function maybe_upgrade() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $version = get_option( 'bjm_stats_schema_version', '' );
        if ( BJM_VERSION !== $version ) { BJM_Job_Stats_DB::create_table(); update_option( 'bjm_stats_schema_version', BJM_VERSION ); }
    }
    public static function maybe_track_job_view() {
        if ( ! is_singular( 'job_listing' ) || is_admin() ) { return; }
        $job_id = get_queried_object_id(); if ( ! $job_id ) { return; }
        self::increment_stat( $job_id, 'views', 1 );
        $key = 'bjm_viewed_' . $job_id . '_' . gmdate( 'Ymd', current_time( 'timestamp' ) );
        if ( empty( $_COOKIE[ $key ] ) ) { self::increment_stat( $job_id, 'unique_views', 1 ); setcookie( $key, '1', time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true ); $_COOKIE[ $key ] = '1'; }
    }
    public static function handle_external_apply_redirect() {
        if ( empty( $_GET['bjm_external_apply'] ) ) { return; }
        $job_id = absint( $_GET['bjm_external_apply'] ); $nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );
        if ( ! $job_id || ! wp_verify_nonce( $nonce, 'bjm_external_apply_' . $job_id ) ) { wp_safe_redirect( home_url( '/' ) ); exit; }
        $url = esc_url_raw( (string) get_post_meta( $job_id, '_bjm_apply_url', true ) ); if ( ! $url ) { wp_safe_redirect( get_permalink( $job_id ) ); exit; }
        self::increment_stat( $job_id, 'external_apply_clicks', 1 ); wp_redirect( $url ); exit;
    }
    public static function track_apply_open( $job_id ) { self::increment_stat( $job_id, 'apply_opens', 1 ); }
    public static function track_application( $job_id ) { self::increment_stat( $job_id, 'applications', 1 ); }
    public static function track_save( $job_id ) { self::increment_stat( $job_id, 'saves', 1 ); }
    public static function track_alert_send( $job_id, $count = 1 ) { self::increment_stat( $job_id, 'alert_sends', max( 1, absint( $count ) ) ); }
    public static function increment_stat( $job_id, $column, $amount = 1, $date = '' ) {
        global $wpdb; $allowed=array('views','unique_views','apply_opens','external_apply_clicks','applications','saves','alert_sends'); if(!$job_id||!in_array($column,$allowed,true))return;
        $date=$date?gmdate('Y-m-d',strtotime($date)):current_time('Y-m-d'); $table=$wpdb->prefix.'bjm_job_stats_daily';
        $row_id=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE job_id = %d AND stat_date = %s",$job_id,$date));
        if(!$row_id)$wpdb->insert($table,array('job_id'=>$job_id,'stat_date'=>$date,'created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')),array('%d','%s','%s','%s'));
        $wpdb->query($wpdb->prepare("INSERT INTO {$table} (job_id, stat_date, {$column}, created_at, updated_at) VALUES (%d, %s, %d, %s, %s) ON DUPLICATE KEY UPDATE {$column} = {$column} + VALUES({$column}), updated_at = VALUES(updated_at)",$job_id,$date,max(1,absint($amount)),current_time('mysql'),current_time('mysql')));
    }
    public static function get_summary_for_jobs( $job_ids, $days = 30 ) {
        global $wpdb; $job_ids=array_filter(array_map('absint',(array)$job_ids)); if(empty($job_ids))return array(); $table=$wpdb->prefix.'bjm_job_stats_daily'; $placeholders=implode(',',array_fill(0,count($job_ids),'%d'));
        $sql="SELECT job_id, SUM(views) AS views, SUM(unique_views) AS unique_views, SUM(apply_opens) AS apply_opens, SUM(external_apply_clicks) AS external_apply_clicks, SUM(applications) AS applications, SUM(saves) AS saves, SUM(alert_sends) AS alert_sends FROM {$table} WHERE stat_date >= %s AND job_id IN ({$placeholders}) GROUP BY job_id";
        $params=array_merge(array(gmdate('Y-m-d',strtotime('-'.max(1,absint($days)).' days',current_time('timestamp')))),$job_ids); $rows=$wpdb->get_results($wpdb->prepare($sql,$params),ARRAY_A); $indexed=array(); foreach($rows as $row)$indexed[(int)$row['job_id']]=$row; return $indexed;
    }
    public static function get_rollup_for_owner( $owner_id, $days = 30 ) {
        $jobs=get_posts(array('post_type'=>'job_listing','posts_per_page'=>200,'post_status'=>array('publish','pending','draft'),'author'=>absint($owner_id),'fields'=>'ids')); $per_job=self::get_summary_for_jobs($jobs,$days);
        $totals=array('views'=>0,'unique_views'=>0,'apply_opens'=>0,'external_apply_clicks'=>0,'applications'=>0,'saves'=>0,'alert_sends'=>0,'jobs'=>count($jobs)); foreach($per_job as $row){foreach(array_keys($totals) as $key){if('jobs'===$key)continue;$totals[$key]+=isset($row[$key])?(int)$row[$key]:0;}} return array('job_ids'=>$jobs,'per_job'=>$per_job,'totals'=>$totals);
    }
    public static function admin_menu() { add_submenu_page('edit.php?post_type=job_listing','Analytics','Analytics','manage_job_board','bjm-analytics',array(__CLASS__,'render_admin_page')); }
    public static function render_admin_page() {
        if(!current_user_can('manage_job_board'))wp_die(esc_html__('You do not have permission to access analytics.','better-job-manager')); $days=max(7,absint($_GET['days']??30));
        $jobs=get_posts(array('post_type'=>'job_listing','posts_per_page'=>500,'post_status'=>array('publish','pending','draft'),'fields'=>'ids')); $per_job=self::get_summary_for_jobs($jobs,$days); $totals=array('views'=>0,'unique_views'=>0,'apply_opens'=>0,'external_apply_clicks'=>0,'applications'=>0,'saves'=>0,'alert_sends'=>0);
        foreach($per_job as $row){foreach(array_keys($totals) as $key)$totals[$key]+=isset($row[$key])?(int)$row[$key]:0;} uasort($per_job,function($a,$b){return((int)$b['applications']+(int)$b['views'])<=>((int)$a['applications']+(int)$a['views']);});
        echo '<div class="wrap"><h1>Job Analytics</h1><p>See which listings are actually getting attention, saves, form opens, and applications.</p><p><a class="button '.(7===$days?'button-primary':'').'" href="'.esc_url(add_query_arg('days',7)).'">7 days</a> <a class="button '.(30===$days?'button-primary':'').'" href="'.esc_url(add_query_arg('days',30)).'">30 days</a> <a class="button '.(90===$days?'button-primary':'').'" href="'.esc_url(add_query_arg('days',90)).'">90 days</a></p><div class="bjm-stats-grid">';
        foreach(array('Views'=>'views','Unique views'=>'unique_views','Apply opens'=>'apply_opens','Applications'=>'applications','External apply clicks'=>'external_apply_clicks','Saved jobs'=>'saves','Alert sends'=>'alert_sends') as $label=>$key)echo '<div class="bjm-stat-card"><div class="bjm-stat-label">'.esc_html($label).'</div><div class="bjm-stat-value">'.esc_html(number_format_i18n((int)$totals[$key])).'</div></div>';
        echo '</div><table class="widefat striped"><thead><tr><th>Job</th><th>Views</th><th>Unique</th><th>Apply opens</th><th>Applications</th><th>External clicks</th><th>Saves</th><th>Alerts</th><th>Conversion</th></tr></thead><tbody>';
        if($per_job){foreach($per_job as $job_id=>$row){$views=max(0,(int)$row['views']);$applications=max(0,(int)$row['applications']);$conversion=$views>0?round(($applications/$views)*100,1):0;echo '<tr><td><a href="'.esc_url(get_permalink($job_id)).'">'.esc_html(get_the_title($job_id)).'</a></td><td>'.esc_html(number_format_i18n($views)).'</td><td>'.esc_html(number_format_i18n((int)$row['unique_views'])).'</td><td>'.esc_html(number_format_i18n((int)$row['apply_opens'])).'</td><td>'.esc_html(number_format_i18n($applications)).'</td><td>'.esc_html(number_format_i18n((int)$row['external_apply_clicks'])).'</td><td>'.esc_html(number_format_i18n((int)$row['saves'])).'</td><td>'.esc_html(number_format_i18n((int)$row['alert_sends'])).'</td><td>'.esc_html($conversion).'%</td></tr>';}}else echo '<tr><td colspan="9">No analytics recorded yet.</td></tr>'; echo '</tbody></table></div>';
    }
}
