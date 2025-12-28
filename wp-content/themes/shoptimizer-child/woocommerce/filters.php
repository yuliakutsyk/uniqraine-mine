<?php

// Display product filters based on ACF settings
function cris_display_product_filters($filter_parameters) {
    if ( ! $filter_parameters['filter_taxonomies'] ) {
        return;
    }

    foreach ( $filter_parameters['filter_taxonomies'] as $filter ) {
        switch ( $filter ) {
            case 'product_cat':
                cris_render_filter_block( __( 'Category', 'cris' ), cris_get_category_terms( $excluded_items ) );
                break;
            case 'pa_kolir':
                cris_render_filter_block( __( 'Color', 'cris' ), cris_get_attribute_terms( 'pa_color', $excluded_items ) );
                break;
            case 'pa_rozmir-odyagu':
                cris_render_filter_block( __( 'Size Top', 'cris' ), cris_get_attribute_terms( 'pa_size-top', $excluded_items ) );
                break;
        }
    }
}

// Render filter block HTML
function cris_render_filter_block( $title, $options ) {
    if ( empty( $options ) ) {
        return;
    }

    echo '<div class="filter-form__block">';
    echo '<div class="filter-form__header">' . esc_html( $title ) . '</div>';

    foreach ( $options as $option ) {
        $is_checked = cris_is_filter_checked( $option['name'], $option['value'] );

        echo '<div class="default-form__checkbox">';
        echo '<label class="' . ( $is_checked ? '_checked' : '' ) . '">';
        echo '<input type="checkbox" name="' . esc_attr( $option['name'] ) . '" value="' . esc_attr( $option['value'] ) . '">';
        echo esc_html( $option['label'] );
        echo '</label>';
        echo '</div>';
    }

    echo '</div>';
}

// Get excluded term IDs from ACF field
function cris_get_excluded_ids( $excluded_items ) {
    if ( empty( $excluded_items ) ) {
        return array();
    }

    return array_map(
        function ( $item ) {
            return is_object( $item ) ? $item->term_id : $item;
        },
        $excluded_items
    );
}

// Get category terms for filter
function cris_get_category_terms( $excluded_items ) {
    $terms = get_terms(
        array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'exclude'    => array_merge( cris_get_excluded_ids( $excluded_items ), array( get_option( 'default_product_cat' ) ) ),
        )
    );

    if ( is_wp_error( $terms ) || ! $terms ) {
        return array();
    }

    return array_map(
        function ( $term ) {
            return array(
                'name'  => 'product_cat',
                'value' => $term->slug,
                'label' => $term->name,
            );
        },
        $terms
    );
}

// Get product attrs for filter
function cris_get_attribute_terms( $taxonomy, $excluded_items ) {
    $terms = get_terms(
        array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
            'exclude'    => cris_get_excluded_ids( $excluded_items ),
        )
    );

    if ( is_wp_error( $terms ) || ! $terms ) {
        return array();
    }

    return array_map(
        function ( $term ) use ( $taxonomy ) {
            return array(
                'name'  => $taxonomy,
                'value' => $term->slug,
                'label' => $term->name,
            );
        },
        $terms
    );
}





add_action( 'wp_ajax_cris_product_filter', 'cris_handle_product_filter' );
add_action( 'wp_ajax_nopriv_cris_product_filter', 'cris_handle_product_filter' );

// Handle AJAX product filter request
function cris_handle_product_filter() {
    $filter_data = $_POST['filter_data'] ?? '';
    parse_str( $filter_data, $filters );

    $query = cris_apply_filters($filters);
    if( $query && $query->have_posts() ) {

        ob_start();
        include locate_template('blocks/products-list/index.php');
        $return['loop'] = ob_get_clean();

        global $wp_query;
        $temp_query = $wp_query;
        $wp_query = $query;

        ob_start();
        get_template_part('woocommerce/loop/pagination');
        $return['nav'] = ob_get_clean();

        wp_reset_postdata();

    } else {
        $return['loop'] = '';
        $return['nav'] = '';
    }

    wp_reset_postdata();
    wp_send_json_success($return);
}

add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query()) {
        if ($query->is_post_type_archive('product') || $query->is_tax('product_cat') || $query->is_tax('product_tag') || $query->is_tax('collection')) {
            $filters = $_GET ?? '';
            cris_apply_filters($filters, $query);
        }
    }
});

function cris_prepare_filter_parameters( $filters ) {
    foreach ( $filters as $key => $value ) {
        if ( is_string( $value ) && strpos( $value, ',' ) !== false ) {
            $filters[$key] = explode( ',', $value );
        } else {
            $filters[$key] = (array) $value;
        }
    }

    $tax_query = [];

    foreach ( $filters as $taxonomy => $terms ) {
        if ( ! empty( $terms ) && strpos( $taxonomy, 'pa_' ) === 0 ) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $terms,
                'operator' => 'IN',
            ];
        }
    }

    return [ 'tax_query' => $tax_query ];
}


function cris_apply_filters($filters, $query = null) {
    $args = cris_prepare_filter_parameters($filters);

    if ($query) {
        $query->set('tax_query', $args['tax_query']);
    } else {
        $args_full = [
            'post_type'   => 'product',
            'post_status' => 'publish',
            'tax_query'   => $args['tax_query'],
        ];
        $query = new WP_Query($args_full);
    }

    return $query;
}

//Check if filter value is active in URL
function cris_is_filter_checked( $name, $value ) {
    if ( ! isset( $_GET[ $name ] ) ) {
        return false;
    }

    $url_value = $_GET[ $name ];

    // Handle comma-separated values
    if ( is_string( $url_value ) && strpos( $url_value, ',' ) !== false ) {
        $values = explode( ',', $url_value );
        return in_array( $value, $values, true );
    }

    // Handle array values
    if ( is_array( $url_value ) ) {
        return in_array( $value, $url_value, true );
    }

    // Handle single value
    return $url_value === $value;
}