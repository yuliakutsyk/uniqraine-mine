<?php
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Ninesquares_Widget_Products_Archive extends Widget_Base {

    public function get_name(): string {
		return 'ninesquares_widget_products_archive';
	}

	public function get_title(): string {
		return esc_html__( 'Nine фільтр і товари', 'ninesquares' );
	}

	public function get_icon(): string {
		return 'eicon-code';
	}

	public function get_categories(): array {
		return [ 'basic' ];
	}

	public function get_keywords(): array {
		return [ 'product' ];
	}

    public function get_style_depends(): array {
       return [];
    }
	public function get_script_depends(): array {
		return [];
	}
	//public function has_widget_inner_wrapper(): bool {}

	//protected function is_dynamic_content(): bool {}

	protected function register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Налаштування',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'products_count',
            [
                'label' => 'Кількість товарів',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 4,
            ]
        );

        $this->add_control(
            'filter_taxonomies',
            [
                'label' => 'Таксономії для фільтру',
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'label_block' => true,
                'options' => $this->get_all_taxonomies(), 
                'default' => [ 'product_cat' ], // дефолтна
            ]
        );

        $this->add_control(
            'term_start',
            [
                'label' => 'Це стартовий терм, його можна вказати якщо ви хочете щоб в фільтрі одразу була задіяна певна категорія. Але він не буде діяти якщо даний фільтр вже занаходиться на сторінці певної категорії',
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'label_block' => true,
                'options' => $this->get_all_terms(), 
                'default' => [ ], // дефолтна
            ]
        );

        $this->end_controls_section();
    }

    protected function get_all_taxonomies() {
        $taxonomies = get_taxonomies( [ 'object_type' => [ 'product' ] ], 'objects' );
        $options = [];

        foreach ( $taxonomies as $taxonomy ) {
            $options[ $taxonomy->name ] = $taxonomy->labels->singular_name;
        }

        return $options;
    }

    protected function get_all_terms() {
        $options = [];
        $taxonomies = get_object_taxonomies('product');

        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            ]);
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $options[$taxonomy.'||'.$term->term_id] = $term->name . " ({$taxonomy})";
                }
            }
        }

        return $options;
    }

	protected function render() {

        // налаштування віджета 
        $settings = $this->get_settings_for_display();

        global $post;
        $is_novynky = isset( $post->post_name ) && $post->post_name == 'novynky';
        $orderby = $is_novynky ? 'date' : 'menu_order';
        $order = $is_novynky ? 'DESC' : 'ASC';

        // Стартовий обєкт при першій загрузці
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => isset( $settings['products_count'] ) ? (int) $settings['products_count'] : 4,
            'orderby'        => $orderby,
            'order'          => $order,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        ];

        // 
        if ( !empty($_GET['s']) ) {
            $search_term = trim( sanitize_text_field($_GET['s']) );
            $search_term = mb_substr($search_term, 0, 100);
            $args['s'] = $search_term;
            $args['search_columns'] = ['post_title'];
        }

        $term_start = null;
        // просто товари але якщо сторінка таксономії то додаю таксономію цієї сторінки
        if ( is_product_taxonomy() ) {
            // Поточна категорія або підкатегорія
            $term_start = get_queried_object();
            $args['tax_query'] = [
                    [
                            'taxonomy' => $term_start->taxonomy,
                            'field'    => 'term_id',
                            'terms'    => $term_start->term_id
                    ],
            ];
            $term_start_arr = array($term_start->term_id);// для js і атрібута data-checked при першій загрузці
        }elseif( !empty($settings['term_start']) ){ // якщо в налаштуваннях обрано стартовий терм
            $tax_query_arr = array();
            $term_start_arr = array();// для js і атрібута data-checked при першій загрузці
            foreach( $settings['term_start'] as $value ){
                $new_arr = explode("||", $value); // product_cat||457
                if ( !$term_start ) { // зберігаємо ПЕРШИЙ як "term_start"
                    $term_start = get_term( (int) $new_arr[1], $new_arr[0] );
                }
                $tax_query_arr[] = array(
                        'taxonomy' => $new_arr[0],
                        'field'    => 'term_id',
                        'terms'    => $new_arr[1]
                );
                $term_start_arr[] = $new_arr[1];// для js і атрібута data-checked при першій загрузці
            }
            $args['tax_query'] = $tax_query_arr;

        }

        // запит
        $result = new WP_Query( $args );

        $terms_by_tax = ns_get_attrs_available($result);

