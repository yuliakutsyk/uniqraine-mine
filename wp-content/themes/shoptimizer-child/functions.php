<?php
/**
 * Recommended way to include parent theme styles.
 * (Please see http://codex.wordpress.org/Child_Themes#How_to_Create_a_Child_Theme)
 *
*/

// Customize My Account menu items order and labels
add_filter( 'woocommerce_account_menu_items', 'cris_woo_custom_account_menu_items' );
function cris_woo_custom_account_menu_items( $items ) {
	// Set desired menu items and order
	return array(
		'edit-account'    => 'Особисті дані',
		'orders'          => 'Історія замовлень',
		'cgkit-wishlist'  => 'Список бажань',
		'customer-logout' => 'Вийти',
	);
}

// Disable some endpoints entirely (produce 404 on direct access)
add_filter( 'woocommerce_get_query_vars', 'cris_woo_disable_endpoints' );
function cris_woo_disable_endpoints( $vars ) {
	unset( $vars['dashboard'] );
	unset( $vars['downloads'] );
	unset( $vars['edit-address'] );
	return $vars;
}

add_action( 'wp_enqueue_scripts', 'shoptimizer_child_style' );
				function shoptimizer_child_style() {
					wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
					wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style') );
				}

add_action( 'wp_enqueue_scripts', 'shoptimizer_enqueue_styles', 100 );
function shoptimizer_enqueue_styles() {
	wp_enqueue_style( 'shoptimizer-child-main-style',  get_stylesheet_directory_uri() . '/assets/css/ninesquares.css', array(), rand() );
	wp_enqueue_script( 'shoptimizer-child-main-script',  get_stylesheet_directory_uri() . '/assets/js/ninesquares.js', array(), rand(), true );
}


/**** віджети під елементор !!!!! ****/

	function ninesquares_register_new_widgets_elementor( $widgets_manager ) {

		// віджет основний блок карточек товарів
		require_once get_stylesheet_directory() .  '/widgets-elementor/ninesquares-widget-products-archive.php';
		$widgets_manager->register( new \Ninesquares_Widget_Products_Archive() );

	}
	add_action( 'elementor/widgets/register', 'ninesquares_register_new_widgets_elementor' );


	// Для віджета Ninesquares_Widget_Products_Archive
	add_action( 'wp_enqueue_scripts', function() {
		// потрібні скрипти та стилі
		wp_enqueue_style( 'ninesquares_widget_products_archive', get_stylesheet_directory_uri() . '/widgets-elementor/ninesquares_widget_products_archive.css', array(), rand());
		wp_enqueue_script( 'ninesquares_widget_products_archive', get_stylesheet_directory_uri() . '/widgets-elementor/ninesquares_widget_products_archive.js', ['jquery'], rand(), true);
		wp_localize_script( 'ninesquares_widget_products_archive', 'ninesquares_widget', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce('ninesquares_widget_products_archive')
		] );
	});
	add_action('wp_ajax_ninesquares_widget_products_archive', 'ninesquares_widget_products_archive_func');
	add_action('wp_ajax_nopriv_ninesquares_widget_products_archive', 'ninesquares_widget_products_archive_func');
	function ninesquares_widget_products_archive_func() {
		global $wpdb;

		// Перевірка nonce
		if (!wp_verify_nonce($_POST['nonce'], 'ninesquares_widget_products_archive')) {
			wp_die();
		}

		$posts_per_page = intval($_POST['posts_per_page']);
		$paged = !empty($_POST['page']) ? intval($_POST['page']) : 1;
		$sort = isset( $_POST['sort'] ) ? (string) $_POST['sort'] : 'menu_order';

        // Базовий запит
        $query_arr = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'fields'         => 'all',
            'ninesquares_sort' => $sort,
        ];

        if ( $sort === 'date' ) {
            $query_arr['orderby'] = 'date';
            $query_arr['order']   = 'DESC';
        }

		// Таксономії
		$terms = $_POST['term'] ?? [];
		if (!empty($terms)) {
			// підготовчий масив для того щоб терми однієї таксономії потрапили в одну умову
			$arr_new_terms = [];
			foreach ($terms as $term) {
				$arr = explode("||", $term);
				$arr_new_terms[$arr[0]][] = $arr[1];
			}
			// формую умову
			$tax_query = [];
			foreach ($arr_new_terms as $key => $value) {
				$tax_query[] = [
					'taxonomy' => $key,
					'field' => 'id',
					'terms' => $value,
					'include_children' => false, // не чіпати дочірні категорії
					'operator' => 'AND'
				];
			}
			if (!empty($tax_query) && count($tax_query) > 1) {
                $tax_query = [
                        'relation' => 'AND',
                    ] + $tax_query;
            }
    
            $query_arr['tax_query'] = $tax_query;
		}
		
		if ( !empty($_POST['s']) ) {
            $search_term = trim( sanitize_text_field($_POST['s']) );
            $search_term = mb_substr($search_term, 0, 100);
            $query_arr['s'] = $search_term;
            $query_arr['search_columns'] = ['post_title'];
        }

		// Фільтр для сортування через lookup table
        add_filter('posts_clauses', function($clauses, $query) use ($wpdb) {
            $sort = $query->get('ninesquares_sort');

            if (in_array($sort, ['min_price', 'max_price', 'min_sale_price', 'max_sale_price'])) {
                // JOIN lookup table
                if (false === strpos($clauses['join'], "wc_product_meta_lookup")) {
                    $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup wcpl ON {$wpdb->posts}.ID = wcpl.product_id ";
                }

                // Сортування по різним полям
                switch ($sort) {
                    case 'min_price':
                        $clauses['orderby'] = " wcpl.min_price IS NULL, wcpl.min_price ASC ";
                        break;

                    case 'max_price':
                        $clauses['orderby'] = " wcpl.max_price IS NULL, wcpl.max_price DESC ";
                        break;

                    case 'min_sale_price':
                        $clauses['orderby'] = " wcpl.total_sales IS NULL, wcpl.total_sales ASC ";
                        break;

                    case 'max_sale_price':
                        $clauses['orderby'] = " wcpl.total_sales IS NULL, wcpl.total_sales DESC ";
                        break;
                }

                // DISTINCT для уникнення дублікатів
                if (false === strpos($clauses['distinct'], 'DISTINCT')) {
                    $clauses['distinct'] = 'DISTINCT';
                }
            }

            return $clauses;
        }, 10, 2);

		// Виконання запиту
		$query = new WP_Query($query_arr);

		ob_start();

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				wc_get_template_part('content', 'product');
			}
		} else {
			//wc_get_template('loop/no-products-found.php');
			echo '<h2>Результатів не знайдено.</h2>';
		}

		wp_reset_postdata();

		// Видаляємо фільтри після запиту
		remove_all_filters('posts_clauses');

		$html = ob_get_clean();

		wp_send_json_success([
			'html' => $html,
			'max_page' => $query->max_num_pages,
			'res' => $query->request,
			'res2' => $query->posts,
			'res3' => $query_arr,
			'res4' => $query,
			'res5' => $_POST,
		]);

		wp_die();
	}
	////////////////////

