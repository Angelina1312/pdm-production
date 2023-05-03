<?php
if (!isset($_GET['first'])) throw new Exception('Не указан first id');
if (!isset($_GET['second'])) throw new Exception('Не указан second id');

engine::TEMPLATE()->setTagValue('pagename', 'Сравнение спецификаций');

module::getModuleClass('breadcrumbs')->addElement('#!', 'Производство');;
mod_breadcrumbs::$disableDefault = true;
module::getModuleClass('breadcrumbs')->addElement('#!', 'Сравнение спецификаций');;


$firstId = (int) $_GET['first'];
$secondId = (int) $_GET['second'];

$first = engine::FACTORY()->getSpecification($firstId);
$second = engine::FACTORY()->getSpecification($secondId);
if(!$first || !$second) exit('Ошибка получения одной из спецификаций');

$modFirst = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id=?i', $first['modificationId']);
if(!@$modFirst) exit('Ошибка получения модификации ' . $first['modificationId']);
$modSecond = ($first['modificationId'] == $second['modificationId']) ? $modFirst : engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id=?i', $second['modificationId']);
$firstName = $modFirst['name'].' R'.$first['revision'];
$secondName = $modSecond['name'].' R'.$second['revision'];

$allProductIds = [
        ...array_column($first['data']['materials'], 'materialId'),
        ...array_column($second['data']['materials'], 'materialId'),
        ...array_column($first['data']['coatings'], 'coatingProductId'),
        ...array_column($second['data']['coatings'], 'coatingProductId'),
        $modFirst['productId'],
        $modSecond['productId']
    ];
$allProductIds = array_unique($allProductIds);
#engine::debug_printArray($allProductIds);
$productsFromDB = engine::DB()->getAll('SELECT id, `data` FROM @[prefix]products WHERE id IN(?a)', $allProductIds);
$products = [];
foreach($productsFromDB as $product){
    $products[$product['id']] = json_decode($product['data'], true);
}

$materials = [];


//area
//weight
//legth
//quantity
foreach(@$first['data']['materials'] as $material){
    $materials[$material['materialId']]['first'] = $material;
	$materials[$material['materialId']]['product'] = $products[$material['materialId']];
}
foreach(@$second['data']['materials'] as $material){
	$materials[$material['materialId']]['second'] = $material;
	$materials[$material['materialId']]['product'] = $products[$material['materialId']];
}

$coatings = [];
foreach($first['data']['coatings'] as $coating){
	$coatings[$coating['site']][$coating['coatingProductId']]['first'] = $coating;
	$coatings[$coating['site']][$coating['coatingProductId']]['product'] = $products[$coating['coatingProductId']];
}
foreach($second['data']['coatings'] as $coating){
	$coatings[$coating['site']][$coating['coatingProductId']]['second'] = $coating;
	$coatings[$coating['site']][$coating['coatingProductId']]['product'] = $products[$coating['coatingProductId']];
}


$productFirst = $products[$modFirst['productId']];
$productSecond = $products[$modSecond['productId']];


?>
<style>
    .haveDiffs {
        background: rgba(255, 163, 163, 0.24) !important;
    }
