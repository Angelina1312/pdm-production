<?php
if (!@$_GET['id']) throw new Exception('Не указан id спецификации');
$calculation = engine::FACTORY()->coeffs->getCalculationById($_GET['id']);
if(!$calculation) throw new Exception('Спецификация не найдена');
$d = $calculation['jsonData'];

$coatingsFromDB = $d['coatingsFromDB'];
$materialsFromDB = $d['materialsFromDB'];
$materialsLittleArray = $d['materialsLittleArray'];
$coatingsLittleArray =  $d['coatingsLittleArray'];
$allCoeffSum =  $d['allCoeffSum'];
$coeffSums =  $d['coeffSums'];
$materialsSum = $d['materialsSum'];
$coatingsSum = $d['coatingsSum'];
$ssp = $d['ssp'];
$pp = $d['pp'];
$profitCoeff = $d['profitCoeff'];

$calculationCreatedBy = engine::USERS()->getUserById($calculation['createdBy']);

// users id
$usersId = [
        2, // aristov
        6, // inandaf
        38, // olga
        18, //aa
        34, // alina
        32, // lichagin
       62 // angelina for test
];

$currentUser = engine::USERS()->getCurrentUser()['id'];


$specification = engine::DB()->getRow('SELECT * FROM @[prefix]specification WHERE id=?i', $calculation['specificationId']);
$modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id=?i', $specification['modificationId']);
$product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE id=?s', $modification['productId']);

engine::TEMPLATE()->setTagValue('pagename', 'Расчёт от '.date('Y-m-d H:i:s', strtotime($calculation['calculationDate'])));

module::getModuleClass('breadcrumbs')->addElement('/index.php?component=moysklad&page=product&productId='.$product['id'], $product['article']);;
module::getModuleClass('breadcrumbs')->addElement('#!', 'Модификация '.$modification['name']);;
module::getModuleClass('breadcrumbs')->addElement('/index.php?component=factory&page=specification&id='.$specification['id'], 'Спецификация '. 'R' . $specification['revision']);;
module::getModuleClass('breadcrumbs')->addElement('/index.php?component=factory&page=calculation&id='.$calculation['id'], 'Расчёт от '.date('Y-m-d H:i:s', strtotime($calculation['calculationDate'])));;

$fileName = urlencode($product['article'].' '.$modification['name'].'R'.$specification['revision'].' расчёт ');

ob_start();

foreach($materialsLittleArray as $material){

    foreach($materialsFromDB as $m){
        if($m['article'] == $material['article']) $msitem = $m;
    }
    $calculateField = helper::uomToField($material['uom']);
    echo '<tr>';
    echo '<td><a href="'.$msitem['meta']['uuidHref'].'">'.$material['article'].'</a></td>';
    echo '<td>'.$material['name'].'</td>';
    echo '<td class="text-right">'.engine::format_price(round($material[$material['calculateField']], 3)).'</td>';
    echo '<td>'.$material['uom'].'</td>';
    echo '<td class="text-right">'.engine::format_price(@$msitem['buyPrice']['value'] / 100).'</td>';
    echo '<td class="text-right">'.(@$material['coeffs']['price'] ? '1 / '.engine::format_price(@$material['coeffs']['price']) : '-').'</td>';
    echo '<td class="text-right '.(!$material['price'] ? 'badField' : '').'">'.engine::format_price($material['price'] / 100).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['sum']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffs']['zp']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['zp']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffs']['othodi']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['othodi']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffs']['komplektushie']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['komplektushie']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffs']['dostavka']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['dostavka']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffs']['nakladnie']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['nakladnie']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffs']['upakovka']).'</td>';
    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['upakovka']).'</td>';
    echo '<td class="text-right">'.engine::format_price(array_sum($material['coeffSums'])).'</td>';
    echo '</tr>';

}

$table = ob_get_clean();


//echo '$materialsSum = ' . $materialsSum . '<br>';
//echo '$coatingsSum = ' . $coatingsSum . '<br>';
//echo '$allCoeffSum = ' . $allCoeffSum . '<br>';
//echo '$ssp (чистая СС) = ' . $ssp . '<br>';
//echo '$pp (СС производства) ('.$profitCoeff.') = ' . $pp . '<br>';
//
//echo '$coeffSums = <pre>' . print_r($coeffSums, true) . '</pre><br>';
//echo '$coeffFinalSums = <pre>' . print_r($coeffFinalSums, true) . '</pre><br>';