/****  ****/
// Мінікошик Shoptimizer
function ns_shoptimizer_header_cart_shortcode() {
	if ( function_exists( 'shoptimizer_header_cart' ) ) {
		ob_start();
		shoptimizer_header_cart();
		return ob_get_clean();
	}

	return '';
}
add_shortcode( 'shoptimizer_header_cart', 'ns_shoptimizer_header_cart_shortcode' );

// Пошук Shoptimizer

function ns_shoptimizer_product_search_shortcode() {
	if ( function_exists( 'shoptimizer_product_search' ) ) {
		ob_start();
		shoptimizer_product_search();
		return ob_get_clean();
	}

	return '';
}
add_shortcode( 'shoptimizer_product_search', 'ns_shoptimizer_product_search_shortcode' );

// Лого / брендінг
function ns_shoptimizer_site_branding_shortcode() {
	if ( function_exists( 'shoptimizer_site_branding' ) ) {
		ob_start();
		shoptimizer_site_branding();
		return ob_get_clean();
	}

	return '';
}
add_shortcode( 'shoptimizer_site_branding', 'ns_shoptimizer_site_branding_shortcode' );

add_action('woocommerce_before_shop_loop_item_title', function(){
    echo do_shortcode('[wpclv]');
    echo do_shortcode('[wpcvs_archive hide="kolir"]');
}, 99);

add_action( 'woocommerce_after_add_to_cart_quantity', function(){ echo do_shortcode('[commercekit_sizeguide]'); }, 1 );

add_filter('woocommerce_reset_variations_link', '__return_empty_string');

add_shortcode('uniq-related-products', function() {
    global $product;
    if (is_a($product, 'WC_Product')) {
        $shortcode = '[wt-related-products product_id=' . $product->get_id() . ']';
        return do_shortcode($shortcode);
    }
    return '';
});

add_action( 'template_redirect', function() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Не чіпаємо кастомний wishlist-ендпоінт
    if ( is_account_page() && isset( $_SERVER['REQUEST_URI'] ) && str_contains( $_SERVER['REQUEST_URI'], 'my-account/cgkit-wishlist' ) ) {
        return;
    }

    // Єдина сторінка "My account" без ендпоінтів
    if ( is_account_page() && ! is_wc_endpoint_url() ) {
        wp_safe_redirect( wc_get_account_endpoint_url( 'edit-account' ) );
        exit;
    }
} );

add_filter( 'woocommerce_login_redirect', function( $redirect, $user ) {
    return wc_get_account_endpoint_url( 'edit-account' );
}, 10, 2 );

add_filter( 'woocommerce_registration_redirect', function( $redirect ) {
    return wc_get_account_endpoint_url( 'edit-account' );
}, 10, 1 );

add_action( 'woocommerce_customer_reset_password', function( $user ) {
    wp_safe_redirect( wc_get_account_endpoint_url( 'edit-account' ) );
    exit;
}, 10, 1 );

add_action('woocommerce_checkout_process', 'uniq_validate_billing_phone', 9999);

function uniq_validate_billing_phone() {
    $phone = isset($_POST['billing_phone']) ? $_POST['billing_phone'] : '';
    $digits_only = preg_replace('/\D+/', '', $phone);
    if ($digits_only && strlen($digits_only) != 12) {
        wc_add_notice(__('Телефон має містити 12 цифр.', 'woocommerce'), 'error');
    }
}

add_filter( 'woocommerce_checkout_fields', 'uniq_remove_billing_company_field' );

function uniq_remove_billing_company_field( $fields ) {
    if ( isset( $fields['billing']['billing_company'] ) ) {
        unset( $fields['billing']['billing_company'] );
    }
    return $fields;
}
