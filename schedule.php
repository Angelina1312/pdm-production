<?php

if(!defined('included')){

    if(!@$_COOKIE['schedule_switcher']) setcookie('schedule_switcher', 'hide', time()+60*60*24*30);

    engine::TEMPLATE()->setTagValue('pagename', 'График производства');

    module::getModuleClass('breadcrumbs')->addElement('#!', 'Производство ');
    mod_breadcrumbs::$disableDefault = true;
    $fileName = urlencode('График производства '.date('d-m-Y'));
    module::getModuleClass('breadcrumbs')->addElement('/index.php?component=factory&page=schedule', 'График производства');
    module::getModuleClass('breadcrumbs')->addElement('/index.php?component=system&do=generateXLSX&template=schedule&type=xlsx&mode=default&id=15&filenameS='.$fileName, '<i class="fa fa-file-excel"></i> excel');
    $schedule = engine::getClass('schedule');

    $processingOrders = [];
    $factoryData = $schedule->getFactoryData();
    $revision = $schedule->getSchedule2();
    $processingOrders = $revision['orders'];
    $products = $revision['products'];

    $_statuses = [
        0 => 'В графике',
        1 => 'В работе',
        2 => 'Упаковка',
    ];

    $statuses = false;
    if(array_key_exists('status', $_GET)){
        $statuses = explode(',', $_GET['status']);
    }
}



$processingPlansIds = array_column($processingOrders, 'processingPlan');

