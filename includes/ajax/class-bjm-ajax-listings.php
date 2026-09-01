<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BJM_Ajax_Listings {
    public static function init() {
        add_shortcode( 'bjm_jobs', array( __CLASS__, 'render' ) );
        add_action( 'wp_ajax_bjm_filter_jobs', array( __CLASS__, 'filter' ) );
        add_action( 'wp_ajax_nopriv_bjm_filter_jobs', array( __CLASS__, 'filter' ) );
    }

    public static function render( $atts = array() ) {
        $atts = shortcode_atts( array(
            'per_page' => bjm_get_jobs_per_page( 12 ), 'featured_only' => '', 'show_filters' => '1', 'work_mode' => '', 'category' => '', 'type' => '', 'region' => '', 'orderby' => 'date', 'order' => 'DESC', 'show_excerpt' => '1',
        ), $atts, 'bjm_jobs' );
        $per_page = max( 1, absint( $atts['per_page'] ) );
        $show_filters = (string) $atts['show_filters'] !== '0';
        $defaults = array(
            'keyword' => '', 'featured' => (string) $atts['featured_only'] === '1' ? '1' : '', 'work_mode' => sanitize_key( $atts['work_mode'] ),
            'category' => sanitize_title( $atts['category'] ), 'type' => sanitize_title( $atts['type'] ), 'region' => sanitize_title( $atts['region'] ),
            'orderby' => in_array( $atts['orderby'], array( 'date', 'title' ), true ) ? $atts['orderby'] : 'date', 'order' => strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC',
            'show_excerpt' => (string) $atts['show_excerpt'] !== '0' ? '1' : '0', 'paged' => 1,
        );
        ob_start();
        echo '<div class="bjm-jobs-wrap" data-per-page="' . esc_attr( $per_page ) . '" data-defaults="' . esc_attr( wp_json_encode( $defaults ) ) . '">';
        if ( $show_filters ) { echo self::render_filters( $defaults ); }
        echo '<div class="bjm-jobs-results">';
        echo self::get_jobs_html( array_merge( $defaults, array( 'per_page' => $per_page ) ) );
        echo '</div></div>';
        return ob_get_clean();
    }

    private static function render_filters( $defaults ) {
        $categories = bjm_get_available_job_categories();
        $types = get_terms( array( 'taxonomy' => 'job_type', 'hide_empty' => true ) );
        $regions = get_terms( array( 'taxonomy' => 'job_location_region', 'hide_empty' => true ) );
        ob_start();
        echo '<form class="bjm-jobs-form">';
        echo '<input type="text" name="keyword" placeholder="Keyword" value="' . esc_attr( $defaults['keyword'] ) . '">';
        echo '<select name="category"><option value="">All categories</option>';
        foreach ( $categories as $term ) { echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $defaults['category'], $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>'; }
        echo '</select><select name="type"><option value="">All types</option>';
        foreach ( $types as $term ) { echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $defaults['type'], $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>'; }
        echo '</select><select name="region"><option value="">All regions</option>';
        foreach ( $regions as $term ) { echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $defaults['region'], $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>'; }
        echo '</select><select name="work_mode"><option value="">Any work mode</option>';
        foreach ( array( 'onsite'=>'Onsite','hybrid'=>'Hybrid','remote'=>'Remote' ) as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $defaults['work_mode'], $key, false ) . '>' . esc_html( $label ) . '</option>'; }
        echo '</select><select name="featured"><option value="">All jobs</option><option value="1" ' . selected( $defaults['featured'], '1', false ) . '>Featured only</option></select>';
        echo '<select name="orderby"><option value="date" ' . selected( $defaults['orderby'], 'date', false ) . '>Newest</option><option value="title" ' . selected( $defaults['orderby'], 'title', false ) . '>Title</option></select>';
        echo '<button type="submit">Search</button><button type="button" class="bjm-reset-filters">Reset</button></form>';
        return ob_get_clean();
    }

    public static function filter() {
        check_ajax_referer( 'bjm_nonce', 'nonce' );
        $args = array(
            'keyword' => sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) ), 'featured' => sanitize_text_field( wp_unslash( $_POST['featured'] ?? '' ) ),
            'work_mode' => sanitize_key( wp_unslash( $_POST['work_mode'] ?? '' ) ), 'category' => sanitize_title( wp_unslash( $_POST['category'] ?? '' ) ),
            'type' => sanitize_title( wp_unslash( $_POST['type'] ?? '' ) ), 'region' => sanitize_title( wp_unslash( $_POST['region'] ?? '' ) ),
            'orderby' => in_array( wp_unslash( $_POST['orderby'] ?? '' ), array( 'date', 'title' ), true ) ? wp_unslash( $_POST['orderby'] ) : 'date',
            'order' => strtoupper( sanitize_text_field( wp_unslash( $_POST['order'] ?? 'DESC' ) ) ) === 'ASC' ? 'ASC' : 'DESC',
            'show_excerpt' => sanitize_text_field( wp_unslash( $_POST['show_excerpt'] ?? '1' ) ) === '0' ? '0' : '1',
            'per_page' => max( 1, absint( $_POST['per_page'] ?? bjm_get_jobs_per_page( 12 ) ) ), 'paged' => max( 1, absint( $_POST['paged'] ?? 1 ) ),
        );
        wp_send_json_success( array( 'html' => self::get_jobs_html( $args ) ) );
    }

    public static function get_jobs_html( $args = array() ) {
        $args = wp_parse_args( $args, array( 'keyword'=>'','featured'=>'','work_mode'=>'','category'=>'','type'=>'','region'=>'','orderby'=>'date','order'=>'DESC','show_excerpt'=>'1','per_page'=>bjm_get_jobs_per_page(12),'paged'=>1 ) );
        $meta_query = array( 'relation'=>'AND', array( 'relation'=>'OR', array( 'key'=>'_bjm_archived','compare'=>'NOT EXISTS' ), array( 'key'=>'_bjm_archived','value'=>'1','compare'=>'!=' ) ) );
        if ( '1' === (string) $args['featured'] ) { $meta_query[] = array( 'key'=>'_bjm_featured','value'=>'1' ); }
        if ( ! empty( $args['work_mode'] ) ) { $meta_query[] = array( 'key'=>'_bjm_work_mode','value'=>sanitize_key($args['work_mode']) ); }
        $tax_query = array();
        foreach ( array( 'category'=>'job_category','type'=>'job_type','region'=>'job_location_region' ) as $key=>$taxonomy ) {
            if ( ! empty( $args[$key] ) ) { $tax_query[] = array( 'taxonomy'=>$taxonomy,'field'=>'slug','terms'=>sanitize_title($args[$key]) ); }
        }
        if ( count( $tax_query ) > 1 ) { $tax_query['relation'] = 'AND'; }
        $query_args = array( 'post_type'=>'job_listing','post_status'=>'publish','posts_per_page'=>max(1,absint($args['per_page'])),'paged'=>max(1,absint($args['paged'])),'s'=>sanitize_text_field($args['keyword']),'meta_query'=>$meta_query,'orderby'=>$args['orderby'],'order'=>$args['order'] );
        if ( ! empty( $tax_query ) ) { $query_args['tax_query'] = $tax_query; }
        $query = new WP_Query( $query_args );
        ob_start();
        if ( $query->have_posts() ) {
            echo '<div class="bjm-job-grid">';
            while ( $query->have_posts() ) {
                $query->the_post(); $job_id=get_the_ID(); $company=get_post_meta($job_id,'_bjm_company_name',true); $location=get_post_meta($job_id,'_bjm_location_text',true); $work_mode=get_post_meta($job_id,'_bjm_work_mode',true);
                echo '<article class="bjm-job-card"><div class="bjm-card-head"><h4><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h4>';
                if ( get_post_meta($job_id,'_bjm_featured',true) ) { echo '<span class="bjm-pill">Featured</span>'; }
                echo '</div><div class="meta">' . esc_html($company); if($company&&$location){echo ' · ';} echo esc_html($location) . '</div><div class="bjm-job-meta-row">';
                if($work_mode){echo '<span class="bjm-chip">'.esc_html(ucfirst($work_mode)).'</span>';}
                $type_terms=get_the_terms($job_id,'job_type'); if($type_terms&&!is_wp_error($type_terms)){echo '<span class="bjm-chip">'.esc_html($type_terms[0]->name).'</span>';}
                echo '</div>'; if('1'===(string)$args['show_excerpt']){echo '<div class="bjm-excerpt">'.esc_html(wp_trim_words(wp_strip_all_tags(get_the_excerpt()?get_the_excerpt():get_the_content()),24)).'</div>';}
                echo '<div class="bjm-card-actions"><a class="button" href="'.esc_url(get_permalink($job_id)).'">View job</a>';
                if(is_user_logged_in()){ $saved=bjm_is_job_saved($job_id); $link=$saved?wp_nonce_url(add_query_arg('bjm_unsave_job',$job_id,get_permalink($job_id)),'bjm_unsave_job'):wp_nonce_url(add_query_arg('bjm_save_job',$job_id,get_permalink($job_id)),'bjm_save_job'); echo ' <a class="button" href="'.esc_url($link).'">'.esc_html($saved?'Saved':'Save job').'</a>'; }
                echo '</div></article>';
            }
            echo '</div>'; $current_page=max(1,absint($args['paged']));
            if($query->max_num_pages>1){echo '<div class="bjm-pagination" data-total-pages="'.esc_attr($query->max_num_pages).'">';for($i=1;$i<=(int)$query->max_num_pages;$i++){ $classes='page-numbers bjm-page-link'.($i===$current_page?' current':''); echo '<a href="#" class="'.esc_attr($classes).'" data-page="'.esc_attr($i).'">'.esc_html($i).'</a>'; }echo '</div>';}
            wp_reset_postdata();
        } else { echo '<p>No jobs found.</p>'; }
        return ob_get_clean();
    }
}