?>
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-gradient-green">
                <div class="inner">
                    <h3><?=engine::format_datetime($calculation['calculationDate'])?></h3>

                    <p>Дата создания расчёта</p>
                </div>
                <div class="icon">
                    <i class="ion ion-bag"></i>
                </div>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-gradient-blue">
                <div class="inner">
                    <h3><?= $calculationCreatedBy['lastname'] . ' ' . mb_substr($calculationCreatedBy['firstname'], 0, 1) . '. ' . mb_substr($calculationCreatedBy['middlename'], 0, 1) . '. (<a href="#" style="color: #fff">' . $calculationCreatedBy['username'] . '</a>)' ?></h3>

                    <p>Инициатор</p>
                </div>
                <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                </div>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-cyan">
                <div class="inner">
                    <h3><?=engine::format_price($d['ssp'])?> <i class="fas fa-ruble-sign"></i></h3>

                    <p>Себестоимость производства</p>
                </div>
                <div class="icon">
                    <i class="ion ion-person-add"></i>
                </div>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-gradient-indigo">
                <div class="inner">
                    <h3 id="buyPrice" data-price="<?=engine::format_price($d['pp'])?>"><?=engine::format_price($d['pp'])?> <i class="fas fa-ruble-sign"></i></h3>

                    <p>Закупочная цена</p>
                    <a href="#" id="updateBuyPrice" class="btn btn-danger <?=
                  //  (in_array($currentUser, $usersId)) ? '' : 'disabled';
                    (($modification['draft'] == 0) && (in_array($currentUser, $usersId))) ? '' : 'disabled';
                    ?>">Обновить</a>
                </div>
                <div class="icon">
                    <i class="ion ion-pie-graph"></i>
                </div>
            </div>
        </div>
        <!-- ./col -->
    </div>
    <div class="card card-success">
        <div class="card-header">
            <div class="card-tools" style="float:left"><h3>Материалы = <?=engine::format_price($materialsSum);?> р.</h3></div>
            <div class="card-tools"><a target="_blank" href="/index.php?component=system&do=generateXLSX&template=calculation&type=xlsx&id=<?=$_GET['id']?>&filename=<?=$fileName?>&revision=<?=@$_GET['revision'] ?: 'R' . $specification['revision']?>&created=<?=@$_GET['created'] ?: $modification['created'] ?>&article=<?=@$_GET['article'] ?:$product['article'] ?>" class="btn btn-outline-default"><i class="far fa-file-excel"></i></a></div>
        </div>
        <div class="card-body">
            <table class="table fixtable">
                <thead>
                <th>Артикул</th>

                <th>Наименование</th>
                <th>Количество</th>
                <th>ед. изм.</th>
                <th>Закупочная</th>
                <th class="text-right">к.</th>
                <th>Стоимость</th>
                <th>Сумма</th>
                <th>к. ЗП</th>
                <th></th>
                <th>к. Отходы</th>
                <th></th>
                <th>к. Комплект.</th>
                <th></th>
                <th>к. Доставка</th>
                <th></th>
                <th>к. Накладные</th>
                <th></th>
                <th>к. Упаковка</th>
                <th></th>
                <th><b>к. Сумма</b></th>
                </thead>
                <tbody>
                <?php

                echo $table;

                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-blue">
        <div class="card-header">
            <h3>Покрытия = <?=engine::format_price($coatingsSum);?> р.</h3>
        </div>
        <div class="card-body">
            <table class="table fixtable">
                <thead>
                <th>Артикул</th>

                <th>Наименование</th>
                <th>Площадь</th>
                <th>Закупочная</th>
                <th class="text-right">к.</th>
                <th>Стоимость</th>
                <th>Сумма</th>
                <th>к. ЗП</th>
                <th></th>
                <th>к. Отходы</th>
                <th></th>
                <th>к. Комплект.</th>
                <th></th>
                <th>к. Доставка</th>
                <th></th>
                <th>к. Накладные</th>
                <th></th>
                <th>к. Упаковка</th>
                <th></th>
                <th><b>к. Сумма</b></th>
                </thead>
                <tbody>
                <?php


                foreach($coatingsLittleArray as $material){
                    foreach($coatingsFromDB as $m){
                        if($m['article'] == $material['article']) $msitem = $m;
                    }

                    echo '<tr>';
                    echo '<td><a href="'.$msitem['meta']['uuidHref'].'">'.$material['article'].'</a></td>';
                    echo '<td>'.$material['name'].'</td>';
                    echo '<td class="text-right">'.engine::format_price(round($material['area'], 3)).'</td>';
                    echo '<td class="text-right">'.engine::format_price(@$msitem['buyPrice']['value'] / 100).'</td>';
                    echo '<td class="text-right">'.(@$material['coeffs']['price'] ? '1 / '.engine::format_price(@$material['coeffs']['price']) : '-').'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['price'] / 100).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['sum']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffs']['zp']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['zp']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffs']['othodi']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['othodi']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffs']['komplektushie']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['komplektushie']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffs']['dostavka']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['dostavka']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffs']['nakladnie']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['nakladnie']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffs']['upakovka']).'</td>';
                    echo '<td class="text-right">'.engine::format_price($material['coeffSums']['upakovka']).'</td>';
                    echo '<td class="text-right">'.engine::format_price(array_sum($material['coeffSums'])).'</td>';
                    echo '</tr>';


                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