$specificationsUP = ($processingPlansIds) ? engine::DB()->getAll('
SELECT @[prefix]moysklad_processingPlans.*, @[prefix]specification.modificationId as modificationId, @[prefix]specification.revision as specificationRevision, @[prefix]modifications.name as modificationName, @[prefix]pack.id as packId, @[prefix]pack.revision as packRevision
FROM @[prefix]moysklad_processingPlans
LEFT JOIN @[prefix]specification ON @[prefix]specification.id = @[prefix]moysklad_processingPlans.specificationId
LEFT JOIN @[prefix]modifications ON @[prefix]modifications.id = @[prefix]specification.modificationId
LEFT JOIN @[prefix]pack ON @[prefix]pack.id = (SELECT MAX(@[prefix]pack.id) FROM @[prefix]pack WHERE @[prefix]pack.modificationId = @[prefix]specification.modificationId AND archived = 0)
WHERE processingPlanId_moySklad IN(?a)', $processingPlansIds) : [];

$ppToSpecAndMod = [];
foreach($specificationsUP as $s){
    $ppToSpecAndMod[$s['processingPlanId_moySklad']] = $s;
}

$lastSpecificationIds = engine::DB()->getCol('SELECT id FROM @[prefix]specification t1 WHERE revision = (SELECT MAX(revision) FROM @[prefix]specification t2 WHERE t2.id = t1.id)');

$GLOBALS['maxDiff'] = -1;

ob_start();

?>

<div class="card card-success">

    <div class="card-body table-responsive p-0" style="overflow-y: hidden !important;">
        <?php
        global $startMonth, $thisMonth, $superCat, $limit, $monthNames, $superCatStart;


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




        echo '<table class="table factory_schedule fixtable" >';
        echo '<thead>';
        echo '<th class="cat"></th>';
        echo '<th class="date switchable off">Дата запуска</th>';
        echo '<th class="date">Дата сдачи</th>';
        echo '<th class="inprocess">В работе</th>';
        echo '<th class="photo">Фото</th>';
        echo '<th class="article">Артикул и наименование</th>';

        #echo '<th class="pds switchable off" data-tooltip="Предполагаемая дата сдачи из МС">П. дата сдачи</th>';
        #echo '<th class="metall switchable off">Металл</th>';
        #echo '<th class="plywood switchable off">Фанера</th>';
        #echo '<th class="set switchable off">Комплект</th>';
        #echo '<th class="docs switchable off">Документы</th>';
        echo '<th class="orderStatus">Статус</th>';

        #echo '<th class="actions">Действия</th>';
        echo '<th class="sum switchableSwitch">Сумма<i class="fas fa-arrows-alt-h"></i></th>';
        echo '<th class="cat"></th>';

        echo '</thead>';
        echo '<tbody>';

        $sumNI = 0;
        //$ready = 0;

        /**
         * @param $text
         * @return string
         */
        function getMFColor($text){
            return match (mb_strtolower($text)) {
                'готов' => 'color: #fff; background-color: #40c728; text-align: center;',
                'запущен' => 'color: #fff; background-color: #8034fa; text-align: center;',
                'нет' => 'color: #000; background-color: #ddd; text-align: center;',
                'в работе' => 'color: #fff; background-color:  #3486fa; text-align: center;',
                'проблема' => 'color: #fff; background-color:  #fba200; text-align: center;',
                default => '',
            };
        }

        function getMFColor2($text){
            return match ($text) {
                'В графике' => 'color: #000; background-color: #e0e0e0; text-align: center;',
                'В работе' => 'color: #fff; background-color: #3d84f5; text-align: center;',
                'Упаковка' => 'color: #fff; background-color:  #4fc059; text-align: center;',
                default => '',
            };
        }

        $catSum = [];
        $week = -1;
        $color = 'odd';
        $i = 0;
        $lastWeek = -1;
        $weekColor = 0;

        $weekSum = [];
        if(!defined('EXCEL')) foreach($processingOrders as $k=>$order){

            if($statuses !== false){
                $found = false;
                if($statuses[0] == -1){
                    if(in_array($order['orderStatus'], $_statuses)) continue;
                }else{
                    foreach($statuses as $status){
                        if($order['orderStatus'] == @$_statuses[$status]) $found = true;
                    }
                    if(!$found) continue;
                }

            }

            if($week != $order['week']) {
                $week = $order['week'];
                $color = ($color == 'odd') ? 'odd' : 'even';
                if($order['redzone']) $color = 1;
            }
            if(!$order['redzone'] && $lastWeek != $order['week']){
                $weekColor++;
                $lastWeek = $order['week'];
                if($weekColor > 10) $weekColor = 1;
            }
            if($order['redzone']) $color = 1; else $color = (($week % 2) ? 'even' : 'odd');
            $i++;
            #if(!array_key_exists($order['cat'], $catSum)) $catSum[$order['cat']] = 0;
            $product = $products[$order['productId']];
            $pdm = @$ppToSpecAndMod[$order['processingPlan']] ?? false;
            $buyPriceO = $product['buyprice'];


            $toi = $order['quantity'] - $order['ready'];
            if($product['supplier'] == '098c1160-8cbd-4533-bcf3-b37b38f96a29') $buyPrice = (int) ($buyPriceO / 2); else $buyPrice = $buyPriceO;
            $orderSum =$buyPrice * $toi;
            if(!array_key_exists($week, $weekSum)) $weekSum[$week] = 0;
            $weekSum[$week] += $orderSum;
            #$catSum[$order['cat']] += $orderSum;
            $sumNI += $orderSum; // Сумма нарастающим итогом

            $orderpr = $order['quantity'] * $buyPriceO / 100;
            $st = 'Закупочная заказа = '.engine::format_price($buyPriceO).' * '.$order['quantity']. ' = '.engine::format_price($orderpr);
            if($product['supplier'] == '098c1160-8cbd-4533-bcf3-b37b38f96a29') $st .= ' / 2 = '.engine::format_price($orderpr/2).', так как поставщик МК';


            echo '<tr class="tr cat-'.@$color.' cat-week-'.$order['week'].' cat_weekP_'.$weekColor.'" id="order'.$order['id'].'">';
            echo '<td class="cat"><a href="'.$_SERVER['REQUEST_URI'].'#order'.$order['id'].'">'.$i.'</a></td>';

            $estimated = $order['endDateTime'];
            $estimatedDiff = helper::aDatediff($estimated); //(@$revision['params']['generateOnDate'] && defined('included')) ? helper::aDatediff($estimated, @$revision['params']['generateOnDate']) :

            echo '<td class="switchable off">
'.($order['startDateTime'] != $order['realStartDateTime'] && engine::USERS()->isLoggedIn() ? '<span class="b startDate switchable off" data-tooltip="Дата создания заказа">'.date('d', $order['realStartDateTime']).' '.$GLOBALS['monthNamesS'][date('m', $order['realStartDateTime'])-1].' (PDM)<br>'.date('H:i:s', $order['realStartDateTime']).'</span>' : '').'
'.(engine::USERS()->isLoggedIn() ? '' : '<br>').'
<span class="b startDate switchable off" data-tooltip="Дата создания заказа">'.date('d', $order['startDateTime']).' '.$GLOBALS['monthNamesS'][date('m', $order['startDateTime'])-1].' (MS)<br>'.date('H:i:s', $order['startDateTime']).'</span></td>';
            echo '<td class="date">
<!--span class="b startDate" data-tooltip="Дата создания заказа">'.date("Y-m-d", strtotime($order['moment'])).'</span-->
<span class="b daysInWork n" data-tooltipr="Осталось">'.$estimatedDiff.'<span class="smpd"> д</span></span>
<span class="b endDate">'.date('d', $order['endDateTime']).' '.$GLOBALS['monthNamesS'][date('m', $order['endDateTime'])-1].'</span>
</td>';

            echo '<td class="inprocess">
<span class="b inWork" data-tooltipr="Осталось изготовить '.$toi.' шт.">'.($toi).'<span class="smpd"> шт</span></span>
<span class="b inOrder" data-tooltip="Изготовлено '.$order['ready'].' из '.$order['quantity'].'">'.$order['ready'].' из '.$order['quantity'].' шт</span>
</td>';

            echo '<td class="photo" data-tooltip="'.$st.'"><img loading="lazy" style="max-width: 125px;" src="https://office.lebergroup.ru:9994/tg/'.$product['article'].'.jpg" onerror="this.onerror=null;this.width=100;this.src=\'https://tech.lebergroup.ru/moysklad/pages/schedule/notfound.png\';" ></td>';

            $pdmAdd = '';


            $pdmAddLArrray = [];
            $pdmAddL = '';

            $thisIsLastRevisionInModifcation = false;

            if(@$pdm && @$pdm['specificationId']){
                $thisIsLastRevisionInModifcation = in_array($pdm['specificationId'], $lastSpecificationIds);
                #if(!$thisIsLastRevisionInModifcation)  echo '❗️123123'; else echo 132;
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

            $pdmContextAdd = '';
            if(!@$pdm && @$pdm['modificationId'] && !$thisIsLastRevisionInModifcation) {
                $pdmAddL = '❗️'.$pdmAddL;
                $pdmContextAdd .= '<div class="dropdown-divider"></div><span class="dropdown-item-text">Используется техкарта не последней ревизии</span>';
            }

            if(@$pdm['modificationName'] == 'Б1') {
                $pdmAddL = '⚠️'.$pdmAddL;
                $pdmContextAdd .= '<div class="dropdown-divider"></div><span class="dropdown-item-text">⚠️Используется модификация Б1</span>';
            }

            if(!@$pdm['modificationId']){
                $pdmAddL = '⚠️'.$pdmAddL;
                $pdmContextAdd .= '<div class="dropdown-divider"></div><span class="dropdown-item-text">⚠️Модификации нет в PDM</span>';
            }

            if(@$pdm['archived']){
                $pdmAddL = '⚠️'.$pdmAddL;
                $pdmContextAdd .= '<div class="dropdown-divider"></div><span class="dropdown-item-text">⚠️Тех. карта в архиве</span>';
            }

            // ограничение если есть техоперации
            if(@$order['ready'] > 0) {
                $pdmAddL = '⚠️'.$pdmAddL;
                $pdmContextAdd .= '<div class="dropdown-divider"></div><span class="dropdown-item-text">⚠В заказе есть техоперации</span>';
            }

            $pdmContextAdd .= '';
            $disableRecreate = '';
            if(@$order['ready'] > 1) {
                $disableRecreate = 'disabled';
            }
            $pdmAdd .= '<div class="dropdown-divider"></div><a class="dropdown-item recreateLink '.$disableRecreate.'" href="#" data-orderId="'.$order['id'].'">Пересоздать с другой тех. картой</a>';

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
   '.$pdmAdd.$pdmContextAdd.'
</div>
</span></td>';

            #echo '<td class="pds switchable off">'.($order['attr_pds'] ? date("Y-m-d", strtotime($order['attr_pds'])) : '').'</td>';
            #echo '<td class="metall switchable off" style="'.getMFColor($order['attr_metal']).'">'.$order['attr_metal'].'</td>';
            #echo '<td class="plywood switchable off" style="'.getMFColor($order['attr_plywood']).'">'.$order['attr_plywood'].'</td>';
            #echo '<td class="set switchable off">'.$order['attr_set'].'</td>';
            #echo '<td class="docs switchable off">'.$order['attr_docs'].'</td>';

            echo '<td class="orderStatus" style="'.getMFColor2($order['orderStatus']).'">'.($order['orderStatus']).'</td>';

            #$s = $catSum[$order['cat']];
            $s = 0;
            #if($order['cat'] > 0 && $order['cat'] < 4) $s = @$catSum[1] + @$catSum[2] + @$catSum[3];

            echo '<td class="sum dt-l" data-tooltipl="Сумма в работе нарастающим итогом в тысячах рублей"><br>
                <span class="subsum">'.engine::format_price($buyPriceO * $order['quantity'] / 100).'₽</span>
                <span class="sum">'.number_format((int) ($sumNI / 100), 0, '', ' ').'₽</span>
                <span class="sum" style="color: transparent">W: '.number_format((int) ($weekSum[$week] / 100), 0, '', ' ').'₽</span>
                </td>';
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




?>



<?php if(!defined('included')){?>
    <div class="modal orderRecreatingModal" tabindex="-1" role="dialog">
        <form class="form">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Пересоздание заказа</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы хотите пересоздать заказ с другой техкартой. Проверьте данные, выберите нужную техкарту и нажмите "Пересоздать".</p>
                        <p><b>Номер заказа:</b> <span id="recreateOrder_orderName"></span></p>
                        <p><b>Товар:</b> <span id="recreateOrder_orderProduct"></span></p>
                        <select class="form-select" id="recreateOrder_PPs"></select>
                        <input type="hidden" id="recreateOrder_orderId">
                    </div>
                    <div class="modal-footer">
                        <!--button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button-->
                        <button type="button" class="btn btn-primary" id="recreateOrder_submit">Пересоздать</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
<div class="row">
    <div class="col-lg-3 col-xs-6">
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
    $previousMonth = mb_strtolower((((int)$mm) == 1 ? $monthNames[12-1] : $monthNames[$mm-2]));
    $thisYear = date('Y');
    ?>
    <div class="col-lg-6 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-gradient-green">
            <div class="inner">
                <h4>Изготовлено</h4>
                <h3 style="line-height: 1 !important;"><?=number_format((int) $factoryData['month']['profit'], 0, '', ' ').' ₽'?> (<?=$thisMonthD?>)</h3>
                <h4 style="line-height: 0.7 !important;"><?=number_format((int) $factoryData['pMonth']['profit'], 0, '', ' ').' ₽'?> (<?=$previousMonth?>), <?=number_format((int) $factoryData['year']['profit'], 0, '', ' ').' ₽'?> (<?=$thisYear?>)</h4>
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
<?php } ?>

<?=$table?>

<script>




    var switchableStatus = 'hide';
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

        function ppc (pp){
            let out = '';
            if(pp.current) out = "[Текущая] ";
            return out + pp.name;
        }

        $('#recreateOrder_PPs').on('change', function (e){
            if($("#recreateOrder_PPs option:selected").attr('disabled') == 'disabled'){
                $('#recreateOrder_submit').attr('disabled', 'disabled');
            }else{
                $('#recreateOrder_submit').attr('disabled', false);
            }
        });


        $('.recreateLink').on('click', function(e) {
            let orderId = e.target.getAttribute('data-orderId');

            $.ajax({
                method: "POST",
                url: "/ajax.php?component=factory&do=recreateOrder",
                dataType: "json",
                data: {action: 'getData', orderId: orderId}
            }).done(function (data) {
                console.log(data);
                if (data.status == true) {

                    $('#recreateOrder_PPs').text("");
                    $('#recreateOrder_orderName').text("");
                    $('#recreateOrder_orderProduct').text("");
                    $('#recreateOrder_orderId').val("");


                    for (let pp of data.data.pp) {
                        $('#recreateOrder_PPs').append($('<option>', {
                            value: pp.id,
                            text: ppc(pp),
                            //disabled: pp.cu   rrent,
                            selected: pp.current
                        }));
                    }

                    $('#recreateOrder_PPs').change();

                    $('#recreateOrder_orderName').append($('<a>', {
                        href: data.data.order.uuidHref,
                        text: data.data.order.name,
                        target: '_blank'
                    }))
                    $('#recreateOrder_orderProduct').append($('<a>', {
                        href: data.data.product.uuidHref,
                        text: "[" + data.data.product.article + "] " + data.data.product.name,
                        target: '_blank'
                    }))
                    $('#recreateOrder_orderId').val(orderId);


                    $('.orderRecreatingModal').modal();
                } else {
                    console.log(data);
                    alert(data.message)
                }
            });
        });

            //#recreateOrder_orderName
            //#recreateOrder_orderProduct
            //recreateOrder_PPs



        $('#recreateOrder_submit').on('click', function (e){
            $('#recreateOrder_submit').attr('disabled', 'disabled');
            $('#recreateOrder_PPs').attr('disabled', 'disabled');

            $.ajax({
                method: "POST",
                url: "/ajax.php?component=factory&do=recreateOrder",
                dataType: "json",
                data: { action: 'recreate', orderId: $('#recreateOrder_orderId').val(), newPp: $('#recreateOrder_PPs').val() }
            }).done(function( actionData ) {
                if(actionData.status == true){
                    alert(actionData.data);
                    $('.orderRecreatingModal').modal('hide');
                    location.reload();
                }else{
                    alert(actionData.message);
                    $('.orderRecreatingModal').modal('hide');
                }
                console.log(actionData);
            });
        });

        if (PDM.ReadCookie('schedule_switcher') == 'show') {
            switchableStatus = 'hide';
            $('.switchableSwitch').trigger('click');
        }

        $('.action_recreateOrder').on('click', function (e){
            $('.orderRecreatingModal').modal();
        });

        var hash = $(location).attr('hash');
        if(hash.length > 0){
            switchableStatus = 'hide';
            $(hash).addClass('anchorOrder');
        }

    })
</script>
