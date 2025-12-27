<?php

// ручне перебудування таблиці пошуку товарів !!! 
define('WP_USE_THEMES', false);
require_once __DIR__ . '/wp-load.php';

global $wpdb;

$table_name = $wpdb->prefix . 'wc_product_meta_lookup';

// Видаляємо таблицю
echo "Видаляємо таблицю {$table_name}...\n";
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Створюємо таблицю заново
echo "Створюємо таблицю {$table_name}...\n";
$create_sql = "
CREATE TABLE `{$table_name}` (
  `product_id` bigint(20) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `virtual` tinyint(1) DEFAULT 0,
  `downloadable` tinyint(1) DEFAULT 0,
  `min_price` decimal(19,4) DEFAULT NULL,
  `max_price` decimal(19,4) DEFAULT NULL,
  `onsale` tinyint(1) DEFAULT 0,
  `stock_quantity` double DEFAULT NULL,
  `stock_status` varchar(100) DEFAULT 'instock',
  `rating_count` bigint(20) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `total_sales` bigint(20) DEFAULT 0,
  `tax_status` varchar(100) DEFAULT 'taxable',
  `tax_class` varchar(100) DEFAULT NULL,
  `global_unique_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  KEY `sku` (`sku`),
  KEY `virtual` (`virtual`),
  KEY `downloadable` (`downloadable`),
  KEY `min_price` (`min_price`),
  KEY `max_price` (`max_price`),
  KEY `onsale` (`onsale`),
  KEY `stock_quantity` (`stock_quantity`),
  KEY `stock_status` (`stock_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
";
$wpdb->query($create_sql);

// Отримуємо всі продукти
echo "Отримуємо всі продукти...\n";
$products = $wpdb->get_results("
    SELECT ID 
    FROM {$wpdb->prefix}posts 
    WHERE post_type IN ('product', 'product_variation') 
      AND post_status = 'publish'
");

echo "Знайдено " . count($products) . " продуктів.\n";

// Перебираємо всі продукти
foreach ($products as $p) {
    $product_id = $p->ID;

    // SKU та атрибути
    $sku = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_sku'", $product_id
    ));
    $virtual = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_virtual'", $product_id
    )) ?: 0;
    $downloadable = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_downloadable'", $product_id
    )) ?: 0;

    $stock_quantity = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_stock'", $product_id
    ));
    $stock_status = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_stock_status'", $product_id
    )) ?: 'instock';

    $rating_count = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_wc_review_count'", $product_id
    )) ?: 0;

    $average_rating = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_wc_average_rating'", $product_id
    )) ?: 0;

    $total_sales = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='total_sales'", $product_id
    )) ?: 0;

    $tax_status = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_tax_status'", $product_id
    )) ?: 'taxable';

    $tax_class = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_tax_class'", $product_id
    ));

    $global_unique_id = 'product_' . $product_id;

    // Визначаємо активні ціни
    $min_price = null;
    $max_price = null;
    $onsale = 0;

    $is_parent = $wpdb->get_var($wpdb->prepare(
        "SELECT post_parent FROM {$wpdb->prefix}posts WHERE ID=%d", $product_id
    )) == 0;

    $prices = [];
    $onsale_flag = 0;

    if ($is_parent) {
        $variations = $wpdb->get_results($wpdb->prepare(
            "SELECT ID FROM {$wpdb->prefix}posts WHERE post_parent=%d AND post_type='product_variation'", $product_id
        ));

        if ($variations) {
            foreach ($variations as $v) {
                $var_stock_status = $wpdb->get_var($wpdb->prepare(
                    "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_stock_status'", $v->ID
                ));
                
                // ПРОПУСКАЄМО out-of-stock варіації
                if ($var_stock_status !== 'instock') {
                    continue;
                }
                $reg = floatval($wpdb->get_var($wpdb->prepare(
                    "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_regular_price'", $v->ID
                )));
                $sale = floatval($wpdb->get_var($wpdb->prepare(
                    "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_sale_price'", $v->ID
                )));
                $active_price = ($sale > 0 && $sale < $reg) ? $sale : $reg;
                if ($active_price > 0) $prices[] = $active_price;

                if ($sale > 0 && $sale < $reg) $onsale_flag = 1;
            }
        }
    }

    // Простий продукт або батьківський без варіацій
    if (empty($prices)) {
        $reg = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_regular_price'", $product_id
        )));
        $sale = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id=%d AND meta_key='_sale_price'", $product_id
        )));
        $active_price = ($sale > 0 && $sale < $reg) ? $sale : $reg;
        if ($active_price > 0) $prices[] = $active_price;

        if ($sale > 0 && $sale < $reg) $onsale_flag = 1;
    }

    $min_price = !empty($prices) ? min($prices) : 1.00;
    $max_price = !empty($prices) ? max($prices) : 1.00;
    $onsale = $onsale_flag;

    // Вставляємо в таблицю
    $wpdb->insert($table_name, [
        'product_id' => $product_id,
        'sku' => $sku,
        'virtual' => $virtual,
        'downloadable' => $downloadable,
        'min_price' => $min_price,
        'max_price' => $max_price,
        'onsale' => $onsale,
        'stock_quantity' => $stock_quantity,
        'stock_status' => $stock_status,
        'rating_count' => $rating_count,
        'average_rating' => $average_rating,
        'total_sales' => $total_sales,
        'tax_status' => $tax_status,
        'tax_class' => $tax_class,
        'global_unique_id' => $global_unique_id
    ]);
}

echo "Готово! Таблиця {$table_name} повністю заповнена.\n";



/*
DELETE FROM wp_wc_product_meta_lookup
WHERE min_price IS NULL OR max_price IS NULL OR min_price = 0 OR max_price = 0;

*/
/*
SELECT product_id FROM wp_wc_product_meta_lookup WHERE min_price IS NULL OR max_price IS NULL OR min_price = 0 OR max_price = 0;
*/