//        echo '<pre>';
//        print_r( $term_start );
//        echo '</pre>';
//        echo '<pre>';
//        print_r( $terms_by_tax );
//        echo '</pre>';

        $taxonomy = ns_get_filters_options($settings['filter_taxonomies'], $term_start, $terms_by_tax);


//        echo '<pre>';
//        print_r( $taxonomy );
//        echo '</pre>';
        ?>

        <!--мобільна кнопка-->
            <button id="ns_btn_open_fill" type="button">
                <span class="ns_btn_fill_img">
                    <!-- <svg xmlns="http://www.w3.org/2000/svg" fill="#000000" width="20px" height="20px" viewBox="0 0 32 32"><path d="M12,25l6.67,6.67a1,1,0,0,0,.7.29.91.91,0,0,0,.39-.08,1,1,0,0,0,.61-.92V13.08L31.71,1.71A1,1,0,0,0,31.92.62,1,1,0,0,0,31,0H1A1,1,0,0,0,.08.62,1,1,0,0,0,.29,1.71L11.67,13.08V24.33A1,1,0,0,0,12,25ZM3.41,2H28.59l-10,10a1,1,0,0,0-.3.71V28.59l-4.66-4.67V12.67a1,1,0,0,0-.3-.71Z"/></svg> -->
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M7.14563 15.596V11.62C7.14563 11.239 7.14562 11.048 7.10162 10.87C7.06227 10.7119 6.99742 10.5613 6.90963 10.424C6.80963 10.269 6.67163 10.138 6.39463 9.876L1.57863 5.322C1.19348 4.95779 0.926498 4.48638 0.812243 3.96876C0.697989 3.45113 0.741715 2.91114 0.937765 2.41864C1.13381 1.92615 1.47316 1.50383 1.91189 1.20634C2.35063 0.908844 2.86854 0.749875 3.39863 0.75H16.5876C17.1183 0.749988 17.6369 0.908478 18.0769 1.20515C18.517 1.50183 18.8584 1.92316 19.0573 2.41514C19.2563 2.90711 19.3038 3.44731 19.1938 3.96647C19.0837 4.48562 18.8211 4.96008 18.4396 5.329L13.6746 9.936C13.4046 10.197 13.2696 10.328 13.1736 10.481C13.0881 10.617 13.0249 10.7659 12.9866 10.922C12.9426 11.098 12.9426 11.286 12.9426 11.662V15.597C12.9426 16.139 12.9426 16.41 12.8806 16.654C12.7782 17.0543 12.5543 17.4131 12.2396 17.681C12.0476 17.844 11.8036 17.963 11.3166 18.201C10.1766 18.758 9.60763 19.036 9.14463 19.036C8.77053 19.0357 8.40401 18.9305 8.08671 18.7324C7.76941 18.5342 7.51404 18.251 7.34963 17.915C7.14563 17.499 7.14563 16.864 7.14563 15.596Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="ns_btn_fill_text">Фільтр</span>
            </button>
        <!---->
        <!--мобільний елемент-->
            <div class="offcanvas-backdrop"></div>
        <!---->

        <!--блок швидкого очищення товарів-->
            <div class="ns_wrap_kill">
            </div>
        <!---->

        <!--Фільтер таксономій-->
            <div class="wrap_fillter_ninesquares_widget_products_archive offcanvas " data-posts_per_page = "<?php echo $settings['products_count']; ?>">

                <!--мобільна кнопка та мобільний блок-->
                    <div class="ns_blok_moby">
                        <button id="ns_btn_close_fill">Закрити</button>
                        <h2>ФІЛЬТР</h2>
                    </div>
                <!---->

                <?php $html = ns_render_filter_sidebar($taxonomy, false, $is_novynky, $term_start_arr);
                echo $html; ?>

            </div>
        <!-------------->

        <!--Контейнер товарів-->
            <div class="ninesquares_widget_products_archive_wrap">
                <?php
                $html = ns_render_loop($result, false);
                echo $html;
                ?>
            </div>
        <!-------------->

        <!--Кнопка показати ще-->
            <div class="ninesquares_widget_products_archive_loadmore">
                <button id="ninesquares_loadmore" <?php echo ( $result->max_num_pages > 1 ? ' style="display:block;" ' : ' style="display:none;" ' ) ?> >Показати ще</button>
            </div>
        <!----------------->

        <?php
    }


}


