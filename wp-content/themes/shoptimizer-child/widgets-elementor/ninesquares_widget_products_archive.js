jQuery(document).ready(function ($) {
    const ajax_url = ninesquares_widget.ajax_url;
    const nonce = ninesquares_widget.nonce;

// Читання search з URL
    function getSearchFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('s') || '';
    }

    function ninesquares_widget_products_archive() {

        let wrap = document.querySelector('.ninesquares_widget_products_archive_wrap .products'); // контейнер з товарами

        let fillter = document.querySelector('.wrap_fillter_ninesquares_widget_products_archive'); // контейнер з фільтрами

        let ninesquares_loadmore = document.querySelector('#ninesquares_loadmore'); // кнопка додати ще

        if (!fillter) {
            return;
        }

        let items = document.querySelectorAll('.item_fillter_request li'); // всі фільтра
        let data = []; // основний масив
        let term = [];
        let titles = document.querySelectorAll('.wrap_fillter_ninesquares_widget_products_archive .item_fillter_response ul'); // заголовки
        let page = 1; // перша сторінка
        let sorts = document.querySelectorAll('.item_sort_request li'); // всі фільтра
        let sort_value = '';

        ////////при першій загрузці відмічаю активні пункти та кладу їх в маисв data якщо такі є наприклад знаходимось на сторінці категорії
        // для фільтрів


        data = Array.from(items).filter((item) => {
            if (item.getAttribute('data-checked') === 'ok') {
                item.classList.add('active');
                return item; // потрапить в масив
            } else {
                item.classList.remove('active');
            }
        });

        // проходжусь по data та створюю новий масив term вже з потрібними значеннями
        term = data.map(function (name) {
            return name.getAttribute('data-value');
        });
        // отрисовка title

        ns_smth(data);

        // для сортування записую в змінну sort_value Новинки бо вони по дефолту
        sort_value = 'data';

        // блок швидкої очистки
        ns_update_clear_filters_block();
        //
        /////////////


        ///////// фільтри таксономій

        //відкривання менюшки
        // окремо для фільтрів
        let arrows_fillter = document.querySelectorAll(".wrap_fillter_ninesquares_widget_products_archive .item_fillter");
        if (arrows_fillter.length) {
            ns_smth2(arrows_fillter, "fillter");
        }
        // фільтри
        items.forEach((item) => {
            item.addEventListener('click', (e) => {
                //e.preventDefault();
                let checked = item.getAttribute('data-checked');

                //перемикач
                if (checked === 'no') {
                    item.setAttribute('data-checked', 'ok');

                } else if (checked === 'ok') {
                    item.setAttribute('data-checked', 'no');
                }
                // складу все в масив який буду відправляти
                // переберу всі елементи в дереві і якщо ок то буду додавати їх в масив data


                data = Array.from(items).filter((item) => {
                    if (item.getAttribute('data-checked') === 'ok') {
                        item.classList.add('active');
                        return item; // потрапить в масив
                    } else {
                        item.classList.remove('active');
                    }
                });


                // отрисовка title
                // при кожному кліку спочатку видалим все що передцим було написанов title
                titles.forEach(title => {
                    title.innerHTML = '';
                });

                ns_smth(data);


                // проходжусь по data та створюю новий масив term вже з потрібними значеннями
                term = data.map(function (name) {
                    return name.getAttribute('data-value');
                });

                // скидаєм на до першої сторінки
                page = 1;

                // блок швидкої очистки
                ns_update_clear_filters_block();
                //

                // аякс запит з масивом term тобто таксономій
                let norm_data = {
                    action: 'ninesquares_widget_products_archive',
                    nonce: nonce,
                    posts_per_page: fillter.getAttribute('data-posts_per_page'),
                    page: page,
                    term: term,
                    sort: sort_value,
                    s: getSearchFromUrl()
                };
                ninesquares_widget_products_archive_ajax_fill_sort(page, norm_data, wrap, fillter, ninesquares_loadmore);

            });
        });
        /////////////////////


        ///////// сортування

        //відкривання менюшки
        // окремо для сортування
        let arrows_sorts = document.querySelectorAll(".wrap_fillter_ninesquares_widget_products_archive .item_sort");
        if (arrows_sorts.length) {
            ns_smth2(arrows_sorts, "sort");
        }
        // сортування
        sorts.forEach((sort) => {

            sort.addEventListener('click', (e) => {

                // спочатку стираю все
                sorts.forEach((sortttt) => {
                    sortttt.classList.remove('active');
                });
                // роблю ативним елемнент
                sort.classList.add('active');
                sort_value = sort.getAttribute('data-value');
                //
                let parent = sort.closest('.item_sort');
                item_sort_response_ul = parent.querySelector('.item_sort_response ul');
                item_sort_response_ul.innerHTML = `<li >${sort.textContent}</li>`;
                // скидаєм на до першої сторінки
                page = 1;
                // отправляю аякс заприт
                let norm_data = {
                    action: 'ninesquares_widget_products_archive',
                    nonce: nonce,
                    posts_per_page: fillter.getAttribute('data-posts_per_page'),
                    page: page,
                    term: term,
                    sort: sort_value,
                    s: getSearchFromUrl()
                };
                ninesquares_widget_products_archive_ajax_fill_sort(page, norm_data, wrap, fillter, ninesquares_loadmore);

            });
        });
        //////////////////////


        //////////////// кнопка додати ще
        if (ninesquares_loadmore) {
            ninesquares_loadmore.addEventListener('click', (e) => {
                // при клікі на кнопку збільшу лічильник сторінок
                page++;
                // використовую готовий масив term який або пусти або вже заповнений
                // аякс запит з масивом term тобто таксономій
                let norm_data = {
                    action: 'ninesquares_widget_products_archive',
                    nonce: nonce,
                    posts_per_page: fillter.getAttribute('data-posts_per_page'),
                    page: page,
                    term: term,
                    sort: sort_value,
                    s: getSearchFromUrl()
                };
                ninesquares_widget_products_archive_ajax_paginate(page, norm_data, wrap, fillter, ninesquares_loadmore);


            });
        }
        ///////////////////////

        // кнопка для показу мобільної версії фільтра
        let button_open = document.querySelector('#ns_btn_open_fill');
        let button_close = document.querySelector('#ns_btn_close_fill');

        // створюємо backdrop динамічно
        const backdrop = document.createElement('div');
        backdrop.classList.add('offcanvas-backdrop');
        document.body.appendChild(backdrop);

        // відкриття
        button_open.addEventListener('click', () => {
            openOffcanvas(fillter, backdrop);
        });

        // закриття по кнопці
        button_close.addEventListener('click', () => {
            closeOffcanvas(fillter, backdrop);
        });

        // закриття по кліку на бекдроп
        backdrop.addEventListener('click', () => {
            closeOffcanvas(fillter, backdrop);
        });

        // закриття по Esc
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeOffcanvas(fillter, backdrop);
        });

        //
        const offsetTop = button_open.offsetTop; // початкове місце кнопки
        window.addEventListener('scroll', () => {
            if (window.scrollY > offsetTop) {
                button_open.classList.add('sticky');
            } else {
                button_open.classList.remove('sticky');
            }
        });



        function ns_update_clear_filters_block() {
            let ns_wrap_kill = document.querySelector('.ns_wrap_kill');

            if (data.length === 0) {
                ns_wrap_kill.innerHTML = '';
                return;
            }

            let html = `<button class="ns_kill_button">Очистити фільтри</button><ul>`;
            data.forEach(item => {
                html += `<li data-value="${item.getAttribute('data-value')}">${item.textContent}</li>`;
            });
            html += `</ul>`;

            ns_wrap_kill.innerHTML = html;
            ns_wrap_kill.querySelector('.ns_kill_button').addEventListener('click', ns_clear_all_filters);

            ns_wrap_kill.querySelector('ul').addEventListener('click', (e) => {
                let li = e.target.closest('li');
                if (li) ns_remove_filter_by_value(li.getAttribute('data-value'));
            });
        }

        function ns_remove_filter_by_value(value) {
            const itemToRemove = Array.from(items).find(item => item.getAttribute('data-value') === value);
            if (itemToRemove) {
                itemToRemove.setAttribute('data-checked', 'no');
                itemToRemove.classList.remove('active');
            }

            data = data.filter(item => item.getAttribute('data-value') !== value);
            term = data.map(item => item.getAttribute('data-value'));
            page = 1;

            ns_smth(data);
            ns_update_clear_filters_block();
            ns_send_ajax_request();
        }

        function ns_clear_all_filters() {
            items.forEach(item => {
                item.setAttribute('data-checked', 'no');
                item.classList.remove('active');
            });

            data = [];
            term = [];
            page = 1;

            titles.forEach(title => title.innerHTML = '');
            ns_update_clear_filters_block();
            ns_send_ajax_request();
        }

        function ns_send_ajax_request() {
            let norm_data = {
                action: 'ninesquares_widget_products_archive',
                nonce: nonce,
                posts_per_page: fillter.getAttribute('data-posts_per_page'),
                page: page,
                term: term,
                sort: sort_value,
                s: getSearchFromUrl()
            };
            ninesquares_widget_products_archive_ajax_fill_sort(page, norm_data, wrap, fillter, ninesquares_loadmore);
        }

        function ns_smth(data) {
            data.forEach(itemm => {
                let parent = itemm.closest('.item_fillter');
                item_fillter_response_ul = parent.querySelector('.item_fillter_response ul');
                let li = document.createElement('li');
                li.textContent = itemm.textContent;
                li.setAttribute('data-value', itemm.getAttribute('data-value'));

                li.addEventListener('click', (e) => {
                    e.stopPropagation();
                    ns_remove_filter_by_value(itemm.getAttribute('data-value'));
                });

                // додаємо у список
                item_fillter_response_ul.appendChild(li);
            });
        }

        function ns_smth2(data, slug) {
            data.forEach((item) => {
                item.addEventListener("click", (e) => {
                    e.stopPropagation(); // не пускаємо далі

                    let requestBlock = item.querySelector(".item_" + slug + "_request");
                    let isOpen = requestBlock?.classList.contains("show");

                    // закриваємо всі
                    data.forEach((el) => {
                        el.querySelector(".item_" + slug + "_request")?.classList.remove("show");
                    });

                    // якщо був закритий — відкриваємо поточний
                    if (!isOpen) {
                        requestBlock?.classList.add("show");
                    }
                });
            });
            // клік поза межами
            document.addEventListener("click", (e) => {
                if (!e.target.closest(".wrap_fillter_ninesquares_widget_products_archive")) {
                    data.forEach((el) => {
                        el.querySelector(".item_" + slug + "_request")?.classList.remove("show");
                    });
                }
            });
        }
    }

    ninesquares_widget_products_archive();