<div class="row">
    <div class="col-xl-4 col-lg-5 col-md-5 col-sm-6 col-12">

    <div class="card card-cyan">
        <div class="card-header">
            <h3>Коэффициенты = <?=engine::format_price(array_sum($coeffSums));?> р.</h3>
        </div>
        <div class="card-body">
            <table class="table fixtable">
                <thead>
                <th>Коэффициент</th>
                <th>Сумма</th>
                </thead>
                <tbody>
                <tr>
                    <td>ЗП</td>
                    <td class="text-right"><?=engine::format_price($coeffSums['zp']);?></td>
                </tr>
                <tr>
                    <td>Отходы</td>
                    <td class="text-right"><?=engine::format_price($coeffSums['othodi']);?></td>
                </tr>
                <tr>
                    <td>Комплектующие</td>
                    <td class="text-right"><?=engine::format_price($coeffSums['komplektushie']);?></td>
                </tr>
                <tr>
                    <td>Доставка</td>
                    <td class="text-right"><?=engine::format_price($coeffSums['dostavka']);?></td>
                </tr>
                <tr>
                    <td>Накладные</td>
                    <td class="text-right"><?=engine::format_price($coeffSums['nakladnie']);?></td>
                </tr>
                <tr>
                    <td>Упаковка</td>
                    <td class="text-right"><?=engine::format_price($coeffSums['upakovka']);?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    </div>
<div class="col-xl-4 col-lg-5 col-md-5 col-sm-6 col-12">
    <div class="card card-indigo">
        <div class="card-header">
            <h3>Себестоимость</h3>
        </div>
        <div class="card-body">
            <table class="table fixtable">
                <thead>
                <th>Составляющее</th>
                <th>Стоимость</th>
                </thead>
                <tbody>
                <tr>
                    <td>Материалы</td>
                    <td class="text-right"><?=engine::format_price($materialsSum);?></td>
                </tr>
                <tr>
                    <td>Покрытия</td>
                    <td class="text-right"><?=engine::format_price($coatingsSum);?></td>
                </tr>
                <tr>
                    <td>Коэффициенты</td>
                    <td class="text-right"><?=engine::format_price(array_sum($coeffSums));?></td>
                </tr>
                <tr>
                    <td><b>Себестоимость производства</b></td>
                    <td class="text-right"><b><?=engine::format_price($ssp);?></b></td>
                </tr>
                <tr>
                    <td><b>Закупочная <?=$profitCoeff?></b></td>
                    <td class="text-right"><b><?=engine::format_price($pp);?></b></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<script>
    $(document).ready(function () {
        let strPrice = ($("#buyPrice").data('price')).replace(',', '');
        strPrice = strPrice.replace(/ /g,'');
        $('#updateBuyPrice').on('click', function (e) {
            $('#updateBuyPrice').attr('disabled', true);
            $.ajax({
                dataType: "json",
                method: 'POST',
                url: '/ajax.php?component=factory&do=updateBuyPrice',
                data: {
                    price: parseInt(strPrice),
                    productId: '<?= $product['id']?>',
                    modificationId: '<?= $modification['id'] ?>'
                },
                success: function (response) {
                    if (response.status === true) {
                        window.location.reload();
                        alert('Цена успешно обновлена');
                    } else {
                        alert(response.message);
                        $('#updateBuyPrice').attr('disabled', false);
                    }
                }
            });
        });
    });

</script>

<?php
//engine::debug_printArray($materialsLittleArray);
//
//engine::debug_printArray($materialsLittleArray);
//engine::debug_printArray($coatingsLittleArray);
//engine::debug_printArray($materialsFromDB);
//
//engine::debug_printArray($specification);