</style>
<div class="card card-default table-responsive">
	<div class="card-header">
        <div class="card-title">
            <?
            if($productFirst['id'] == $productSecond['id']) echo '<h4>['.$productFirst['article'].'] '.$productFirst['name'].'</h4>';
            else echo '<h3>Сравниваются разные товары, "'.$productFirst['article'] . '" и "' . $productSecond['article'] . '"</h3>';
            ?>
        </div>
		<div class="card-tools">
            <a href="#" id="switchMode" class="btn btn-info">Отображать только различия</a>
		</div>
	</div>
	<div class="card-body">
        <h4>Материалы</h4>
        <table class="table materials table-bordered table-striped">
            <thead>
                <tr>
                    <th colspan="2"></th>

                    <th colspan="2" class="text-center">Площадь м2</th>
                    <th colspan="2" class="text-center">Масса кг</th>
                    <th colspan="2" class="text-center">Длина м</th>
                    <th colspan="2" class="text-center">Количество шт</th>
                </tr>
                <tr>
                    <th>Артикул (МС)</th>
                    <th>Наименование (PDM)</th>
                    <th class="text-right"><?=$firstName?></th>
                    <th class="text-right"><?=$secondName?></th>
                    <th class="text-right"><?=$firstName?></th>
                    <th class="text-right"><?=$secondName?></th>
                    <th class="text-right"><?=$firstName?></th>
                    <th class="text-right"><?=$secondName?></th>
                    <th class="text-right"><?=$firstName?></th>
                    <th class="text-right"><?=$secondName?></th>
                </tr>
            </thead>
            <tbody>
            <?php
                foreach($materials as $material){
	                $hasFirst = array_key_exists('first', $material);
	                $hasSecond = array_key_exists('second', $material);
                    $haveDiff = (
                            @$material['first']['area'] != @$material['second']['area'] ||
                            @$material['first']['weight'] != @$material['second']['weight'] ||
                            @$material['first']['length'] != @$material['second']['length'] ||
                            @$material['first']['quantity'] != @$material['second']['quantity'] ||
                            !$hasFirst || !$hasSecond
                    );




	                $product = $material['product'];
	                $calculateField = helper::uomToField($product['uom']['name']);

	                $no = 'отсутствует';

	                echo '<tr class="diffrow '.($haveDiff ? 'haveDiffs' : '').'">';
                    echo '<td><a href="'.$product['meta']['uuidHref'].'" target="_blank">'.$product['article'].'</a></td>';
                    echo '<td><a href="'.helper::getLink('product', $product['id']).'" target="_blank">'.$product['name'].'</a></td>';
                    echo '<td class="text-right '.($calculateField == 'area' ? 'calculateField' : '').'">       '.($hasFirst ? $material['first']['area']       : $no).'</td>';
                    echo '<td class="text-right '.($calculateField == 'area' ? 'calculateField' : '').'">       '.($hasSecond ? $material['second']['area']     : $no).'</td>';
	                echo '<td class="text-right '.($calculateField == 'weight' ? 'calculateField' : '').'">     '.($hasFirst ? $material['first']['weight']     : $no).'</td>';
	                echo '<td class="text-right '.($calculateField == 'weight' ? 'calculateField' : '').'">     '.($hasSecond ? $material['second']['weight']   : $no).'</td>';
	                echo '<td class="text-right '.($calculateField == 'length' ? 'calculateField' : '').'">     '.($hasFirst ? $material['first']['length']     : $no).'</td>';
	                echo '<td class="text-right '.($calculateField == 'length' ? 'calculateField' : '').'">     '.($hasSecond ? $material['second']['length']   : $no).'</td>';
	                echo '<td class="text-right '.($calculateField == 'quantity' ? 'calculateField' : '').'">   '.($hasFirst ? $material['first']['quantity']   : $no).'</td>';
	                echo '<td class="text-right '.($calculateField == 'quantity' ? 'calculateField' : '').'">   '.($hasSecond ? $material['second']['quantity'] : $no).'</td>';


                    echo '</tr>';

                }
            ?>
            </tbody>
        </table>
        <h4>Покрытия</h4>
        <table class="table materials table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="3"></th>
                <th colspan="2" class="text-center">Площадь м2</th>
            </tr>
            <tr>
                <th>Артикул (МС)</th>
                <th>Наименование (PDM)</th>
                <th class="text-center">Участок</th>
                <th class="text-right"><?=$firstName?></th>
                <th class="text-right"><?=$secondName?></th>
            </tr>
            </thead>
            <tbody>
			<?php
			foreach($coatings as $site => $coatingsArray){
			    foreach($coatingsArray as $coating){
                    $hasFirst = array_key_exists('first', $coating);
                    $hasSecond = array_key_exists('second', $coating);
                    $haveDiff = (
                        @$material['first']['area'] != @$material['second']['area'] ||
                        !$hasFirst || !$hasSecond
                    );




                    $product = $coating['product'];

                    $no = 'отсутствует';

                    echo '<tr class="diffrow '.($haveDiff ? 'haveDiffs' : '').'">';
                    echo '<td><a href="'.$product['meta']['uuidHref'].'" target="_blank">'.$product['article'].'</a></td>';
                    echo '<td><a href="'.helper::getLink('product', $product['id']).'" target="_blank">'.$product['name'].'</a></td>';
                    echo '<td class="text-center">'.$site.'</td>';
                    echo '<td class="text-right">   '.($hasFirst ? $coating['first']['area']       : $no).'</td>';
                    echo '<td class="text-right">   '.($hasSecond ? $coating['second']['area']     : $no).'</td>';



                    echo '</tr>';
			    }
			}
			?>
            </tbody>
        </table>
	</div>
</div>
<script>
    var mode = false;
    $('#switchMode').on('click', function (e){
        e.preventDefault();
        $('.diffrow').each(function (d, a, b){
            if($(a).hasClass('haveDiffs') === false){
                if(mode === false) $(a).hide();
                else $(a).show();
            }
        });
        if(mode === false) {
            mode = true;
            $('#switchMode').text('Отображать всё');
        } else {
            mode = false;
            $('#switchMode').text('Отображать только различия');
        }
    });
</script>