//wpcvs_cast_select_wpcvs_terms();

// функція закриття
    function closeOffcanvas(offcanvas, backdrop) {
        offcanvas.classList.remove('show');
        backdrop.classList.remove('show');
    }

// функція відкриття
    function openOffcanvas(offcanvas, backdrop) {
        offcanvas.classList.add('show');
        backdrop.classList.add('show');
    }

// аякс фунція для запиту зміни фільтра або сортування
    function ninesquares_widget_products_archive_ajax_fill_sort(f_page, f_data, f_wrap, f_fillter, f_ninesquares_loadmore) {
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: f_data,
            beforeSend: function () {
                f_wrap.style.pointerEvents = 'none';
                f_wrap.style.opacity = '0.5';
                f_fillter.style.pointerEvents = 'none';
                f_fillter.style.opacity = '0.5';
            },
            success: function (response) {

                //console.log('аякс фунція для запиту зміни фільтра або сортування');
                //console.log(response.data);

                f_wrap.style.pointerEvents = 'auto';
                f_wrap.style.opacity = '1';
                f_fillter.style.pointerEvents = 'auto';
                f_fillter.style.opacity = '1';

                // перезаписую весь контейнер
                f_wrap.innerHTML = response.data.html;
                // для плагіну WPC Variation Swatches for WooCommerce
                wpcvs_cast_select_wpcvs_terms();
                // умова для зникнення кнопки пашінації
                if (response.data.max_page && f_page >= response.data.max_page) {
                    f_ninesquares_loadmore.style.display = 'none';
                } else {
                    f_ninesquares_loadmore.style.display = 'block';
                }
                // окремо якщо response.data.max_page прийде 0
                if (!response.data.max_page) {
                    f_ninesquares_loadmore.style.display = 'none';
                }


            },
            error: function () {

            }
        });
    }

