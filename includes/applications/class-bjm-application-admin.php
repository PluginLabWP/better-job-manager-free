<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Application_Admin {
    public static function init() { add_action( 'admin_menu', array( __CLASS__, 'menu' ) ); }
    public static function menu() { add_submenu_page( 'edit.php?post_type=job_listing', 'Applications', 'Applications', 'manage_all_applications', 'bjm-applications', array( __CLASS__, 'render' ) ); }
    private static function maybe_handle_bulk_actions() {
        if ( ! current_user_can( 'manage_all_applications' ) ) { return; }
        $action = sanitize_key( $_REQUEST['bjm_bulk_action'] ?? '' );
        if ( ! $action || empty( $_REQUEST['application_ids'] ) || ! is_array( $_REQUEST['application_ids'] ) ) { return; }
        check_admin_referer( 'bjm_bulk_applications' );
        $ids = array_filter( array_map( 'absint', (array) $_REQUEST['application_ids'] ) ); if ( empty( $ids ) ) { return; }
        global $wpdb; $table = $wpdb->prefix . 'bjm_applications'; $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        if ( 'delete' === $action ) { $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ($placeholders)", $ids ) ); echo '<div class="notice notice-success is-dismissible"><p>Selected applications deleted.</p></div>'; return; }
        $statuses = bjm_get_application_statuses();
        if ( isset( $statuses[ $action ] ) ) {
            $args = array_merge( array( $action, current_time( 'mysql' ), current_time( 'mysql' ) ), $ids );
            $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, status_changed_at = %s, updated_at = %s WHERE id IN ($placeholders)", $args ) );
            foreach ( $ids as $id ) { bjm_add_application_note( $id, 'Status changed to ' . $statuses[ $action ] . '.', get_current_user_id() ); }
            echo '<div class="notice notice-success is-dismissible"><p>Selected applications updated.</p></div>';
        }
    }
    public static function render() {
        if ( ! current_user_can( 'manage_all_applications' ) ) { wp_die( esc_html__( 'You do not have permission to view applications.', 'better-job-manager' ) ); }
        self::maybe_handle_bulk_actions(); global $wpdb; $table = $wpdb->prefix . 'bjm_applications';
        $status = sanitize_key( $_GET['status'] ?? '' ); $search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); $paged = max( 1, absint( $_GET['paged'] ?? 1 ) ); $per_page = 20;
        if ( isset( $_GET['bjm_export'] ) && 'csv' === $_GET['bjm_export'] ) { self::export_csv( $status, $search ); }
        $where = 'WHERE 1=1'; if ( $status ) { $where .= $wpdb->prepare( ' AND status = %s', $status ); }
        if ( $search ) { $like = '%' . $wpdb->esc_like( $search ) . '%'; $where .= $wpdb->prepare( ' AND (applicant_name LIKE %s OR applicant_email LIKE %s)', $like, $like ); }
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" ); $offset = ( $paged - 1 ) * $per_page;
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );
        $base_url = add_query_arg( array( 'post_type'=>'job_listing','page'=>'bjm-applications','status'=>$status,'s'=>$search ), admin_url( 'edit.php' ) );
        echo '<div class="wrap"><h1>Applications</h1><form method="get"><input type="hidden" name="post_type" value="job_listing"><input type="hidden" name="page" value="bjm-applications"><input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="Search applicants"><select name="status"><option value="">All statuses</option>';
        foreach ( bjm_get_application_statuses() as $key=>$label ) { echo '<option value="'.esc_attr($key).'" '.selected($status,$key,false).'>'.esc_html($label).'</option>'; }
        echo '</select><button class="button">Filter</button> <a class="button button-secondary" href="'.esc_url(add_query_arg('bjm_export','csv',$base_url)).'">Export CSV</a></form><form method="post">'; wp_nonce_field('bjm_bulk_applications');
        echo '<div class="tablenav top"><div class="alignleft actions"><select name="bjm_bulk_action"><option value="">Bulk actions</option>';
        foreach ( bjm_get_application_statuses() as $key=>$label ) { echo '<option value="'.esc_attr($key).'">Mark '.esc_html($label).'</option>'; }
        echo '<option value="delete">Delete</option></select> <button class="button action">Apply</button></div></div><table class="widefat striped"><thead><tr><td class="check-column"><input type="checkbox" class="bjm-check-all"></td><th>Date</th><th>Applicant</th><th>Email</th><th>Job</th><th>Status</th><th>Deadline</th><th>Source</th><th>Resume</th><th>Notes</th><th>Messages</th><th>Interviews</th></tr></thead><tbody>';
        foreach ( $rows as $row ) {
            $resume=$row->resume_attachment_id?'<a href="'.esc_url(wp_get_attachment_url($row->resume_attachment_id)).'">Download</a>':'—'; $note_count=bjm_get_application_note_count($row->id); $message_count=function_exists('bjm_get_application_message_count')?bjm_get_application_message_count($row->id):0; $interview_count=function_exists('bjm_get_application_interview_count')?bjm_get_application_interview_count($row->id):0;
            echo '<tr><th scope="row" class="check-column"><input type="checkbox" name="application_ids[]" value="'.esc_attr($row->id).'"></th><td>'.esc_html($row->created_at).'</td><td>'.esc_html($row->applicant_name).'</td><td>'.esc_html($row->applicant_email).'</td><td>'.esc_html(get_the_title($row->job_id)).'</td><td>'.esc_html($row->status).'</td><td>'.esc_html($row->deadline_snapshot?$row->deadline_snapshot:'—').'</td><td>'.esc_html($row->source?$row->source:'site').'</td><td>'.$resume.'</td><td>'.esc_html($note_count).' note(s)</td><td>'.esc_html($message_count).'</td><td>'.esc_html($interview_count).'</td></tr>';
        }
        if(!$rows){echo '<tr><td colspan="12">No applications found.</td></tr>';}
        echo '</tbody></table></form>'; $total_pages=max(1,(int)ceil($total/$per_page)); if($total_pages>1){echo '<div class="tablenav"><div class="tablenav-pages">'.paginate_links(array('base'=>add_query_arg('paged','%#%',$base_url),'format'=>'','current'=>$paged,'total'=>$total_pages)).'</div></div>';} echo '</div>';
    }
    private static function export_csv( $status, $search ) {
        global $wpdb; $table=$wpdb->prefix.'bjm_applications'; $where='WHERE 1=1'; if($status){$where.=$wpdb->prepare(' AND status = %s',$status);} if($search){$like='%'.$wpdb->esc_like($search).'%';$where.=$wpdb->prepare(' AND (applicant_name LIKE %s OR applicant_email LIKE %s)',$like,$like);} $limit=bjm_get_application_export_limit(); $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d",$limit),ARRAY_A);
        nocache_headers(); header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=bjm-applications.csv'); $out=fopen('php://output','w'); fputcsv($out,array('Date','Applicant','Email','Phone','Job','Status','Deadline','Source','Notes')); foreach($rows as $row){fputcsv($out,array($row['created_at'],$row['applicant_name'],$row['applicant_email'],$row['applicant_phone'],get_the_title($row['job_id']),$row['status'],$row['deadline_snapshot'],$row['source'],$row['notes']));} fclose($out); exit;
    }
}
