<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Deactivator {
    public static function deactivate() {
        wp_clear_scheduled_hook( 'bjm_daily_expiry_event' );
        wp_clear_scheduled_hook( 'bjm_daily_alert_event' );
        flush_rewrite_rules();
    }
}