// аякс функція для пагінації
    function ninesquares_widget_products_archive_ajax_paginate(f_page, f_data, f_wrap, f_fillter, f_ninesquares_loadmore) {
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: f_data,
            beforeSend: function () {
                f_wrap.style.pointerEvents = 'none';
                f_wrap.style.opacity = '0.5';
                f_fillter.style.pointerEvents = 'none';
                f_fillter.style.opacity = '0.5';
                f_ninesquares_loadmore.style.pointerEvents = 'none';
                f_ninesquares_loadmore.style.opacity = '0.5';
            },
            success: function (response) {

                //console.log(response.data);

                f_wrap.style.pointerEvents = 'auto';
                f_wrap.style.opacity = '1';
                f_fillter.style.pointerEvents = 'auto';
                f_fillter.style.opacity = '1';
                f_ninesquares_loadmore.style.pointerEvents = 'auto';
                f_ninesquares_loadmore.style.opacity = '1';
                // додаю до вже існуючих
                f_wrap.insertAdjacentHTML('beforeend', response.data.html);
                // для плагіну WPC Variation Swatches for WooCommerce
                wpcvs_cast_select_wpcvs_terms();
                // умова для зникнення кнопки пашінації
                if (response.data.max_page && f_page >= response.data.max_page) {
                    f_ninesquares_loadmore.style.display = 'none';
                } else {
                    f_ninesquares_loadmore.style.display = 'block';
                }
                // окремо якщо response.data.max_page прийде 0
                if (!response.data.max_page) {
                    f_ninesquares_loadmore.style.display = 'none';
                }
            },
            error: function () {

            }
        });
    }

});

