<?php
if (!@$_GET['specificationId']) throw new Exception('Не указан id спецификации');
$d = engine::FACTORY()->coeffs->calculatev1($_GET['specificationId']);

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

//echo '$materialsSum = ' . $materialsSum . '<br>';
//echo '$coatingsSum = ' . $coatingsSum . '<br>';
//echo '$allCoeffSum = ' . $allCoeffSum . '<br>';
//echo '$ssp (чистая СС) = ' . $ssp . '<br>';
//echo '$pp (СС производства) ('.$profitCoeff.') = ' . $pp . '<br>';
//
//echo '$coeffSums = <pre>' . print_r($coeffSums, true) . '</pre><br>';
//echo '$coeffFinalSums = <pre>' . print_r($coeffFinalSums, true) . '</pre><br>';

?>

<div class="card card-success">
    <div class="card-header">
        <h3>Материалы = <?=engine::format_price($materialsSum);?> р.</h3>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <th>Артикул</th>

                <th>Наименование</th>
                <th>Количество</th>
                <th>ед. изм.</th>
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


                foreach($materialsLittleArray as $material){

                    echo '<tr>';
                    echo '<td>'.$material['article'].'</td>';
                    echo '<td>'.$material['name'].'</td>';
                    echo '<td>'.round($material[$material['calculateField']], 3).'</td>';
                    echo '<td>'.$material['uom'].'</td>';
                    echo '<td>'.engine::format_price($material['price']).'</td>';
                    echo '<td>'.engine::format_price($material['sum']).'</td>';

                    echo '<td>'.$material['coeffs']['zp'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['zp']).'</td>';

                    echo '<td>'.$material['coeffs']['othodi'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['othodi']).'</td>';

                    echo '<td>'.$material['coeffs']['komplektushie'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['komplektushie']).'</td>';

                    echo '<td>'.$material['coeffs']['dostavka'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['dostavka']).'</td>';

                    echo '<td>'.$material['coeffs']['nakladnie'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['nakladnie']).'</td>';

                    echo '<td>'.$material['coeffs']['upakovka'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['upakovka']).'</td>';

                    echo '<td>'.engine::format_price(array_sum($material['coeffSums'])).'</td>';
                    echo '</tr>';

                }
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
            <table class="table">
                <thead>
                <th>Артикул</th>

                <th>Наименование</th>
                <th>Площадь</th>
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

                    echo '<tr>';
                    echo '<td>'.$material['article'].'</td>';
                    echo '<td>'.$material['name'].'</td>';
                    echo '<td>'.round($material['area'], 3).'</td>';
                    echo '<td>'.engine::format_price($material['price'] / 100).'</td>';
                    echo '<td>'.engine::format_price($material['sum']).'</td>';

                    echo '<td>'.$material['coeffs']['zp'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['zp']).'</td>';

                    echo '<td>'.$material['coeffs']['othodi'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['othodi']).'</td>';

                    echo '<td>'.$material['coeffs']['komplektushie'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['komplektushie']).'</td>';

                    echo '<td>'.$material['coeffs']['dostavka'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['dostavka']).'</td>';

                    echo '<td>'.$material['coeffs']['nakladnie'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['nakladnie']).'</td>';

                    echo '<td>'.$material['coeffs']['upakovka'].'</td>';
                    echo '<td>'.engine::format_price($material['coeffSums']['upakovka']).'</td>';



                    echo '<td>'.engine::format_price(array_sum($material['coeffSums'])).'</td>';
                    echo '</tr>';


                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-cyan">
        <div class="card-header">
            <h3>Коэффициенты = <?=engine::format_price(array_sum($coeffSums));?> р.</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <th>Коэффициент</th>
                    <th>Сумма</th>
                </thead>
                <tbody>
                    <tr>
                        <td>ЗП</td>
                        <td><?=engine::format_price($coeffSums['zp']);?></td>
                    </tr>
                    <tr>
                        <td>Отходы</td>
                        <td><?=engine::format_price($coeffSums['othodi']);?></td>
                    </tr>
                    <tr>
                        <td>Комплектующие</td>
                        <td><?=engine::format_price($coeffSums['komplektushie']);?></td>
                    </tr>
                    <tr>
                        <td>Доставка</td>
                        <td><?=engine::format_price($coeffSums['dostavka']);?></td>
                    </tr>
                    <tr>
                        <td>Накладные</td>
                        <td><?=engine::format_price($coeffSums['nakladnie']);?></td>
                    </tr>
                    <tr>
                        <td>Упаковка</td>
                        <td><?=engine::format_price($coeffSums['upakovka']);?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

<div class="card card-fuchsia">
    <div class="card-header">
        <h3>Себестоимость</h3>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
            <th>Составляющее</th>
            <th>Стоимость</th>
            </thead>
            <tbody>
                <tr>
                    <td>Материалы</td>
                    <td><?=engine::format_price($materialsSum);?></td>
                </tr>
                <tr>
                    <td>Покрытия</td>
                    <td><?=engine::format_price($coatingsSum);?></td>
                </tr>
                <tr>
                    <td>Коэффициенты</td>
                    <td><?=engine::format_price(array_sum($coeffSums));?></td>
                </tr>
                <tr>
                    <td><b>Себестоимость производства</b></td>
                    <td><b><?=engine::format_price($ssp);?></b></td>
                </tr>
                <tr>
                    <td><b>Закупочная <?=$profitCoeff?></b></td>
                    <td><b><?=engine::format_price($pp);?></b></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
engine::debug_printArray($materialsLittleArray);

engine::debug_printArray($materialsLittleArray);
engine::debug_printArray($coatingsLittleArray);
engine::debug_printArray($materialsFromDB);

//engine::debug_printArray($specification);

