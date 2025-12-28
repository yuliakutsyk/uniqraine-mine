<?php
/**
 * Recommended way to include parent theme styles.
 * (Please see http://codex.wordpress.org/Child_Themes#How_to_Create_a_Child_Theme)
 *
 */

//include get_template_directory() . '/filters.php';

// Customize My Account menu items order and labels
add_filter('woocommerce_account_menu_items', 'cris_woo_custom_account_menu_items');
function cris_woo_custom_account_menu_items($items)
{
    // Set desired menu items and order
    return array(
        'edit-account' => 'Особисті дані',
        'orders' => 'Історія замовлень',
        'cgkit-wishlist' => 'Список бажань',
        'customer-logout' => 'Вийти',
    );
}

// Disable some endpoints entirely (produce 404 on direct access)
add_filter('woocommerce_get_query_vars', 'cris_woo_disable_endpoints');
function cris_woo_disable_endpoints($vars)
{
    unset($vars['dashboard']);
    unset($vars['downloads']);
    unset($vars['edit-address']);
    return $vars;
}

add_action('wp_enqueue_scripts', 'shoptimizer_child_style');
function shoptimizer_child_style()
{
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'));
}

add_action('wp_enqueue_scripts', 'shoptimizer_enqueue_styles', 100);
function shoptimizer_enqueue_styles()
{
    wp_enqueue_style('shoptimizer-child-main-style', get_stylesheet_directory_uri() . '/assets/css/ninesquares.css', array(), rand());
    wp_enqueue_script('shoptimizer-child-main-script', get_stylesheet_directory_uri() . '/assets/js/ninesquares.js', array(), rand(), true);
}


/**** віджети під елементор !!!!! ****/

function ninesquares_register_new_widgets_elementor($widgets_manager)
{

    // віджет основний блок карточек товарів
    require_once get_stylesheet_directory() . '/widgets-elementor/ninesquares-widget-products-archive.php';
    $widgets_manager->register(new \Ninesquares_Widget_Products_Archive());

}

add_action('elementor/widgets/register', 'ninesquares_register_new_widgets_elementor');


// Для віджета Ninesquares_Widget_Products_Archive
add_action('wp_enqueue_scripts', function () {
    // потрібні скрипти та стилі
    wp_enqueue_style('ninesquares_widget_products_archive', get_stylesheet_directory_uri() . '/widgets-elementor/ninesquares_widget_products_archive.css', array(), rand());
    wp_enqueue_script('ninesquares_widget_products_archive', get_stylesheet_directory_uri() . '/widgets-elementor/ninesquares_widget_products_archive.js', ['jquery'], rand(), true);
    wp_localize_script('ninesquares_widget_products_archive', 'ninesquares_widget', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ninesquares_widget_products_archive')
    ]);
});

