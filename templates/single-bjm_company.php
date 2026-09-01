<?php get_header(); the_post(); ?>
<main class="bjm-container single-company">
<?php $logo_id = (int) get_post_meta( get_the_ID(), '_bjm_company_logo_id', true ); ?>
<div class="bjm-company-hero">
    <div class="bjm-company-logo"><?php if ( $logo_id ) { echo wp_get_attachment_image( $logo_id, 'medium' ); } ?></div>
    <div class="bjm-company-meta">
        <h1><?php the_title(); ?></h1>
        <div><?php the_content(); ?></div>
        <?php $website = get_post_meta( get_the_ID(), '_bjm_company_website', true ); ?>
        <?php $email = get_post_meta( get_the_ID(), '_bjm_company_email', true ); ?>
        <?php $location = get_post_meta( get_the_ID(), '_bjm_company_location', true ); ?>
        <?php if ( $website ) : ?><p><strong>Website:</strong> <a href="<?php echo esc_url( $website ); ?>"><?php echo esc_html( $website ); ?></a></p><?php endif; ?>
        <?php if ( $email ) : ?><p><strong>Email:</strong> <?php echo esc_html( $email ); ?></p><?php endif; ?>
        <?php if ( $location ) : ?><p><strong>Location:</strong> <?php echo esc_html( $location ); ?></p><?php endif; ?>
    </div>
</div>
<div class="bjm-company-jobs">
<h3>Open Jobs</h3>
<?php $jobs = get_posts(array('post_type'=>'job_listing','posts_per_page'=>20,'post_status'=>'publish','meta_key'=>'_bjm_company_id','meta_value'=>get_the_ID()));
if ( $jobs ) { foreach($jobs as $job){ echo '<p><a href="'.esc_url(get_permalink($job)).'">'.esc_html(get_the_title($job)).'</a></p>'; } } else { echo '<p>No open jobs right now.</p>'; } ?>
</div>
</main>
<?php get_footer(); ?>
