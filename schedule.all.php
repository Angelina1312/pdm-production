<?php

if(!@$_COOKIE['schedule_switcher']) setcookie('schedule_switcher', 'hide', time()+60*60*24*30);
engine::TEMPLATE()->setTagValue('pagename', 'График производства');

module::getModuleClass('breadcrumbs')->addElement('#!', 'Производство ');;
mod_breadcrumbs::$disableDefault = true;
module::getModuleClass('breadcrumbs')->addElement('/index.php?component=factory&page=scheduleInDev', 'График производства');;
module::getModuleClass('breadcrumbs')->addElement('/index.php?component=system&do=generateXLSX&template=schedule&type=xlsx&mode=default&id=15&mode=all&filename='.urlencode('График производства '.date('Y-m-d')), '<i class="fa fa-file-excel"></i> excel');

$processingOrders = engine::DB()->getAll('SELECT * FROM @[prefix]moysklad_processingOrders WHERE applicable=1 ORDER BY moment ASC');

$productsIds = array_column($processingOrders, 'productId');
$processingPlansIds = array_column($processingOrders, 'processingPlan');

$specificationsUP = ($processingPlansIds) ? engine::DB()->getAll('
SELECT @[prefix]moysklad_processingPlans.*, @[prefix]specification.modificationId as modificationId, @[prefix]specification.revision as specificationRevision, @[prefix]modifications.name as modificationName, @[prefix]pack.id as packId, @[prefix]pack.revision as packRevision
FROM @[prefix]moysklad_processingPlans
LEFT JOIN @[prefix]specification ON @[prefix]specification.id = @[prefix]moysklad_processingPlans.specificationId
LEFT JOIN @[prefix]modifications ON @[prefix]modifications.id = @[prefix]specification.modificationId
LEFT JOIN @[prefix]pack ON @[prefix]pack.id = (SELECT MAX(@[prefix]pack.id) FROM @[prefix]pack WHERE @[prefix]pack.modificationId = @[prefix]specification.modificationId AND archived = 0)
WHERE processingPlanId_moySklad IN(?a)', $processingPlansIds) : [];


// (
//     [id] => 21
//     [creationDate] => 2021-07-01 17:54:09
//     [createdBy] => 2
//     [specificationId] => 51
//     [processingPlanId_moySklad] => 4516d58a-f62b-11e9-0a80-048c0001baa7
//     [uuidHref] => https://online.moysklad.ru/app/#processingplan/edit?id=30dea7a3-da7c-11eb-0a80-00ed00285654
//     [archived] => 0
//     [modificationId] => 48
//     [specificationRevision] => 0
//     [modificationName] => Б1
//     [packId] => 39
//     [packRevision] => 2
// )

$ppToSpecAndMod = [];
foreach($specificationsUP as $s){
    $ppToSpecAndMod[$s['processingPlanId_moySklad']] = $s;
}


$productsUP = ($productsIds) ? engine::DB()->getAll('SELECT id, article, `name`, pathName, supplier, buyprice, uuidhref FROM lgf_products WHERE id IN(?a)', $productsIds) : [];
$products = [];
foreach($productsUP as $p){
    $products[$p['id']] = $p;
}

#engine::debug_printArray($ppToSpecAndMod);
#engine::debug_printArray($processingOrders[0]);

$contextDisableSSL=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
);

$factoryData = [
    'plus' => 'error',
    'minus' => 'error',
    'requests' => 'error',
    'bonus' => 'error',
];

//bitrix data here
$factoryData = @engine::getCache('A_SCHEDULE_DATA')['value'];
$factoryData['month']['profit'] = $factoryData['month']['plus'] + $factoryData['month']['minus'];
$factoryData['month']['bonus'] = $factoryData['month']['profit'] * 0.05;

$factoryData['pMonth']['profit'] = $factoryData['pMonth']['plus'] + $factoryData['pMonth']['minus'];
$factoryData['pMonth']['bonus'] = $factoryData['pMonth']['profit'] * 0.05;

$factoryData['year']['profit'] = $factoryData['year']['plus'] + $factoryData['year']['minus'];
$factoryData['year']['bonus'] = $factoryData['year']['profit'] * 0.05;

$plusNormativ = 10;
$GLOBALS['maxDiff'] = -1;
?>


