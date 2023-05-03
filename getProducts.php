<?php
require_once 'engine/paginator.class.php';

//engine::debug_printArray($_POST);
//exit;

$out = [
    'status' => false,
    'data' => null
];

//engine::debug_printArray($_POST['supplier']);

$query = 'SELECT @[prefix]products.name, @[prefix]products.article, @[prefix]products.code, @[prefix]products.id, 
          @[prefix]counterparty.id AS counter_id, 
          @[prefix]counterparty.name AS counter_name
          FROM @[prefix]products
          INNER JOIN @[prefix]counterparty
          ON @[prefix]products.supplier = @[prefix]counterparty.id ';

$supArray = explode(",", @$_POST['setData']['supplier']);


// если отправили поисковую строку
if (mb_strlen(@$_POST['setData']['searchProd']) > 1) {

    //  записываем в переменные эти значения
    $searchProd = trim((@$_POST['setData']['searchProd']) ? $_POST['setData']['searchProd'] : '');


    if (in_array('all', $supArray)) {
        $query = 'SELECT @[prefix]products.name, @[prefix]products.article, @[prefix]products.code, @[prefix]products.id, 
          @[prefix]counterparty.id AS counter_id, 
          @[prefix]counterparty.name AS counter_name
          FROM @[prefix]products
          LEFT JOIN @[prefix]counterparty
          ON @[prefix]counterparty.id = @[prefix]products.supplier
                  WHERE @[prefix]products.name LIKE "%' . $searchProd . '%"
                    OR @[prefix]products.article LIKE "%' . $searchProd . '%"
                    OR @[prefix]products.code LIKE "%' . $searchProd . '%"';
    } else {
        // если выбрали и товар и поставщика

        $query .= ' WHERE @[prefix]counterparty.id IN (?a) 
                    AND @[prefix]products.name LIKE "%' . $searchProd . '%"
                    OR @[prefix]products.article LIKE "%' . $searchProd . '%"
                    OR @[prefix]products.code LIKE "%' . $searchProd . '%"';
        $query = engine::DB()->parse($query, $supArray);
    }


} // если выбрали конкретного производителя, но не выбрали значение товара
else {
    $query .= ' WHERE @[prefix]counterparty.id IN (?a)';
    $query = engine::DB()->parse($query, $supArray);


    if (in_array('all', $supArray)) {
        $query = 'SELECT @[prefix]products.name, @[prefix]products.article, @[prefix]products.code, @[prefix]products.id, 
          @[prefix]counterparty.id AS counter_id, 
          @[prefix]counterparty.name AS counter_name
          FROM @[prefix]products
          LEFT JOIN @[prefix]counterparty
          ON @[prefix]counterparty.id = @[prefix]products.supplier';
    }

}

// формирование пагинации
$limit = (isset($_POST['limit'])) ? $_POST['limit'] : 15;
$num = intval((isset($_POST['setData']['numid'])) ? $_POST['setData']['numid'] : 1);
$links = (isset($_POST['links'])) ? $_POST['links'] : 4;

$rs = engine::DB()->getAll($query);
$total = count($rs);
$offset = $num * $total;

$Paginator = new paginator();
$Paginator->getQuery($query, $total);
$results = $Paginator->getData($limit, $num, $offset);


// вывод товаров
if ($results->data) {
    $out['status'] = true;
    $items = [];
    if ($results->data) {
        foreach ($results->data as $item) {
            $items[] = $item;
        }
    }
    $out['data']['items'] = $items;
} else {
    $items = [];
    $out['status'] = false;
    $out['message'] = 'По вашему запросу товары не найдены';
}


$out['data']['setData'] = $supArray;

$out['data']['offset'] = $offset;
$out['data']['total'] = $total;

$out['data']['limit'] = $limit;
$out['data']['numid'] = $num;
$out['data']['links'] = $links;


exit(json_encode($out));















