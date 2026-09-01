<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Company_Admin {
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add' ) );
        add_action( 'save_post_bjm_company', array( __CLASS__, 'save' ) );
    }
    public static function add() {
        add_meta_box( 'bjm_company_meta', 'Company Details', array( __CLASS__, 'render' ), 'bjm_company', 'normal', 'default' );
    }
    public static function render( $post ) {
        wp_nonce_field( 'bjm_company_meta', 'bjm_company_meta_nonce' );
        echo '<p><label>Website</label><input type="url" name="bjm_company_website" class="widefat" value="' . esc_attr( get_post_meta( $post->ID, '_bjm_company_website', true ) ) . '"></p>';
        echo '<p><label>Email</label><input type="email" name="bjm_company_email" class="widefat" value="' . esc_attr( get_post_meta( $post->ID, '_bjm_company_email', true ) ) . '"></p>';
        echo '<p><label>Location</label><input type="text" name="bjm_company_location" class="widefat" value="' . esc_attr( get_post_meta( $post->ID, '_bjm_company_location', true ) ) . '"></p>';
    }
    public static function save( $post_id ) {
        if ( ! isset( $_POST['bjm_company_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bjm_company_meta_nonce'] ) ), 'bjm_company_meta' ) ) { return; }
        update_post_meta( $post_id, '_bjm_company_website', esc_url_raw( wp_unslash( $_POST['bjm_company_website'] ?? '' ) ) );
        update_post_meta( $post_id, '_bjm_company_email', sanitize_email( wp_unslash( $_POST['bjm_company_email'] ?? '' ) ) );
        update_post_meta( $post_id, '_bjm_company_location', sanitize_text_field( wp_unslash( $_POST['bjm_company_location'] ?? '' ) ) );
    }
}