<?php
ob_start();
?>

<div class="card card-success">



    <style>

        /*[data-tooltip] {
            position: relative;
        }
        [data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            width: 200px;
            height: auto;
            white-space: break-spaces;
            left: 0; top: 0;
            background: #007bff;
            color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.12),0 1px 2px rgba(0,0,0,.24);
            padding: 0.5em;
            pointer-events: none;
            opacity: 0;
            transition: 0.3s;
            border-radius: 15px;
            font-size: 18px;
            margin-top:15px;
            font-weight: normal;
            z-index: 99999;
        }
        [data-tooltip]:hover::after {
            opacity: 1;
            top: 1.3em;
        }

        [data-tooltipl] {
            position: relative;
        }
        [data-tooltipl]::after {
            content: attr(data-tooltipl);
            position: absolute;
            width: 200px;
            height: auto;
            white-space: break-spaces;
            left: -100px; top: 0;
            background: #007bff;
            color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.12),0 1px 2px rgba(0,0,0,.24);
            padding: 0.5em;
            pointer-events: none;
            opacity: 0;
            transition: 0.3s;
            border-radius: 15px;
            font-size: 18px;
            margin-top:15px;
            font-weight: normal;
            z-index: 99999;
        }
        [data-tooltipl]:hover::after {
            opacity: 1;
            top: 75px;
            left: -75px;
        }

        [data-tooltipr] {
            position: relative;
        }
        [data-tooltipr]::after {
            content: attr(data-tooltipr);
            position: absolute;
            width: 200px;
            height: auto;
            white-space: break-spaces;
            left: 0; top: 0;
            background: #007bff;
            color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.12),0 1px 2px rgba(0,0,0,.24);
            padding: 0.5em;
            pointer-events: none;
            opacity: 0;
            transition: 0.3s;
            border-radius: 15px;
            font-size: 18px;
            margin-top:15px;
            font-weight: normal;
            z-index: 99999;
        }
        [data-tooltipr]:hover::after {
            opacity: 1;
            top: -75px;
            left: 0;
        }*/

        .factory_schedule td{
            border: 0;
        }
        .factory_schedule th.date{
            text-align: center;
        }
        .factory_schedule th.article{
            min-width: 300px;
        }
        .factory_schedule th.inprocess{
            text-align: center;
        }
        .factory_schedule th.photo{
            text-align: center;
        }
        .factory_schedule td.sum{
            vertical-align: middle;
        }
        .factory_schedule th.sum{
            cursor: pointer;
            text-align: center;
        }


        .factory_schedule td.article{
            min-width: 350px;
        }

        .factory_schedule tr.cat-2 td.cat{
            background-color: #c6ff80;
            color: #000000a1;
            width: 5px;
        }
        .factory_schedule tr.cat-1 td.cat{
            background-color: #ffed80;
            color: #000000a1;
            width: 5px;
        }
        .factory_schedule  tr.cat-0 td.cat{
            background-color: #ff8080;
            color: #000000a1;
            width: 5px;
        }

        .factory_schedule  tr.cat-3 td.cat{
            background-color: #868686;
            width: 5px;
        }
        .factory_schedule  tr.cat-4 td.cat{
            background-color: #313131;
            width: 5px;
        }
        .factory_schedule  tr.cat-5 td.cat{
            background-color: #868686;
            width: 5px;
        }
        .factory_schedule  tr.cat-6 td.cat{
            background-color: #313131;
            width: 5px;
        }

        tr.cat-3 td.cat a, tr.cat-4 td.cat a, tr.cat-5 td.cat a, tr.cat-6 td.cat a, tr.cat-7 td.cat a{
            color: rgba(255, 255, 255, 0.63) !important;
            text-decoration: none;
        }



        tr.cat td.cat a{
            color: inherit;
            text-decoration: none;
        }

        td.cat{
            padding: 0 !important;
            text-align: center;
            vertical-align: middle;
            font-size: 14px;
            width: 30px;
        }
        td.cat a{
            color: #000000a1;
            text-decoration: none;
        }

        .factory_schedule  td.photo{
            background: #fff;
            text-align: center;
            width: 50px;
            vertical-align: middle;
        }
        .factory_schedule  td.metall{
            white-space: nowrap;
        }
        .factory_schedule  td.plywood{
            white-space: nowrap;
        }

        .factory_schedule  tr{
            height: 100px;
        }
        .factory_schedule thead tr{
            height: auto;
        }
        .factory_schedule  tr:nth-child(even) {
            background: #f5f5f5;
        }

        .factory_schedule span.b {
            display: block;
        }

        .factory_schedule span.daysInWork{
            width: 100%;
            font-size: 3em;
            text-align: center;
        }
        .factory_schedule span.startDate{
            width: 100%;
            font-size: 1.3em;
            text-align: center;
            white-space: nowrap;
        }

        .factory_schedule span.inWork{
            width: 100%;
            font-size: 3em;
            text-align: center;
        }
        .factory_schedule span.inOrder{
            width: 100%;
            font-size: 1.3em;
            text-align: center;
        }


        .factory_schedule span.orderName{
            width: 100%;
            font-size: 1.2em;
            text-align: left;
            height: 22px;
        }
        .factory_schedule span.articleAndName{
            width: 100%;
            font-size: 1.8em;
            line-height: 1.2em;
            text-align: left;
            height: 78px;
            white-space: pre-line;
            word-break: break-all;
        }
        .factory_schedule span.sum{
            width: 100%;
            text-align: center;
            font-size: 22px;
            display: block;
            white-space: nowrap;
        }
        .factory_schedule span.subsum{
            width: 100%;
            text-align: center;
            font-size: 18px;
            display: block;
            white-space: nowrap;
            word-break: break-all;
        }
        .factory_schedule span.fontSizeGroup1{
            font-size: 20pt !important;
            line-height: 20pt !important;
        }
        .factory_schedule span.fontSizeGroup2{
            font-size: 18pt !important;
            line-height: 18pt !important;
        }
        .factory_schedule span.fontSizeGroup3{
            font-size: 16pt !important;
            line-height: 16pt !important;
        }
        .factory_schedule span.fontSizeGroup4{
            font-size: 14pt !important;
            line-height: 14pt !important;
        }
        .factory_schedule span.article{
            font-weight: 800;
        }

        .factory_schedule span.links {
            width: 100%;
            line-height: 15px;
        }

        .factory_schedule .switchable {

        }
        .factory_schedule .switchable.on {

        }
        .factory_schedule .switchable.off{
            display: none;
        }

        .anchorOrder{
            background: #ffeded !important;
        }

    </style>

    <div class="card-body table-responsive p-0" style="overflow-y: hidden !important;">
        <?php
        global $startMonth, $thisMonth, $superCat, $limit, $monthNames, $superCatStart;

        $vPro = 2400000000;
        if(array_key_exists('vpro', $_GET) && $_GET['vpro'] > 0) $vPro = $_GET['vpro'];

        $limit = 2400000000;
        if(array_key_exists('factoryThroughput', $_GET)) $limit = $_GET['factoryThroughput'];

        $GLOBALS['superMonth'] = [
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            8 => 0,
            9 => 0,
            10 => 0,
            11 => 0,
        ];

        $GLOBALS['monthNames'] = [
            0 => 'Январь',
            1 => 'Февраль',
            2 => 'Март',
            3 => 'Апрель',
            4 => 'Май',
            5 => 'Июнь',
            6 => 'Июль',
            7 => 'Август',
            8 => 'Сентябрь',
            9 => 'Октябрь',
            10 => 'Ноябрь',
            11 => 'Декабрь',
        ];

        $GLOBALS['monthNamesS'] = [
            0 => 'января',
            1 => 'февраля',
            2 => 'марта',
            3 => 'апреля',
            4 => 'мая',
            5 => 'июня',
            6 => 'июля',
            7 => 'августа',
            8 => 'сентября',
            9 => 'октября',
            10 => 'ноября',
            11 => 'декабря',
        ];


        $GLOBALS['startMonth'] = 9;
        $GLOBALS['thisMonth'] = $GLOBALS['startMonth'];
        $GLOBALS['superCatStart'] = 3;
        $GLOBALS['superCat'] = $GLOBALS['superCatStart'];

        function getCatAfter2($orderSum){
            if($GLOBALS['superMonth'][$GLOBALS['thisMonth']] > $GLOBALS['limit']){
                $GLOBALS['thisMonth']++;
                $GLOBALS['superCat']++;
                if($GLOBALS['thisMonth'] > 11) $GLOBALS['thisMonth'] = 0;
                return $GLOBALS['superCat'];
            }else{
                $GLOBALS['superMonth'][$GLOBALS['thisMonth']] += $orderSum;
                return $GLOBALS['superCat'];
            }
        }

        $GLOBALS['normativ'] = 0;
        $GLOBALS['normativPlus'] = 0;
        function getCategory($days){
            if($days <= ((($GLOBALS['normativ']-$GLOBALS['normativPlus'])/3)*1)) return 2;
            if($days <= ((($GLOBALS['normativ']-$GLOBALS['normativPlus'])/3)*2)) return 1;
            return 0;
        }

        foreach($processingOrders as $k => $order){
            $processingOrders[$k]['diff'] = helper::datediff(strtotime(explode(' ', $order['moment'])[0]));
            if($GLOBALS['maxDiff'] < $processingOrders[$k]['diff']) $GLOBALS['maxDiff'] = $processingOrders[$k]['diff'];
        }

        $GLOBALS['normativ'] = $GLOBALS['maxDiff'] + $plusNormativ;
        $GLOBALS['normativPlus'] = $plusNormativ;
        foreach($processingOrders as $k => $order){
            $processingOrders[$k]['cat'] = getCategory($processingOrders[$k]['diff']);
        }

        usort($processingOrders, function ($a, $b){
            return $b['diff'] - $a['diff'];
        });

        $productIds = [];

        $GLOBALS['firstFromCat3'] = -1;
        $dateLimiter = strtotime('2021-06-03');
        foreach($processingOrders as $k => $order){
            $moment = strtotime(explode(' ', $order['moment'])[0]);
            if($dateLimiter < $moment){
                if($GLOBALS['firstFromCat3'] < $order['diff']) $GLOBALS['firstFromCat3'] = $order['diff'];
                $product = $products[$order['productId']];
                $buyPriceO = $product['buyprice'];
                $toi = $order['quantity'] - $order['ready'];
                $sum = $toi * $buyPriceO;
                $processingOrders[$k]['cat'] = getCatAfter2($sum);
            }
        }

        function getCategoryNew($days){
            if($days - $GLOBALS['firstFromCat3'] <= ((($GLOBALS['maxDiff'] - $GLOBALS['firstFromCat3'])/3)*1)) return 2;
            if($days - $GLOBALS['firstFromCat3'] <= ((($GLOBALS['maxDiff'] - $GLOBALS['firstFromCat3'])/3)*2)) return 1;
            return 0;
        }


        foreach($processingOrders as $k => $order){
            $moment = strtotime(explode(' ', $order['moment'])[0]);
            if($dateLimiter >= $moment){
                $processingOrders[$k]['cat'] = getCategoryNew($order['diff']);
                $processingOrders[$k]['superMonth'] = ($GLOBALS['startMonth'] - $processingOrders[$k]['cat']);
            }else{
                $processingOrders[$k]['superMonth'] = ($GLOBALS['startMonth'] + $processingOrders[$k]['cat'] - $GLOBALS['superCatStart']);
                if($processingOrders[$k]['superMonth'] > 11) $processingOrders[$k]['superMonth'] = abs($processingOrders[$k]['superMonth'] - 11);

            }
        }

        $productIds = [];

        if(@$_GET['groupByArticle'] != 'disable'){
            foreach($processingOrders as $k => $order){ #Гениальная сортировка в пределах одной категории
                if(!@$productIds[$order['cat']][$order['productId']]) $productIds[$order['cat']][$order['productId']] = ($k + 1) * 1000;
                else $productIds[$order['cat']][$order['productId']] += 1;
                $processingOrders[$k]['sort'] = $productIds[$order['cat']][$order['productId']];

            }
            usort($processingOrders, function ($a, $b){
                if ($a['sort'] == $b['sort']) {
                    return 0;
                }
                return ($a['sort'] < $b['sort']) ? -1 : 1;
            });
        }


        echo '<table class="table factory_schedule fixtable" >';
        echo '<thead>';
        echo '<th class="cat"></th>';
        echo '<th class="date">Дата запуска</th>';
        echo '<th class="inprocess">В работе</th>';
        echo '<th class="photo">Фото</th>';
        echo '<th class="article">Артикул и наименование</th>';

        echo '<th class="pds switchable off" data-tooltip="Предполагаемая дата сдачи из МС">П. дата сдачи</th>';
        echo '<th class="metall switchable off">Металл</th>';
        echo '<th class="plywood switchable off">Фанера</th>';
        echo '<th class="set switchable off">Комплект</th>';
        echo '<th class="docs switchable off">Документы</th>';

        #echo '<th class="actions">Действия</th>';
        echo '<th class="sum switchableSwitch">Сумма<i class="fas fa-arrows-alt-h"></i></th>';
        echo '<th class="cat"></th>';

        echo '</thead>';
        echo '<tbody>';

        $sumNI = 0;
        //$ready = 0;

        function getMFColor($text){
            switch(mb_strtolower($text)){
                case 'готово':
                    return 'color: #34fa4b;';
                    break;
                case 'запущено':
                    return 'color: #f0fa34;';
                    break;
                case 'в работе':
                    return 'color: #3486fa;';
                    break;
                case 'проблема':
                    return 'color: #ff0000;';
                    break;
                default:
                    return '';
            }
        }

        $catSum = [];

        $i = 0;
        if(!defined('EXCEL')) foreach($processingOrders as $k=>$order){
            $i++;
            if(!array_key_exists($order['cat'], $catSum)) $catSum[$order['cat']] = 0;
            $product = $products[$order['productId']];
            $pdm = @$ppToSpecAndMod[$order['processingPlan']] ?? false;
            $buyPriceO = $product['buyprice'];

            $toi = $order['quantity'] - $order['ready'];
            if($toi < 0) $toi = 0;
            if($product['supplier'] == '098c1160-8cbd-4533-bcf3-b37b38f96a29') $buyPrice = (int) ($buyPriceO / 2); else $buyPrice = $buyPriceO;
            $orderSum =$buyPrice * $toi;
            $catSum[$order['cat']] += $orderSum;
            $sumNI += $orderSum; // Сумма нарастающим итогом

            $orderpr = $order['quantity'] * $buyPriceO / 100;
            $st = 'Закупочная заказа = '.engine::format_price($buyPriceO).' * '.$order['quantity']. ' = '.engine::format_price($orderpr);
            if($product['supplier'] == '098c1160-8cbd-4533-bcf3-b37b38f96a29') $st .= ' / 2 = '.engine::format_price($orderpr/2).', так как поставщик МК';


            echo '<tr class="tr cat-'.$order['cat'].'" id="order'.$order['id'].'">';
            echo '<td class="cat"><a href="'.$_SERVER['REQUEST_URI'].'#order'.$order['id'].'">'.$i.'</a></td>';

            echo '<td class="date">
<span class="b daysInWork n" data-tooltipr="Дней в работе">'.$order['diff'].'</span>
<span class="b startDate" data-tooltip="Дата создания заказа">'.date("Y-m-d", strtotime($order['moment'])).($order['cat'] > 2 ? '<br>'.$GLOBALS['monthNames'][$order['superMonth']] : '').'</span>
</td>';

            echo '<td class="inprocess">
<span class="b inWork" data-tooltipr="Осталось изготовить '.$toi.' шт.">'.($toi).'</span>
<span class="b inOrder" data-tooltip="Изготовлено '.$order['ready'].' из '.$order['quantity'].'">'.$order['ready'].' из '.$order['quantity'].'</span>
</td>';


            echo '<td class="photo" data-tooltip="'.$st.'"><img loading="lazy" style="max-width: 125px;" src="https://office.lebergroup.ru:9994/tg/'.$product['article'].'.jpg" onerror="this.onerror=null;this.width=100;this.src=\'https://tech.lebergroup.ru/moysklad/pages/schedule/notfound.png\';" ></td>';

            $pdmAdd = '';


            $pdmAddLArrray = [];
            $pdmAddL = '';
            if(@$pdm && @$pdm['specificationId']){
                $pdmAdd .= '<a class="dropdown-item" target="_blank" href="/index.php?component=factory&page=specification&id='.$pdm['specificationId'].'">Спецификация R'.$pdm['specificationRevision'].'</a>';
                $pdmAddLArrray[] = 'спец';
            }
            if(@$pdm && @$pdm['packId']){
                $pdmAdd .= '<a class="dropdown-item" target="_blank" href="/index.php?component=warehouse&page=package&id='.$pdm['packId'].'">Упаковка R'.$pdm['packRevision'].'</a>';
                $pdmAdd .= '<div class="dropdown-divider"></div>';

                $fileName = urlencode('Заказ '.$order['name'].' этикетки');
                $fileNameK = urlencode('Заказ '.$order['name'].' комплектация');
                $on = urlencode($order['name']);


                $pdmAdd .= '<a class="dropdown-item" href="/index.php?component=system&do=generateXLSX&template=packinglist&type=xlsx&id='.$pdm['packId'].'&filename='.$fileNameK.'&orderName='.$on.'">Комплектация R'.$pdm['packRevision'].'</a>';
                $pdmAdd .= '<a class="dropdown-item" href="/index.php?component=system&do=generateXLSX&template=package&type=pdfcropped&id='.$pdm['packId'].'&filename='.$fileName.'&orderName='.$on.'">Этикетки R'.$pdm['packRevision'].'</a>';
                $pdmAddLArrray[] = 'упак';
            }
            if(count($pdmAddLArrray) > 0) $pdmAddL = ' ('.implode(' + ', $pdmAddLArrray).')';



            $nameFontSizeGroup = ((int)(mb_strlen($product['name'], 'UTF-8') / 30)) + 1;
            echo '<td class="article">
<span class="b orderName" data-tooltipr="Имя заказа">'.$order['name'].'</span>
<span class="b articleAndName fontSizeGroup'.$nameFontSizeGroup.'""><b data-tooltipr="Артикул товара [модификация товара]">'.$product['article'].' '.($pdm ? '['.$pdm['modificationName'].'] ' : ' | ').'</b><span data-tooltipr="Наименование товара">'.$product['name'].'</span></span>
<span class="b links">
<a href="'.$order['uuidHref'].'" target="_blank" data-tooltip="Ссылка на заказ на производство в МС. Откроется в новой вкладке.">Заказ на производство</a> | 
<a href="https://online.moysklad.ru/app/#ProcessingPlan/edit?id='.$order['processingPlan'].'" target="_blank" data-tooltip="Ссылка на техкарту в МС. Откроется в новой вкладке.">Техкарта</a> | 
<a href="'.$product['uuidhref'].'" target="_blank" data-tooltip="Ссылка на товар в МС. Откроется в новой вкладке.">Товар</a> | 
     <a href="#" target="_blank" data-toggle="dropdown" aria-expanded="false">PDM'.$pdmAddL.'</a>
     <div class="dropdown-menu" role="menu" style="">
       <a class="dropdown-item" target="_blank" href="/index.php?component=moysklad&page=product&productId='.$order['productId'].'">Товар</a>
        '.$pdmAdd.'
     </div>
</span></td>';

            echo '<td class="pds switchable off">'.($order['attr_pds'] ? date("Y-m-d", strtotime($order['attr_pds'])) : '').'</td>';
            echo '<td class="metall switchable off" style="'.getMFColor($order['attr_metal']).'">'.$order['attr_metal'].'</td>';
            echo '<td class="plywood switchable off" style="'.getMFColor($order['attr_plywood']).'">'.$order['attr_plywood'].'</td>';
            echo '<td class="set switchable off">'.$order['attr_set'].'</td>';
            echo '<td class="docs switchable off">'.$order['attr_docs'].'</td>';

            echo '<td class="sum dt-l" data-tooltipl="Сумма в работе нарастающим итогом в тысячах рублей"><span class="sum">'.number_format((int) ($sumNI / 100), 0, '', ' ').'₽</span><span class="subsum">'.number_format((int) ($catSum[$order['cat']] / 100), 0, '', ' ').'₽</span></td>';
            echo '<td class="cat"></td>';

            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';











        ?>
    </div>
</div>

<?php


$lastSumInLastMonth = end($catSum);



$table = ob_get_clean();
$date = date('Y-m-d');


#$f = strtotime($date . ' + '.($GLOBALS['maxDiff'])." days");

$monthsCount = count($catSum) - 2;
$f = strtotime($date . ' + '.($monthsCount)." months");;

$nf_month = date('m', $f);
$nf_year = date('Y', $f);

$nf_daysInMonth =  cal_days_in_month(CAL_GREGORIAN, $nf_month, $nf_year);

$nf_coeff = $lastSumInLastMonth / $vPro;
$nf_nn = $nf_daysInMonth * $nf_coeff;
$nf_normativ = 0;



#$oj = date('d m', strtotime($date . ' + '.($maxDiff+$plusNormativ)." days"));
#$ok = explode(' ', $oj);
#$ol = $ok[0] . ' ' . $GLOBALS['monthNamesS'][$ok[1] - 1];

#echo $nf_year.'-'.$nf_month.'-01';
$date2 = strtotime($nf_year.'-'.($nf_month - 1).'-01');
$date2d = date('Y-m-d', $date2);

$ojsource = strtotime($date2d . ' + '.((int)$nf_nn+$nf_normativ + 1)." days");
$oj = date('d m', $ojsource);
$ok = explode(' ', $oj);
$ol = $ok[0] . ' ' . $GLOBALS['monthNamesS'][$ok[1] - 1];
#$GLOBALS['maxDiff'] = $processingOrders[0]['diff'];

$GLOBALS['estimated'] = helper::datediff($ojsource);

?>




<div class="row">
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-gradient-green">
            <div class="inner">
                <h4>Ожидаемый срок</h4>
                <h3 data-tooltip="<дней с добавления самого старого непроизведенного заказа> + <норматив>: <?=$GLOBALS['maxDiff']?> + <?=$plusNormativ?>"><?=$GLOBALS['estimated']?> дн. (<?=$ol?>)</h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-gradient-green">
            <div class="inner">
                <h4>В работе</h4>
                <h3 data-tooltip="Сумма закупочных стоимостей изделий в работе"><?=number_format((int) ($sumNI / 100), 0, '', ' ').' ₽'?></h3>
            </div>
        </div>
    </div>
    <?
    $mm =date('m');
    $thisMonthD = mb_strtolower($monthNames[$mm-1]);
    $previousMonth = mb_strtolower($monthNames[$mm-2]);
    $thisYear = date('Y');
    ?>
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-gradient-green">
            <div class="inner">
                <h4>Изготовлено</h4>
                <h3 style="line-height: 1 !important;"><?=number_format((int) $factoryData['month']['profit'], 0, '', ' ').' ₽'?> (<?=$thisMonthD?>)</h3>
                <h4 style="line-height: 0.7 !important;"><?=number_format((int) $factoryData['pMonth']['profit'], 0, '', ' ').' ₽'?> (<?=$previousMonth?>)</h4>
                <h4 style="line-height: 0.7 !important;"><?=number_format((int) $factoryData['year']['profit'], 0, '', ' ').' ₽'?> (<?=$thisYear?>)</h4>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-gradient-green">
            <div class="inner">
                <h4>Премия</h4>
                <h3 style="line-height: 1 !important;"><?=number_format((int) $factoryData['month']['bonus'], 0, '', ' ').' ₽'?> (<?=$thisMonthD?>)</h3>
                <!--<h4 style="line-height: 0.7 !important;"><?=number_format((int) $factoryData['pMonth']['bonus'], 0, '', ' ').' ₽'?> (<?=$previousMonth?>)</h4>
                <h4 style="line-height: 0.7 !important;"><?=number_format((int) $factoryData['year']['bonus'], 0, '', ' ').' ₽'?> (<?=$thisYear?>)</h4>-->
            </div>
        </div>
    </div>
</div>

<?=$table?>

<script>



    var switchableStatus = 'show';
    $('.switchableSwitch').on('click', function (e){
        if(switchableStatus == 'show'){
            $('.switchable').addClass('off');
            switchableStatus = 'hide';
            PDM.SetCookie('schedule_switcher', 'hide', 100);
        }else{
            $('.switchable').removeClass('off');
            switchableStatus = 'show';
            PDM.SetCookie('schedule_switcher', 'show', 100);
        }

    });
    $(document).ready(function (){
        if (PDM.ReadCookie('schedule_switcher') == 'hide') {
            switchableStatus = 'show';
            $('.switchableSwitch').trigger('click');
        }

        var hash = $(location).attr('hash');
        if(hash.length > 0){
            $(hash).addClass('anchorOrder');
        }
    })
</script>
