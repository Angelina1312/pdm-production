<?php
require_once 'engine/paginator.class.php';


$perPage = 25;
engine::TEMPLATE()->setTagValue('pagename', 'Товары');

//$products = engine::DB()->getAll('SELECT * FROM @[prefix]products LIMIT 15');

?>
<form action="/index.php?component=moysklad&page=products" method="POST">
    <div class="card-body">
        <div class="form-group">
            <h5>Найти товар</h5>
            <input type="hidden" name="component" id="moysklad" value="moysklad">
            <input type="hidden" name="page" id="products" value="products">
            <?php
            $chooseProducts = '';
            if (@$_GET['searchProd']) {
                $chooseProducts = $_GET['searchProd'];
            }
            ?>
            <input type="search" class="form-control" name="searchProd" id="searchProd" placeholder="Поиск по товарам"
                   value="<?php echo $chooseProducts ?>">
        </div>
        <?php
        // фильтр по поставщику
        $suppliers = engine::DB()->getAll("SELECT DISTINCT @[prefix]counterparty.name, @[prefix]products.supplier, @[prefix]counterparty.id
                                            FROM `@[prefix]counterparty`, `@[prefix]products`
                                            WHERE @[prefix]counterparty.id = @[prefix]products.supplier;");
        $selectedSuppliers = @explode(',', @$_GET['supplier'][0]);
        if (empty(@$_GET['supplier']) || count($selectedSuppliers) == 0) $selectedSuppliers[] = '45e059e8-576b-11e9-9ff4-315000017ccd';
        echo '<div class="form-group">';
        echo '<h5>Выбрать поставщика</h5>';
        echo '<select class="form-control js-example-basic-multiple" id="supplier" name="supplier[]" multiple="multiple">';
        echo '<option value="all" class="counterparty" id="supplierAll" name="supplierAll" ' . (in_array('all', $selectedSuppliers) ? ' selected' : '') . '>Все</option>';
        foreach ($suppliers as $supplier) {
            echo '<option class="counterparty" name="supplier[]" value="' . $supplier['id'] . '" ' . (in_array($supplier['id'], $selectedSuppliers) ? ' selected' : '') . '>' . $supplier['name'] . '</option>';
        }

        ?>
        </select>
    </div>

    <button class="btn btn-primary" name="submit" id="submit">Найти</button>
    <button class="btn btn-danger" name="reset">Сбросить фильтр</button>
    </div>
</form>

<!-- предупреждающее окно, если нет совпадений -->
<div id="warning"></div>

<div class="card card-success">
    <div class="card-header"><h3 class="card-title">Товары</h3></div>
    <div class="card-body">
        <table class="table" id="productsTable">
            <thead>
            <th>Картинка</th>
            <th>Наименование</th>
            <th>Артикул</th>
            <th>Код</th>
            <th>Поставщик</th>
            <thead>
            <tbody id="products_tbody">
            </tbody>
        </table>
    </div>
</div>

<div id="test"></div>


<div class="container" id="paginateLinks"></div>
<div class="container" id="paginateWords"></div>


<script>
    $(document).ready(function () {
        $('.js-example-basic-multiple').select2();
        let selectVal = $('#supplier').val();


        $('#supplier').on('select2:select', function (e) {
            var data = e.params.data;

            let changeSelect = '';

            // если стоял поставщик и выбираем "все"
            if (data.id === "all") {
                selectVal.splice(0);
                selectVal.push(data.id);
                changeSelect = selectVal;

            } // если стоял поставщик и выбираем еще одного
            else if (data.id !== "all") {
                selectVal.push(data.id);
                changeSelect = selectVal;

                // если стояло "все" и выбираем поставщика
                if (($.inArray('all', $('#supplier').val())) > -1) {
                    changeSelect.splice(0);
                    changeSelect.push(data.id);
                    console.log(changeSelect)
                }


                // выбор поставщика после удаления поставщиков
                if (($.inArray('all', $('#supplier').val())) === -1) {
                    $('#supplier').val().splice(0);
                    $('#supplier').val().push(data.id);
                    changeSelect = $('#supplier').val();
                }
            } else {
                changeSelect = selectVal;
            }

            // переопределяем маасив с поставщиками
            selectVal = changeSelect;
            $('#supplier').val(selectVal).trigger('change.select2');
        });


        // событие на очищение выбора
        $('#supplier').on('select2:unselect', function (e) {
            let updateSelect = $('#supplier').val();
            var dataClear = e.params.data;

            // проверяем, чтобы было выбрано не более 1 поставщика
            if (updateSelect.length < 1) {
                let changeSelect = '';
                // подставляем все, если очищается выбор
                updateSelect.splice(0);
                updateSelect.push("all");
                changeSelect = updateSelect;

                updateSelect = changeSelect;

                $('#supplier').val(updateSelect).trigger('change.select2');
            }
        });
    });

    function createLinks(data, links, list_class) {

        let last = Math.ceil(data.data.total / data.data.limit);

        let start = ((data.data.numid - links) > 0) ? data.data.numid - links : 1;
        let end = ((data.data.numid + links) < last) ? data.data.numid + links : last;


        let setHtml = '  <nav aria-label="Page navigation example">';

        setHtml += '<ul class="' + list_class + '">';

        let setClass = (data.data.numid == 1) ? "disabled" : "";
        setHtml += '<li class="page-item ' + setClass + '"><a class="page-link pagePaginate" data-numid="' + (data.data.numid - 1) + '" href="#">&laquo;</a></li>';

        if (start > 1) {
            setHtml += '<li class="page-item"><a class="page-link pagePaginate" data-numid="' + 1 + '" href="#">1</a></li>';
            setHtml += '<li class="page-item disabled"><a class="page-link pagePaginate" data-numid="' + data.data.numid + '" href="#">...</a></li>';
        }

        for (let i = start; i <= end; i++) {
            setClass = (data.data.numid == i) ? " active " : "";
            setHtml += '<li class="page-item ' + setClass + '"><a class="page-link pagePaginate" data-numid="' + i + '" href="#">' + i + '</a></li>';
        }

        if (end < last) {
            setHtml += '<li class="page-item disabled"><a class="page-link pagePaginate" href="#">...</a></li>';
            setHtml += '<li class="page-item"><a class="page-link pagePaginate" data-numid="' + last + '" href="#">' + last + '</a></li>';
        }

        setClass = (data.data.numid == last) ? "disabled" : "";
        setHtml += '<li class="page-item ' + setClass + '"><a class="page-link pagePaginate" data-numid="' + (data.data.numid + 1) + '" href="#">&raquo;</a></li>';

        setHtml += '</ul>';
        setHtml += '</nav>';


        return setHtml;
    }

    function paginateCreate(data) {

        let paginateLink = '';

        function createLinks(links, list_class) {

            let last = Math.ceil(data.data.total / data.data.limit);

            let start = ((data.data.numid - links) > 0) ? data.data.numid - links : 1;
            let end = ((data.data.numid + links) < last) ? data.data.numid + links : last;


            let setHtml = '  <nav aria-label="Page navigation example">';

            setHtml += '<ul class="' + list_class + '">';

            let setClass = (data.data.numid == 1) ? "disabled" : "";
            setHtml += '<li class="page-item ' + setClass + '"><a class="page-link pagePaginate" data-numid="' + (data.data.numid - 1) + '" href="/index.php?component=moysklad&page=products&limit=' + data.data.limit + '&num=' + (data.data.numid - 1) + '">&laquo;</a></li>';

            if (start > 1) {
                setHtml += '<li class="page-item"><a class="page-link pagePaginate" data-numid="' + 1 + '" href="/index.php?component=moysklad&page=products&limit=' + data.data.limit + '&num=1">1</a></li>';
                setHtml += '<li class="page-item disabled"><a class="page-link pagePaginate" data-numid="' + data.data.numid + '" href="#">...</a></li>';
            }

            for (let i = start; i <= end; i++) {
                setClass = (data.data.numid == i) ? " active " : "";
                setHtml += '<li class="page-item ' + setClass + '"><a class="page-link pagePaginate" data-numid="' + i + '" href="/index.php?component=moysklad&page=products&limit=' + data.data.limit + '&num=' + i + '">' + i + '</a></li>';
            }

            if (end < last) {
                setHtml += '<li class="page-item disabled"><a class="page-link pagePaginate" href="#">...</a></li>';
                setHtml += '<li class="page-item"><a class="page-link pagePaginate" data-numid="' + last + '" href="/index.php?component=moysklad&page=products&limit=' + data.data.limit + '&num=' + last + '">' + last + '</a></li>';
            }

            setClass = (data.data.numid == last) ? "disabled" : "";
            setHtml += '<li class="page-item ' + setClass + '"><a class="page-link pagePaginate" data-numid="' + (data.data.numid + 1) + '" href="/index.php?component=moysklad&page=products&limit=' + data.data.limit + '&num=' + (data.data.numid + 1) + '">&raquo;</a></li>';

            setHtml += '</ul>';
            setHtml += '</nav>';


            return setHtml;
        }

        paginateLink += createLinks(data.data.links, 'pagination');

        $('#paginateLinks').html(paginateLink);
    }

    // ajax
    $(document).ready(function () {

        let gets = '';
        let setData = '';

        function getParams() {
            // получение параметров из url
            gets = (function () {
                let a = window.location.search;
                let b = new Object();
                a = a.substring(1).split("&");
                for (let i = 0; i < a.length; i++) {
                    let c = a[i].split("=");
                    b[c[0]] = c[1];
                }
                return b;
            })();

            let urlObj = {
                component: gets.component,
                page: gets.page
            };

            let supObj = {
                supplier: '45e059e8-576b-11e9-9ff4-315000017ccd'
            }

            let numObj = {
                numid: $(this).data('numid')
            }


            //  условия получения котнкретных get параметров
            if (gets.searchProd) {
                var searchObj = {
                    searchProd: decodeURIComponent(gets.searchProd)
                };
            }


            if (gets['supplier[]']) {
                supObj = {
                    supplier: gets['supplier[]']
                };
            }


            if (gets.num) {
                numObj = {
                    numid: gets.num
                }
            }

            setData = Object.assign(urlObj, searchObj, supObj, numObj);
        }


        getParams();

        // загрузка товаров при открытии страницы
        $.ajax({
            dataType: "json",
            method: 'POST',
            url: '/ajax.php?component=moysklad&do=getProducts',
            data: {
                setData
            },
            success: function (data) {
                if (data.status === true) {
                    updateProducts(data)
                    paginateCreate(data)
                    getParams()
                } else {
                    paginateCreate(data)
                    let warningAlert = '<div class="alert alert-warning">По вашему запросу товары не найдены </div>';
                    let productsClean = data.items = [];
                    let itemsLength = data.items.length;
                    let paginateWord = '';
                    paginateWord += '<p class="pagination">Показано ' + itemsLength + ' записей из ' + data.data.total + '</p>';
                    $('#warning').html(warningAlert);
                    $('#products_tbody').html(productsClean);
                    $('#paginateWords').html(paginateWord);
                    getParams()
                }


            }

        });


        // передать номер страницы в пагинации
        $("body").on("click", '.pagePaginate', function (e) {
            e.preventDefault();

            $.ajax({
                dataType: "json",
                method: 'POST',
                url: '/ajax.php?component=moysklad&do=getFilter',
                data: {
                    component: $("#moysklad").val(),
                    page: $("#products").val(),
                    searchProd: $("#searchProd").val(),
                    supplier: $("#supplier").val(),
                    numid: $(this).data('numid')
                },
                success: function (data) {
                    updateProducts(data)
                    paginateCreate(data)
                    setLocation(data.data.page)
                }
            });
        });


        // загрузка товаров при поиске
        $('#submit').on("click", function (e) {
            e.preventDefault();

            $.ajax({
                dataType: "json",
                method: 'POST',
                url: '/ajax.php?component=moysklad&do=getFilter',
                data: {
                    component: $("#moysklad").val(),
                    page: $("#products").val(),
                    searchProd: $("#searchProd").val(),
                    supplier: $("#supplier").val(),
                    numid: $(this).data('numid')
                },
                success: function (data) {
                    if (data.status === true) {
                        updateProducts(data)
                        paginateCreate(data)
                    } else {
                        paginateCreate(data)
                        let warningAlert = '<div class="alert alert-warning">По вашему запросу товары не найдены </div>';
                        let productsClean = data.items = [];
                        let itemsLength = data.items.length;
                        let paginateWord = '';
                        paginateWord += '<p class="pagination">Показано ' + itemsLength + ' записей из ' + data.data.total + '</p>';
                        $('#warning').html(warningAlert);
                        $('#products_tbody').html(productsClean);
                        $('#paginateWords').html(paginateWord);
                    }
                    setLocation(data.data.page)
                },
            });
        });


        // обновление товаров
        function updateProducts(data) {
            let tbody = '';
            let paginateWord = '';
            let itemsLength = data.data.items.length;
            data.data.items.forEach(function (item) {
                tbody += '<tr>';
                tbody += '<td><img loading="lazy" style="max-width: 125px;" src="https://office.lebergroup.ru:9994/tg/' + item.article + '.jpg" onerror="this.onerror=null;this.width=100;this.src=\'https://tech.lebergroup.ru/moysklad/pages/schedule/notfound.png\';"></td>';
                tbody += '<td><a href="/index.php?component=moysklad&page=product&productId=' + item.id + '">' + item.name + '</td>';
                if(item.article == null) {
                    item.article = 'Артикул не указан';
                }
                tbody += '<td>' + item.article + '</td>';
                tbody += '<td>' + item.code + '</td>';
                if(item.counter_name == null) {
                    item.counter_name = 'Поставщик не указан';
                }
                tbody += '<td>' + item.counter_name + '</td>';
                tbody += '</tr>';
            });

            paginateWord += '<p class="pagination">Показано ' + itemsLength + ' записей из ' + data.data.total + '</p>';
            $('#products_tbody').html(tbody);
            $('#paginateWords').html(paginateWord);
        }

        // обновление url
        function setLocation(curLoc) {
            try {
                history.pushState(curLoc, null, curLoc);
                return;
            } catch (e) {
            }
            location.hash = '#' + curLoc;
        }
    });

</script>

