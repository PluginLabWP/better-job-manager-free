<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Applications_DB {
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bjm_applications';
        $notes_table = $wpdb->prefix . 'bjm_application_notes';
        $alerts_table = $wpdb->prefix . 'bjm_job_alerts';
        $messages_table = $wpdb->prefix . 'bjm_application_messages';
        $interviews_table = $wpdb->prefix . 'bjm_application_interviews';
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql_applications = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id BIGINT UNSIGNED NOT NULL,
            employer_user_id BIGINT UNSIGNED NOT NULL,
            applicant_name VARCHAR(190) NOT NULL,
            applicant_email VARCHAR(190) NOT NULL,
            applicant_phone VARCHAR(100) NULL,
            cover_letter LONGTEXT NULL,
            resume_attachment_id BIGINT UNSIGNED NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'new',
            status_changed_at DATETIME NULL,
            deadline_snapshot VARCHAR(30) NULL,
            source VARCHAR(80) NULL,
            rating TINYINT UNSIGNED NULL,
            notes LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY job_id (job_id),
            KEY employer_user_id (employer_user_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $sql_notes = "CREATE TABLE {$notes_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            application_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            note_content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY application_id (application_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $sql_messages = "CREATE TABLE {$messages_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            application_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            recipient_name VARCHAR(190) NULL,
            recipient_email VARCHAR(190) NULL,
            subject VARCHAR(255) NULL,
            message_body LONGTEXT NOT NULL,
            is_email_sent TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY application_id (application_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $sql_interviews = "CREATE TABLE {$interviews_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            application_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            interview_at DATETIME NOT NULL,
            interview_type VARCHAR(40) NULL,
            location VARCHAR(190) NULL,
            meeting_link VARCHAR(255) NULL,
            notes LONGTEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY application_id (application_id),
            KEY interview_at (interview_at),
            KEY status (status)
        ) {$charset_collate};";

        $sql_alerts = "CREATE TABLE {$alerts_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            email VARCHAR(190) NOT NULL,
            keyword VARCHAR(190) NULL,
            category_slug VARCHAR(190) NULL,
            region_slug VARCHAR(190) NULL,
            frequency VARCHAR(20) NOT NULL DEFAULT 'daily',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            manage_token VARCHAR(80) NULL,
            verify_token VARCHAR(80) NULL,
            verified_at DATETIME NULL,
            last_sent_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY frequency (frequency),
            KEY manage_token (manage_token),
            KEY verify_token (verify_token)
        ) {$charset_collate};";

        dbDelta( $sql_applications );
        dbDelta( $sql_notes );
        dbDelta( $sql_messages );
        dbDelta( $sql_interviews );
        dbDelta( $sql_alerts );
    }
}
