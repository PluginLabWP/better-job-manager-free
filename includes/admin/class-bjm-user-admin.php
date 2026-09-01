<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_User_Admin {
    public static function init() {
        add_action( 'show_user_profile', array( __CLASS__, 'render_profile_fields' ) );
        add_action( 'edit_user_profile', array( __CLASS__, 'render_profile_fields' ) );
        add_action( 'personal_options_update', array( __CLASS__, 'save_profile_fields' ) );
        add_action( 'edit_user_profile_update', array( __CLASS__, 'save_profile_fields' ) );
    }
    public static function render_profile_fields( $user ) {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $agency_id = (int) get_user_meta( $user->ID, '_bjm_assigned_agency', true );
        $agency_advertisers = array_map( 'absint', (array) get_user_meta( $user->ID, '_bjm_agency_advertisers', true ) );
        $agencies = get_users( array( 'role' => 'agency', 'orderby' => 'display_name', 'order' => 'ASC' ) );
        $employers = get_users( array( 'role__in' => array( 'employer' ), 'orderby' => 'display_name', 'order' => 'ASC' ) );
        echo '<h2>Better Job Manager</h2><table class="form-table">';
        if ( in_array( 'employer', (array) $user->roles, true ) ) {
            echo '<tr><th><label for="bjm_assigned_agency">Assigned Agency</label></th><td><select name="bjm_assigned_agency" id="bjm_assigned_agency"><option value="0">— None —</option>';
            foreach ( $agencies as $agency ) {
                echo '<option value="' . esc_attr( $agency->ID ) . '" ' . selected( $agency_id, $agency->ID, false ) . '>' . esc_html( $agency->display_name ) . ' (' . esc_html( $agency->user_email ) . ')</option>';
            }
            echo '</select><p class="description">Assign this advertiser to an agency account so the agency can manage jobs and applicants on their behalf.</p></td></tr>';
        }
        if ( in_array( 'agency', (array) $user->roles, true ) ) {
            echo '<tr><th>Managed Advertisers</th><td><select name="bjm_agency_advertisers[]" multiple size="8" style="min-width: 320px;">';
            foreach ( $employers as $employer ) {
                echo '<option value="' . esc_attr( $employer->ID ) . '" ' . selected( in_array( $employer->ID, $agency_advertisers, true ), true, false ) . '>' . esc_html( $employer->display_name ) . ' (' . esc_html( $employer->user_email ) . ')</option>';
            }
            echo '</select><p class="description">Advertiser accounts this agency may manage from the frontend dashboard.</p></td></tr>';
        }
        echo '</table>';
    }
    public static function save_profile_fields( $user_id ) {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $user = get_userdata( $user_id ); if ( ! $user ) { return; }
        if ( in_array( 'employer', (array) $user->roles, true ) ) {
            $agency_id = absint( $_POST['bjm_assigned_agency'] ?? 0 );
            update_user_meta( $user_id, '_bjm_assigned_agency', $agency_id );
            if ( $agency_id ) {
                $managed = array_map( 'absint', (array) get_user_meta( $agency_id, '_bjm_agency_advertisers', true ) );
                if ( ! in_array( $user_id, $managed, true ) ) {
                    $managed[] = $user_id;
                    update_user_meta( $agency_id, '_bjm_agency_advertisers', array_values( array_unique( array_filter( $managed ) ) ) );
                }
            }
        }
        if ( in_array( 'agency', (array) $user->roles, true ) ) {
            $advertisers = array_filter( array_map( 'absint', (array) ( $_POST['bjm_agency_advertisers'] ?? array() ) ) );
            update_user_meta( $user_id, '_bjm_agency_advertisers', array_values( array_unique( $advertisers ) ) );
            foreach ( $advertisers as $advertiser_id ) {
                update_user_meta( $advertiser_id, '_bjm_assigned_agency', $user_id );
            }
        }
    }
}
