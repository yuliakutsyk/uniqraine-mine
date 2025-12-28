jQuery(document).ready(function($) {

    // Читання search з URL
    function getSearchFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('s') || '';
    }

    function ninesquares_widget_products_archive(){
        let wrap = document.querySelector('.ninesquares_widget_products_archive_wrap .products'); // контейнер з товарами
        let fillter = document.querySelector('.wrap_fillter_ninesquares_widget_products_archive'); // контейнер з фільтрами
        let ninesquares_loadmore = document.querySelector('#ninesquares_loadmore'); // кнопка додати ще

        if(!fillter){
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
        data = Array.from(items).filter((item)=>{
            // якщо ок то додам до масива
            if(item.getAttribute('data-checked') === 'ok'){
                // для стилів
                item.classList.add('active');
                return item; // потрапить в масив
            }else{
                // для стилів
                item.classList.remove('active');
            }
        });

        // проходжусь по data та створюю новий масив term вже з потрібними значеннями
        term = data.map(function(name) {
            return name.getAttribute('data-value');
        });

        // отрисовка title
        data.forEach(item => {
            let parent = item.closest('.item_fillter');
            item_fillter_response_ul = parent.querySelector('.item_fillter_response ul');
            // створюємо li елемент
            let li = document.createElement('li');
            //додав текст
            li.textContent = item.textContent;
            //додав атрібут
            li.setAttribute('data-value', item.getAttribute('data-value'));
            // додаємо у список
            item_fillter_response_ul.appendChild(li);
        });

        // для сортування записую в змінну sort_value Новинки бо вони по дефолту
        sort_value = 'data';

        // блок швидкої очистки
        ns_wrap_kill_func( items, data, term, titles, page, ninesquares_widget.nonce, fillter, sort_value, ninesquares_widget.ajax_url, wrap, ninesquares_loadmore );
        //

        /////////////

        ///////// фільтри таксономій
        //відкривання менюшки
        // окремо для фільтрів
        let arrows_fillter = document.querySelectorAll(".wrap_fillter_ninesquares_widget_products_archive .item_fillter");
        if (arrows_fillter.length) {
            arrows_fillter.forEach((item) => {
                item.addEventListener("click", (e) => {
                    e.stopPropagation(); // не пускаємо далі
                    let requestBlock = item.querySelector(".item_fillter_request");
                    let isOpen = requestBlock?.classList.contains("show");

                    // закриваємо всі
                    arrows_fillter.forEach((el) => {
                        el.querySelector(".item_fillter_request")?.classList.remove("show");
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
                    arrows_fillter.forEach((el) => {
                        el.querySelector(".item_fillter_request")?.classList.remove("show");
                    });
                }
            });
        }

        /////////////////////

        ///////// сортування
        //відкривання менюшки
        // окремо для сортування
        let arrows_sorts = document.querySelectorAll(".wrap_fillter_ninesquares_widget_products_archive .item_sort");
        if (arrows_sorts.length) {
            arrows_sorts.forEach((item) => {
                item.addEventListener("click", (e) => {
                    e.stopPropagation(); // не пускаємо далі
                    let requestBlock = item.querySelector(".item_sort_request");
                    let isOpen = requestBlock?.classList.contains("show");

                    // закриваємо всі
                    arrows_sorts.forEach((el) => {
                        el.querySelector(".item_sort_request")?.classList.remove("show");
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
                    arrows_sorts.forEach((el) => {
                        el.querySelector(".item_sort_request")?.classList.remove("show");
                    });
                }
            });
        }

        //////////////////////

        // Функція для ініціалізації обробників подій
        window.initFilterHandlers = function() {
            // Оновлюємо селектори
            items = document.querySelectorAll('.item_fillter_request li');
            sorts = document.querySelectorAll('.item_sort_request li');
            titles = document.querySelectorAll('.wrap_fillter_ninesquares_widget_products_archive .item_fillter_response ul');

            // Видаляємо старі обробники (clone trick)
            items.forEach((item) => {
                let newItem = item.cloneNode(true);
                item.parentNode.replaceChild(newItem, item);
            });

            sorts.forEach((sort) => {
                let newSort = sort.cloneNode(true);
                sort.parentNode.replaceChild(newSort, sort);
            });

            // Оновлюємо селектори після клонування
            items = document.querySelectorAll('.item_fillter_request li');
            sorts = document.querySelectorAll('.item_sort_request li');

            // фільтри
            items.forEach((item) => {
                item.addEventListener('click', (e)=>{
                    let checked = item.getAttribute('data-checked');

                    //перемикач
                    if(checked === 'no'){
                        item.setAttribute('data-checked', 'ok');
                    }
                    else if(checked === 'ok'){
                        item.setAttribute('data-checked', 'no');
                    }

                    // складу все в масив який буду відправляти
                    // переберу всі елементи в дереві і якщо ок то буду додавати їх в масив data
                    data = Array.from(items).filter((item)=>{
                        // якщо ок то додам до масива
                        if(item.getAttribute('data-checked') === 'ok'){
                            // для стилів
                            item.classList.add('active');
                            return item; // потрапить в масив
                        }else{
                            // для стилів
                            item.classList.remove('active');
                        }
                    });

                    // отрисовка title
                    // при кожному кліку спочатку видалим все що передцим було написанов title
                    titles.forEach(title => {
                        title.innerHTML = '';
                    });

                    data.forEach(item => {
                        let parent = item.closest('.item_fillter');
                        item_fillter_response_ul = parent.querySelector('.item_fillter_response ul');
                        // створюємо li елемент
                        let li = document.createElement('li');
                        //додав текст
                        li.textContent = item.textContent;
                        //додав атрібут
                        li.setAttribute('data-value', item.getAttribute('data-value'));

                        // чіпляємо клік
                        li.addEventListener('click', (e) => {
                            e.stopPropagation(); // блокуємо спливання події
                            // зняти клік з елемента
                            // проходжусь заново по всим реальним li і знімаю клік
                            items.forEach(item => {
                                let value = item.getAttribute('data-value');
                                // порівнюю якщо data-value лішок співпало то треба зняти чекед і прибрати клас активності і переписати масив data
                                if( li.getAttribute('data-value') === value ){
                                    item.setAttribute('data-checked', 'no');
                                }
                            });

                            // перезбираю масав data
                            data = Array.from(items).filter((item)=>{
                                // якщо ок то додам до масива
                                if(item.getAttribute('data-checked') === 'ok'){
                                    // для стилів
                                    item.classList.add('active');
                                    return item; // потрапить в масив
                                }else{
                                    // для стилів
                                    item.classList.remove('active');
                                }
                            });

                            // переписати term
                            // проходжусь по data та створюю новий масив term вже з потрібними значеннями
                            term = data.map(function(name) {
                                return name.getAttribute('data-value');
                            });

                            // скидаєм на до першої сторінки
                            page = 1;
                            // видалю цю лішку з title
                            e.target.remove();
                            // блок швидкої очистки
                            ns_wrap_kill_func( items, data, term, titles, page, ninesquares_widget.nonce, fillter, sort_value, ninesquares_widget.ajax_url, wrap, ninesquares_loadmore );
                            //
                            // відправляю аякс
                            // аякс запит з масивом term тобто таксономій
                            let norm_data = {
                                action: 'ninesquares_widget_products_archive',
                                nonce: ninesquares_widget.nonce,
                                posts_per_page: fillter.getAttribute('data-posts_per_page'),
                                page: page,
                                term: term,
                                sort: sort_value,
                                s: getSearchFromUrl()
                            };
                            ninesquares_widget_products_archive_ajax_fill_sort( ninesquares_widget.ajax_url, page, norm_data, wrap, fillter, ninesquares_loadmore );
                        });

                        // додаємо у список
                        item_fillter_response_ul.appendChild(li);
                    });

                    // проходжусь по data та створюю новий масив term вже з потрібними значеннями
                    term = data.map(function(name) {
                        return name.getAttribute('data-value');
                    });

                    // скидаєм на до першої сторінки
                    page = 1;

                    // блок швидкої очистки
                    ns_wrap_kill_func( items, data, term, titles, page, ninesquares_widget.nonce, fillter, sort_value, ninesquares_widget.ajax_url, wrap, ninesquares_loadmore );
                    //

                    // аякс запит з масивом term тобто таксономій
                    let norm_data = {
                        action: 'ninesquares_widget_products_archive',
                        nonce: ninesquares_widget.nonce,
                        posts_per_page: fillter.getAttribute('data-posts_per_page'),
                        page: page,
                        term: term,
                        sort: sort_value,
                        s: getSearchFromUrl()
                    };
                    ninesquares_widget_products_archive_ajax_fill_sort( ninesquares_widget.ajax_url, page, norm_data, wrap, fillter, ninesquares_loadmore );
                });
            });

            // сортування
            sorts.forEach((sort) => {
                sort.addEventListener('click', (e)=>{
                    // спочатку стираю все
                    sorts.forEach((sortttt) => {
                        sortttt.classList.remove('active');
                    });

                    // роблю ативним елемнент
                    sort.classList.add('active');
                    sort_value = sort.getAttribute('data-value');

                    let parent = sort.closest('.item_sort');
                    item_sort_response_ul = parent.querySelector('.item_sort_response ul');
                    item_sort_response_ul.innerHTML = `<li>${sort.textContent}</li>`;

                    // скидаєм на до першої сторінки
                    page = 1;

                    // отправляю аякс заприт
                    let norm_data = {
                        action: 'ninesquares_widget_products_archive',
                        nonce: ninesquares_widget.nonce,
                        posts_per_page: fillter.getAttribute('data-posts_per_page'),
                        page: page,
                        term: term,
                        sort: sort_value,
                        s: getSearchFromUrl()
                    };
                    ninesquares_widget_products_archive_ajax_fill_sort( ninesquares_widget.ajax_url, page, norm_data, wrap, fillter, ninesquares_loadmore );
                });
            });
        }

        // Ініціалізуємо обробники при завантаженні
        initFilterHandlers();

        //////////////// кнопка додати ще
        if(ninesquares_loadmore){
            ninesquares_loadmore.addEventListener('click', (e)=>{
                // при клікі на кнопку збільшу лічильник сторінок
                page++;
                // використовую готовий масив term який або пусти або вже заповнений
                // аякс запит з масивом term тобто таксономій
                let norm_data = {
                    action: 'ninesquares_widget_products_archive',
                    nonce: ninesquares_widget.nonce,
                    posts_per_page: fillter.getAttribute('data-posts_per_page'),
                    page: page,
                    term: term,
                    sort: sort_value,
                    s: getSearchFromUrl()
                };
                ninesquares_widget_products_archive_ajax_paginate( ninesquares_widget.ajax_url, page, norm_data, wrap, fillter, ninesquares_loadmore );
            });
        }
        ///////////////////////

        // кнопка для показу мобільної версії фільтра
        let button_open = document.querySelector('#ns_btn_open_fill');
        let button_close = document.querySelector('#ns_btn_close_fill');

        if(button_open && button_close) {
            // створюємо backdrop динамічно
            const backdrop = document.createElement('div');
            backdrop.classList.add('offcanvas-backdrop');
            document.body.appendChild(backdrop);

            // відкриття
            button_open.addEventListener('click', ()=>{
                openOffcanvas(fillter, backdrop);
            });

            // закриття по кнопці
            button_close.addEventListener('click', ()=>{
                closeOffcanvas(fillter, backdrop);
            });

            // закриття по кліку на бекдроп
            backdrop.addEventListener('click', ()=>{
                closeOffcanvas(fillter, backdrop);
            });

            // закриття по Esc
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeOffcanvas(fillter, backdrop);
            });

            const offsetTop = button_open.offsetTop; // початкове місце кнопки
            window.addEventListener('scroll', () => {
                if (window.scrollY > offsetTop) {
                    button_open.classList.add('sticky');
                } else {
                    button_open.classList.remove('sticky');
                }
            });
        }
    }

    // AJAX функція для фільтрації та сортування
    function ninesquares_widget_products_archive_ajax_fill_sort( ajax_url, page, norm_data, wrap, fillter, ninesquares_loadmore ){
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: norm_data,
            beforeSend: function(){
                // Можна додати loader
                if(fillter) {
                    fillter.classList.add('loading');
                }
            },
            success: function(response){
                if(response.success) {
                    // Оновлюємо товари
                    if(wrap && response.data.html) {
                        wrap.innerHTML = response.data.html;
                    }

                    // Оновлюємо фільтри якщо прийшли
                    if(response.data.fillter_html && fillter) {
                        fillter.innerHTML = response.data.fillter_html;
                    }

                    // Переініціалізуємо обробники подій після AJAX
                    if(typeof window.initFilterHandlers === 'function') {
                        window.initFilterHandlers();
                    }

                    // Управління кнопкою Load More
                    if(ninesquares_loadmore) {
                        if(response.data.max_pages && response.data.max_pages <= page) {
                            ninesquares_loadmore.style.display = 'none';
                        } else {
                            ninesquares_loadmore.style.display = 'block';
                        }
                    }
                }
            },
            complete: function(){
                if(fillter) {
                    fillter.classList.remove('loading');
                }
            },
            error: function(xhr, status, error){
                console.error('AJAX Error:', error);
            }
        });
    }

    // AJAX функція для пагінації (Load More)
    function ninesquares_widget_products_archive_ajax_paginate( ajax_url, page, norm_data, wrap, fillter, ninesquares_loadmore ){
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: norm_data,
            beforeSend: function(){
                if(ninesquares_loadmore) {
                    ninesquares_loadmore.classList.add('loading');
                }
            },
            success: function(response){
                if(response.success) {
                    // Додаємо товари в кінець
                    if(wrap && response.data.html) {
                        wrap.insertAdjacentHTML('beforeend', response.data.html);
                    }

                    // Управління кнопкою Load More
                    if(ninesquares_loadmore) {
                        if(response.data.max_pages && response.data.max_pages <= page) {
                            ninesquares_loadmore.style.display = 'none';
                        }
                    }
                }
            },
            complete: function(){
                if(ninesquares_loadmore) {
                    ninesquares_loadmore.classList.remove('loading');
                }
            },
            error: function(xhr, status, error){
                console.error('AJAX Error:', error);
            }
        });
    }

    // Функція для блоку швидкої очистки фільтрів
    function ns_wrap_kill_func( data, term, page, nonce, fillter, sort_value, ajax_url, wrap, ninesquares_loadmore ){
        const ns_wrap_kill = document.querySelector('#ns_wrap_kill');

        if(ns_wrap_kill) {
            if(data.length === 0) {
                ns_wrap_kill.style.display = 'flex';

                // Видаляємо старий обробник якщо є
                let newKill = ns_wrap_kill.cloneNode(true);
                ns_wrap_kill.parentNode.replaceChild(newKill, ns_wrap_kill);

                newKill.addEventListener('click', () => {
                    const items = document.querySelectorAll('.item_fillter_request li');
                    items.forEach(item => {
                        item.setAttribute('data-checked', 'no');
                        item.classList.remove('active');
                    });

                    const titles = document.querySelectorAll('.item_fillter_response ul');
                    titles.forEach(title => {
                        title.innerHTML = '';
                    });

                    const norm_data = {
                        action: 'ninesquares_widget_products_archive',
                        nonce: nonce,
                        posts_per_page: fillter.getAttribute('data-posts_per_page'),
                        page: 1,
                        term: [],
                        sort: sort_value,
                        s: getSearchFromUrl()
                    };

                    ninesquares_widget_products_archive_ajax_fill_sort( ajax_url, 1, norm_data, wrap, fillter, ninesquares_loadmore );
                });
            } else {
                ns_wrap_kill.style.display = 'none';
            }
        }
    }

    // Helper функції для offcanvas
    function openOffcanvas(fillter, backdrop) {
        fillter.classList.add('show');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeOffcanvas(fillter, backdrop) {
        fillter.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    // Запускаємо основну функцію
    ninesquares_widget_products_archive();
});
