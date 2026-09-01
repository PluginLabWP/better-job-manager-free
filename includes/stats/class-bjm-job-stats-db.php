<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Job_Stats_DB {
    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'bjm_job_stats_daily';
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id BIGINT UNSIGNED NOT NULL,
            stat_date DATE NOT NULL,
            views BIGINT UNSIGNED NOT NULL DEFAULT 0,
            unique_views BIGINT UNSIGNED NOT NULL DEFAULT 0,
            apply_opens BIGINT UNSIGNED NOT NULL DEFAULT 0,
            external_apply_clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
            applications BIGINT UNSIGNED NOT NULL DEFAULT 0,
            saves BIGINT UNSIGNED NOT NULL DEFAULT 0,
            alert_sends BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY job_date (job_id, stat_date),
            KEY stat_date (stat_date)
        ) {$charset_collate};";
        dbDelta( $sql );
    }
}
