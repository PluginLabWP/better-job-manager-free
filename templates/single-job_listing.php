<?php get_header(); the_post(); ?>
<main class="bjm-container single-job"><h1><?php the_title(); ?></h1>
<p><strong>Company:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), '_bjm_company_name', true ) ); ?></p>
<p><strong>Location:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), '_bjm_location_text', true ) ); ?></p>
<?php $deadline = (string) get_post_meta( get_the_ID(), '_bjm_application_deadline', true ); if ( $deadline ) : ?>
<p><strong>Applications close:</strong> <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $deadline ) ); ?></p>
<?php endif; ?>
<div><?php the_content(); ?></div>
<?php $company_id = (int) get_post_meta( get_the_ID(), '_bjm_company_id', true ); if ( $company_id ) : ?><p><a href="<?php echo esc_url( get_permalink( $company_id ) ); ?>">View company profile</a></p><?php endif; ?>
<?php
$external_apply_url = esc_url( (string) get_post_meta( get_the_ID(), '_bjm_apply_url', true ) );
$apply_target_id = 'bjm-apply-form-wrap-' . get_the_ID();
$show_apply_form = isset( $_GET['bjm_apply'] ) && '1' === (string) wp_unslash( $_GET['bjm_apply'] );
if ( $show_apply_form && class_exists( 'BJM_Job_Stats' ) ) { BJM_Job_Stats::track_apply_open( get_the_ID() ); }
$apply_link = add_query_arg( 'bjm_apply', '1', get_permalink( get_the_ID() ) ) . '#' . $apply_target_id;
$is_saved = is_user_logged_in() ? bjm_is_job_saved( get_the_ID() ) : false;
$save_link = is_user_logged_in()
    ? ( $is_saved ? wp_nonce_url( add_query_arg( 'bjm_unsave_job', get_the_ID(), get_permalink( get_the_ID() ) ), 'bjm_unsave_job' ) : wp_nonce_url( add_query_arg( 'bjm_save_job', get_the_ID(), get_permalink( get_the_ID() ) ), 'bjm_save_job' ) )
    : wp_login_url( get_permalink( get_the_ID() ) );
?>
<div class="bjm-apply-entry">
<p><a class="button" href="<?php echo esc_url( $save_link ); ?>"><?php echo esc_html( is_user_logged_in() ? ( $is_saved ? 'Remove saved job' : 'Save job' ) : 'Log in to save job' ); ?></a></p>
    <?php if ( $external_apply_url ) : ?>
        <?php $external_tracked_url = wp_nonce_url( add_query_arg( 'bjm_external_apply', get_the_ID(), home_url( '/' ) ), 'bjm_external_apply_' . get_the_ID() ); ?>
        <p><a class="button button-primary" href="<?php echo esc_url( $external_tracked_url ); ?>" target="_blank" rel="noopener noreferrer">Apply on advertiser site</a></p>
    <?php else : ?>
        <p><a class="button button-primary bjm-reveal-apply-form" href="<?php echo esc_url( $apply_link ); ?>" data-target="<?php echo esc_attr( $apply_target_id ); ?>" aria-expanded="<?php echo $show_apply_form ? 'true' : 'false'; ?>">Apply for this job</a></p>
        <div id="<?php echo esc_attr( $apply_target_id ); ?>" class="bjm-apply-form-reveal" <?php echo $show_apply_form ? '' : 'hidden'; ?>>
            <?php echo do_shortcode('[bjm_apply_form]'); ?>
        </div>
    <?php endif; ?>
</div></main>
<?php get_footer(); ?>