add_action('wp_ajax_ninesquares_widget_products_archive', 'ninesquares_widget_products_archive_func');
add_action('wp_ajax_nopriv_ninesquares_widget_products_archive', 'ninesquares_widget_products_archive_func');
function ninesquares_widget_products_archive_func()
{
    global $wpdb;

    // Перевірка nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ninesquares_widget_products_archive')) {
        wp_die();
    }

    $exclude_cats = [430, 457];

    $posts_per_page = intval($_POST['posts_per_page']);
    $paged = !empty($_POST['page']) ? intval($_POST['page']) : 1;
    $sort = isset($_POST['sort']) ? (string)$_POST['sort'] : 'menu_order';

    // Базовий запит
    $query_arr = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'fields' => 'all',
        'ninesquares_sort' => $sort,
    ];

    if ($sort === 'date') {
        $query_arr['orderby'] = 'date';
        $query_arr['order'] = 'DESC';
    }

    $term_start = [];
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
            if($key == 'product_cat'){
                foreach ($value as $item) {
                    if(!in_array($item, $exclude_cats)) {
                        $term_start[] = get_term($item, 'product_cat');
                    }
                }
            }
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

    if (!empty($_POST['s'])) {
        $search_term = trim(sanitize_text_field($_POST['s']));
        $search_term = mb_substr($search_term, 0, 100);
        $query_arr['s'] = $search_term;
        $query_arr['search_columns'] = ['post_title'];
    }

    // Фільтр для сортування через lookup table
    add_filter('posts_clauses', function ($clauses, $query) use ($wpdb) {
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


    $terms_by_tax = ns_get_attrs_available($query);

    $taxonomy = ns_get_filters_options(
            [ 0 => 'product_cat',
              1 => 'pa_kolir',
              2 => 'pa_rozmir-odyagu',], $term_start, $terms_by_tax);
    $html_filter = ns_render_filter_sidebar($taxonomy, true, false, $term_start);




//    echo '<pre>';
//    print_r( $taxonomy );
//    echo '</pre>';



    $html = ns_render_loop($query, true);

    // Видаляємо фільтри після запиту
    remove_all_filters('posts_clauses');

    wp_send_json_success([
        'html' => $html,
        'max_page' => $query->max_num_pages,
        'res' => $query->request,
        'res2' => $query->posts,
        'res3' => $query_arr,
        'res4' => $query,
        'res5' => $_POST,
        'html_filter' => $html_filter,
    ]);

    wp_die();
}

////////////////////


function ns_render_loop($query, $is_ajax = false)
{
    ob_start();

    if ($query->have_posts()) {
        if (!$is_ajax) woocommerce_product_loop_start();
        while ($query->have_posts()) {
            $query->the_post();
            wc_get_template_part('content', 'product');
        }
        if (!$is_ajax) woocommerce_product_loop_end();
    } else {
        echo '<h2>Результатів не знайдено.</h2>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

function ns_render_filter_sidebar($taxonomy, $is_ajax = false, $is_novynky = false, $term_start_arr = [])
{
    ob_start();
    if (!empty($taxonomy)): ?>
        <?php foreach ($taxonomy as $key => $taxon): ?>
            <div class="item_fillter">
                <div class="item_fillter_response">
                    <span class=""><?php echo $key; ?></span>
                    <ul class=""></ul>
                    <div class="arrow"></div>
                </div>
                <?php if (!empty($taxon)): ?>
                    <ul class="item_fillter_request">
                        <?php foreach ($taxon as $term): ?>
                            <?php
                            //mi($term);
                            $color = false;
                            if ($term->taxonomy === 'pa_kolir') {
                                $color = get_term_meta($term->term_id, 'color', true);
                            }
                            $border = '';
                            if ($term->taxonomy != 'pa_kolir' && $term->taxonomy != 'product_cat') {
                                $border = 'd_text';
                            }
                            ?>
                            <li data-value="<?php echo $term->taxonomy . '||' . $term->term_id; ?>"
                                data-checked="<?php echo(!empty($term_start_arr) && in_array($term->term_id, $term_start_arr) ? 'ok' : 'no'); ?>"
                                class=""><?php echo(!empty($color) ? '<div class="dd_color"><div class="d_color" style="background: ' . $color . ';"></div></div>' : ''); ?>
                                <span class="<?php echo $border; ?>"><?php echo $term->name ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <!--Блок для сортування-->
    <div class="item_sort">
        <div class="item_sort_response">
            <span class="">Сортування</span>
            <ul class="">
                <?php if ( $is_novynky ):?>
                    <li data-value="date">Новинки</li>
                <?php endif; ?>
            </ul>
            <div class="arrow"></div>
        </div>
        <ul class="item_sort_request">
            <li data-value="date" class="<?php if ( $is_novynky ) echo 'active'; ?>">Новинки</li>
            <li data-value="min_price" class="">Спочатку дешевші</li>
            <li data-value="max_price" class="">Спочатку дорожчі</li>
            <!--<li data-value="min_sale_price"  class="">Мінімальна знижка</li>-->
            <!--<li data-value="max_sale_price"  class="">Максимальна знижка</li>-->
        </ul>
    </div>
    <!----------->
    <?php
    return ob_get_clean();
}

function ns_get_filters_options($settings, $term_start = [], $terms_by_tax = []){
//    echo '<pre>';
//    print_r( $settings );
//    echo '</pre>';
//    echo '<pre>';
//    print_r( $term_start );
//    echo '</pre>';
//    echo '<pre>';
//    print_r( $terms_by_tax );
//    echo '</pre>';
    $taxonomy = array();

    foreach( $settings as $value ){
        $tax_obj = get_taxonomy( $value );
        if ( ! $tax_obj ) { continue; }
        if($value === 'product_cat'){
            // якщо сторінка однієї з категорій то треба показати лише цю категорію та її дочірні
            if(!empty($term_start)){
                $children = get_terms(array( 'taxonomy' => $value, 'child_of' => $term_start->term_id, 'hide_empty' => false));
                $taxonomy[ $tax_obj->labels->singular_name] = array_merge( array( $term_start ), $children);
            }else{
                $taxonomy[ $tax_obj->labels->singular_name] = get_terms(array( 'taxonomy' => $value, 'exclude' => 1));
            }

        }else{
            // атрибути: тільки те, що реально зустрілось у продуктах результату
            $terms = ! empty( $terms_by_tax[ $value ] )
                ? array_values( $terms_by_tax[ $value]) : array();

            $taxonomy[$tax_obj->labels->singular_name] = $terms;
        }
    }
    return $taxonomy;
}

function ns_get_attrs_available($query){
    $product_ids = wp_list_pluck( $query->posts, 'ID' );

    $terms_by_tax = []; // 'pa_kolir' => [term_id => WP_Term, ...], 'pa_rozmir' => ...

    foreach ( $product_ids as $pid ) {
        $product = wc_get_product( $pid );
        if ( ! $product ) {
            continue;
        }

        foreach ( $product->get_attributes() as $attr ) {
            if ( ! $attr->is_taxonomy() ) {
                continue;
            }

            $tax = $attr->get_name(); // напр. 'pa_kolir', 'pa_rozmir'

            // всі терми цього атрибута для продукту
            $terms = wc_get_product_terms( $pid, $tax, [ 'fields' => 'all' ] );

            foreach ( $terms as $term ) {
                // зберігаємо унікальні терми по кожній таксономії
                if ( ! isset( $terms_by_tax[ $tax ][ $term->term_id ] ) ) {
                    $terms_by_tax[ $tax ][ $term->term_id ] = $term;
                }
            }
        }
    }
    return $terms_by_tax;
}








function define_selected_categories($settings) {
    if (isset($_GET['category'])) {
        return (array) $_GET['category'];
    }

    $return = [];
    if (!empty($settings['term_start'])) {
        foreach ($settings['term_start'] as $term) {
//            echo '<pre>';
//            var_dump($term);
//            var_dump(explode('||', $term));
//            echo '</pre>';
            $return[] = explode('||', $term)[1];
        }
        return $return;
    }

    if (is_product_taxonomy()) {
        return [get_queried_object()->term_id];
    }

    return [];
}

function define_selected_attributes($attributes) {
    unset($attributes['product_cat']);

    $attrs = [];
    foreach ($attributes as $taxonomy => $terms) {
        if (isset($_GET[$taxonomy])) {
            $attrs[$taxonomy] = (array) $_GET[$taxonomy];
        }
    }

    return $attrs;
}


function remove_exclude_categories($categories_array_ids){
    $exclude = ['430'];
    foreach ($categories_array_ids as $cat_id) {
        if(in_array($cat_id, $exclude)){
            unset($categories_array_ids[$cat_id]);
        }
    }
    return $categories_array_ids;
}

function define_categories_list_for_filter($categories_array_ids) {
    $has_any_child = false;
    $exclude = ['430'];
    $categories = [];

    if(!empty($categories_array_ids)) {
        foreach ($categories_array_ids as $cat_id) {
//            echo '<pre>';
//            var_dump($cat_id);
//            echo '</pre>';
            if ($children = get_terms(['taxonomy' => 'product_cat', 'parent' => $cat_id, 'hide_empty' => true])) {
                $categories = array_merge($categories, $children);
                $has_any_child = true;
            }

            if (!$has_any_child) {
                $cat_obj = get_term($cat_id, 'product_cat');

                if ($siblings = get_terms(['taxonomy' => 'product_cat', 'parent' => $cat_obj->parent, 'hide_empty' => true])) {
                    $categories = array_merge($categories, $siblings);
                }
            }
        }
    }

    if (empty($categories)) {
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'exclude' => $exclude]);
    }

    return ['Категорія' => $categories];
}

function define_attrs_lists_for_filter($categories_array_ids) {
    global $wpdb;

    if (empty($categories_array_ids)) {
        return [];
    }

    $categories_array_ids = array_map('absint', (array) $categories_array_ids);
    $placeholders = implode(',', array_fill(0, count($categories_array_ids), '%d'));

    $query = $wpdb->prepare("
        SELECT DISTINCT pal.taxonomy, pal.term_id
        FROM {$wpdb->prefix}wc_product_attributes_lookup AS pal
        INNER JOIN {$wpdb->prefix}term_relationships AS tr 
            ON pal.product_or_parent_id = tr.object_id
        INNER JOIN {$wpdb->prefix}term_taxonomy AS tt 
            ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE tt.taxonomy = 'product_cat'
          AND tt.term_id IN ($placeholders)
          AND pal.is_variation_attribute = 1
    ", $categories_array_ids);

    $results = $wpdb->get_results($query);

    $attributes = [];
    foreach ($results as $row) {
        $term = get_term($row->term_id, $row->taxonomy);
        if ($term && !is_wp_error($term)) {
            $taxonomy_obj = get_taxonomy($row->taxonomy);
            $taxonomy_name = $taxonomy_obj ? $taxonomy_obj->labels->singular_name : $row->taxonomy;

            if (!isset($attributes[$taxonomy_name])) {
                $attributes[$taxonomy_name] = [];
            }
            $attributes[$taxonomy_name][] = $term;
        }
    }

    return $attributes;
}

function prepare_query_tax_args($selected_categories, $selected_attributes) {
    $tax_query = ['relation' => 'AND'];

    if (!empty($selected_categories)) {
        $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $selected_categories,
                'operator' => 'IN'
        ];
    }

    foreach ($selected_attributes as $taxonomy => $term_ids) {
        if (!empty($term_ids)) {
            $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term_ids,
                    'operator' => 'IN'
            ];
        }
    }

    return count($tax_query) > 1 ? $tax_query : [];
}
