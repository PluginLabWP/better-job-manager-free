<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_WooCommerce {
    public static function init() {
        add_filter( 'bjm_job_submission_requires_payment', array( __CLASS__, 'requires_payment' ), 10, 3 );
        add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'attach_pending_token_to_order' ), 10, 2 );
        add_action( 'woocommerce_thankyou', array( __CLASS__, 'handle_completed_checkout' ) );
        add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'handle_order_status' ) );
        add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'handle_order_status' ) );
        add_action( 'woocommerce_subscription_renewal_payment_complete', array( __CLASS__, 'handle_subscription_renewal' ), 10, 2 );
    }
    public static function requires_payment( $requires, $user_id, $job_id ) {
        if ( ! bjm_get_setting( 'enable_paid_listings', 0 ) || $job_id || bjm_get_user_package_credits( $user_id ) > 0 ) return false;
        return class_exists( 'WooCommerce' ) && absint( bjm_get_setting( 'listing_product_id', 0 ) ) > 0;
    }
    public static function queue_submission_for_checkout( $payload, $user_id ) {
        if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) return new WP_Error( 'bjm_no_woocommerce', __( 'WooCommerce is required for paid listings.', 'better-job-manager' ) );
        $listing_product_id=absint(bjm_get_setting('listing_product_id',0)); $featured_product_id=absint(bjm_get_setting('featured_product_id',0));
        if(!$listing_product_id)return new WP_Error('bjm_no_listing_product',__('No listing product is configured.','better-job-manager'));
        if(!WC()->cart)return new WP_Error('bjm_no_cart',__('WooCommerce cart is not available.','better-job-manager'));
        bjm_cleanup_pending_submissions($user_id); $token=bjm_create_pending_submission($user_id,$payload); if(WC()->session)WC()->session->set('bjm_pending_token',$token);
        WC()->cart->empty_cart(); WC()->cart->add_to_cart($listing_product_id,1,0,array(),array('bjm_pending_token'=>$token,'bjm_is_listing_product'=>1));
        if(!empty($payload['featured'])&&$featured_product_id)WC()->cart->add_to_cart($featured_product_id,1,0,array(),array('bjm_pending_token'=>$token,'bjm_is_featured_upgrade'=>1));
        return wc_get_checkout_url();
    }
    public static function attach_pending_token_to_order( $order, $data ) { if(!function_exists('WC')||!WC()->session)return; $token=WC()->session->get('bjm_pending_token'); if($token)$order->update_meta_data('_bjm_pending_token',sanitize_text_field($token)); }
    public static function handle_completed_checkout( $order_id ) { if($order_id)self::handle_order_status($order_id); }
    public static function handle_subscription_renewal( $subscription, $last_order ) { if($last_order&&is_a($last_order,'WC_Order'))self::apply_credit_maps_to_order($last_order); }
    public static function handle_order_status( $order_id ) {
        if(!class_exists('WooCommerce'))return; $order=wc_get_order($order_id); if(!$order||$order->get_meta('_bjm_processed',true))return; $user_id=(int)$order->get_user_id();
        if(!$user_id){$order->update_meta_data('_bjm_processed',1);$order->save();return;}
        $has_relevant_products=self::apply_credit_maps_to_order($order); $listing_product_id=absint(bjm_get_setting('listing_product_id',0)); $featured_product_id=absint(bjm_get_setting('featured_product_id',0)); $pending_token=sanitize_text_field((string)$order->get_meta('_bjm_pending_token',true));
        if(!$pending_token&&function_exists('WC')&&WC()->session)$pending_token=sanitize_text_field((string)WC()->session->get('bjm_pending_token'));
        if($pending_token&&bjm_order_contains_product_ids($order,array_filter(array($listing_product_id,$featured_product_id)))){
            $pending=bjm_get_pending_submission($user_id,$pending_token); if(!empty($pending)){ $job_id=BJM_Submit_Job_Form::save_submission($pending,$user_id,0,'publish',true); if($job_id&&!is_wp_error($job_id)){bjm_delete_pending_submission($user_id,$pending_token);update_post_meta($job_id,'_bjm_paid_order_id',$order_id);update_post_meta($job_id,'_bjm_checkout_token',$pending_token);bjm_add_notice('Your paid job listing has been published.');}}
        } elseif(!$has_relevant_products){$order->update_meta_data('_bjm_processed',1);$order->save();return;}
        if(function_exists('WC')&&WC()->session)WC()->session->__unset('bjm_pending_token'); $order->update_meta_data('_bjm_processed',1);$order->save();
    }
    private static function apply_credit_maps_to_order( $order ) {
        $user_id=(int)$order->get_user_id(); if(!$user_id)return false; $package_map=bjm_get_package_map(); $sub_map=bjm_get_subscription_credit_map(); $relevant=false;$package_credited=0;$subscription_credited=0;
        foreach($order->get_items() as $item){$product_id=(int)$item->get_product_id();$qty=max(1,(int)$item->get_quantity());if(isset($package_map[$product_id])){$relevant=true;$package_credited+=(int)$package_map[$product_id]*$qty;}if(isset($sub_map[$product_id])){$relevant=true;$subscription_credited+=(int)$sub_map[$product_id]*$qty;}}
        if($relevant){$already=(int)$order->get_meta('_bjm_credits_awarded',true);if(!$already){$starting=bjm_get_user_package_credits($user_id);$ending=$starting;if($subscription_credited>0){$ending='set'===bjm_get_subscription_credit_mode()?$subscription_credited:$ending+$subscription_credited;}if($package_credited>0)$ending+=$package_credited;bjm_set_user_package_credits($user_id,$ending);bjm_add_notice(sprintf('Package credits updated. New balance: %d.',$ending));$order->update_meta_data('_bjm_credits_awarded',$ending-$starting);$order->save();}}
        return $relevant;
    }
}