// для плагіну WPC Variation Swatches for WooCommerce
function wpcvs_cast_select_wpcvs_terms() {

    let products = document.querySelectorAll('.ninesquares_widget_products_archive_wrap .products .product .variations_form.wpcvs_form.wpcvs_archive');

    products.forEach((item) => {
        // необхідно сховати ті спани що не мають варіантів та не є в наявності
        // вкладена загальна інформація та саме важливе є начвність
        let rawVariations = item.getAttribute('data-product_variations');
        let decodedVariations = rawVariations.replace(/"/g, '"');
        let variations = JSON.parse(decodedVariations);
        let variation_arr = [];
        variations.forEach(variation => {
            let size = variation.attributes["attribute_pa_rozmir-odyagu"];
            let color = variation.attributes.attribute_pa_kolir;
            let inStock = variation.is_in_stock; // true або false
            let quantity = variation.cgkit_stock_quantity; // кількість на складі
            if (inStock) {
                variation_arr.push(size);
            }
        });
        //console.log(variation_arr);
        // селекти формуються плагіном але не враховуєть наявність
        let options = item.querySelectorAll('.select select option');
        options_value = [];
        options.forEach((option) => {
            options_value.push(option.value);
        });
        //console.log(options_value);
        // порівнюю два масива - один селекти інший те що є в наявності
        let intersection = variation_arr.filter(item => options_value.includes(item));
        //console.log(intersection); // ['s']
        let terms = item.querySelectorAll('.select .wpcvs-terms .wpcvs-term');
        // порівнюю значення в спанах та в результуючому масиві
        terms.forEach((term) => {
            //
            term.classList.remove('wpcvs-enabled');
            term.classList.add('wpcvs-disabled');
            if (intersection.includes(term.dataset.term)) {
                term.classList.remove('wpcvs-disabled');
                term.classList.add('wpcvs-enabled');
            }
        });
    });
}
