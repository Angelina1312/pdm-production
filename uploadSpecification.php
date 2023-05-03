<?php

$mode = (!isset($_GET['mode'])) ? 'fileUploaded' : strtolower($_GET['mode']);


$checkSpec = false;
if(isset($_POST['checkSpec']) && $_POST['checkSpec'] == 'yes'){
    $checkSpec = true;
    $_FILES['specFile'] = $_FILES['checkSpecFile'];
    //engine::debug_printArray($_FILES);
}else{
    if(!@$_POST['modId'] && !@$_GET['modId']) throw new Exception('modId not set');


    if(isset($_GET['modId'])) $mid = $_GET['modId'];
    if(isset($_POST['modId'])) $mid = $_POST['modId'];
    $modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id = ?s', $mid);
    if(!$modification) throw new Exception('modification \''.$mid.'\' not found');

    $product = false;
    if (isset($_GET['productId'])) {
        $product = engine::DB()->getRow('SELECT * FROM lgf_products WHERE id=?s', $_GET['productId']);
        if (!$product) throw new Exception('Не найден товар с ID ' . $_GET['productId']);
        $product['data'] = json_decode($product['data'], true);
    }
}



switch ($mode) {
case 'fileUploaded':

    ?>
    <form method="POST">
        <div class="card">
                <?php
                if($checkSpec) engine::TEMPLATE()->setTagValue('pagename','Проверка спецификации');
                    else engine::TEMPLATE()->setTagValue('pagename','[1/2] Загрузка спецификации к модификации <b>['.$modification['name'].'] '.$product['article'].'</b>');
                ?>
            <div class="card-body">
                <?php



                try {
                    ob_start();
                    if (!isset($_FILES['specFile']) || empty($_FILES['specFile']['tmp_name'])) throw new Exception('Вы не выбрали файл для загрузки');
                    $file = $_FILES['specFile'];
                    $fileContent = file_get_contents($file['tmp_name']);
                    $data = engine::FACTORY()->parseSpec($fileContent);
                    $savedFileId = engine::saveFile('specFile', 'specification');
                    //engine::debug_printArray($data);
                    $criticalError = false;
                    //engine::debug_printArray($data['errors']['%common%']);
                    foreach($data['errors']['%common%'] as $err){
                        echo '<div class="alert alert-'.($err['type'] == 'error' ? 'error' : 'warning').'"><h3>Внимание!</h3><b>'.$err['message'].'</b></div>';

                    }

                    if(count($data['errors']) > 1){
                        echo '<a href="#" id="showOnlyErrors" class="btn btn-warning" style="margin-bottom: 15px;">Показать только ошибки</a>';
                    }
                    if (!$checkSpec && strtoupper($data['article']) != strtoupper($product['article'])) {
                        echo '<div class="alert alert-danger"><h3>Внимание!</h3><br>Вы пытаетесь загрузить спецификацию <b>' . $data['article'] . '</b> к артикулу <b>' . $product['article'] . '</b>. Спецификация будет загружена к артикулу <b>' . $data['article'] . '</b>.</div>';
                        $product = engine::DB('SELECT * FROM lgf_products WHERE UPPER(article) = ?s', strtoupper($data['article']));
                        if (!$product) throw new Exception('Артикул ' . strtoupper($data['article']) . ' из спецификации не найден в базе товаров.');
                    }
                    if(!$checkSpec && strtolower($data['mod']) != strtolower($modification['name'])){
                        echo '<div class="alert alert-danger">Вы пытаетесь загрузить спецификацию для модификации <b>'.$data['mod'].'</b> к модификации <b>'.$modification['name'].'</b>.<br> Спецификация будет загружена к модификации <b>'.$data['mod'].'</b>.</div>';
                    }
                    if(!$checkSpec) echo '<h3>Проверка спецификации <b>' . $data['article'] . '</b>, модификация <b>' . $data['mod'] . '</b>, ревизия <b>' . $data['revision'] . '</b></h3>';
                    #if(@$_GET['debug']===true){
                    #    engine::debug_printArray($data['hierarchy']);
                    #    exit;
                    #}
                    echo '<table class="table table-striped fixtable">';
                    echo '<thead style="font-size: 0.9rem; word-break: keep-all">';
                    echo '<th>Номер</th>';
                    echo '<th>Наименование</th>';
                    echo '<th>Артикул материала</th>';
                    echo '<th>Количество</th>';
                    echo '<th>Масса</th>';
                    echo '<th>Площадь</th>';
                    echo '<th>Покрытие</th>';
                    echo '<th>Слой</th>';
                    echo '<th>Участок</th>';
                    echo '<th>Раздел</th>';
                    echo '<th>Длина заготовки</th>';
                    echo '<th>Описание</th>';
                    echo '<th>Группа товара</th>';

                    echo '</thead>';
                    echo '<tbody>';
                    foreach ($data['hierarchy'] as $item) {
                        $errorType = false;
                        $errorText = '';
                        if(array_key_exists(@$item['number'], @$data['errors'])) foreach($data['errors'][$item['number']] as $error){
                            if($error['type'] == 'warning' && $errorType === false) $errorType = 'warning'; else
                                if($error['type'] == 'error') {
                                    $errorType = 'error';
                                    $criticalError = true;
                                }
                            $errorText .= '<br>'.$error['message'];
                        }
                        if($errorText !== '') $errorText = '<b>'.$errorText.'</b>';
                        echo '<tr class="'.(($errorType !== false) ? 'hasError '.($errorType == 'error' ? 'bg-red' : 'bg-yellow') : 'noError').'">';


                        $calculateField = (array_key_exists(@$item['materialArticle'], $data['materials'])) ? helper::uomToField($data['materials'][$item['materialArticle']]['uom']['name']) : false;

                        foreach ($item as $k => $i) {
                            switch ($k) {
                                case 'name':
                                    echo '<td>' . $i . $errorText . '</td>';
                                    break;
                                case 'materialArticle':
                                    //echo var_dump(trim($i));
                                    if (array_key_exists(trim($i), $data['materials'])) {
                                        echo '<td><a href="' . $data['materials'][$i]['meta']['uuidHref'] . '" target="_blank">' . $i . '</a></td>';
                                    } else echo '<td color="red">' . $i . '</td>';
                                    break;
                                case 'coating':
                                    if (array_key_exists(trim($i), $data['mspaints'])) {
                                        echo '<td><a href="' . $data['mspaints'][$i]['meta']['uuidHref'] . '" target="_blank">' . $i . '</a></td>';
                                    } else echo '<td color="red">' . $i . '</td>';
                                    break;
                                case 'layer':
                                    echo '<td style="text-align: right;">' . $i . '</td>';
                                    break;
                                case 'level':
                                case 'parent':
                                case 'child':
                                case 'article':
                                    break;
                                case 'weight':
                                case 'length':
                                case 'area':
                                case 'quantity':
                                    if($k == $calculateField) echo '<td class="calculateField" style="text-align: right;">' . $i . '</td>';
                                    else echo '<td style="text-align: right;">' . $i . '</td>';
                                break;
                                default:
                                    echo '<td>' . $i . '</td>';
                                    break;
                            }
                        }
                        echo '</tr>';
                    }
                    echo '</tbody>';
                    echo '</table>';
                    ?>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">

                            </div>
                            <div class="col-md-6" style="text-align: right;">

                                <? if($criticalError === false && !$checkSpec) echo '<a href="/index.php?component=factory&page=uploadSpecification&mode=checkS&specFileId='. $savedFileId . (isset($_GET['productId']) ? '&productId=' . $_GET['productId'] : '') . '&modId='.$modification['id'].'" class="btn btn-success">Продолжить</a>';
                                ?>

                            </div>
                        </div>
                    </div>
                    <?
                    $c = ob_get_clean();
                    echo $c;
                } catch (Exception $ex) {
                    echo '<div class="alert alert-danger"><div class="alert-heading"><h3>Ошибка парсинга спецификации</h3></div>' . $ex->getMessage() . '</div>';
                    //if (@$savedFileId) engine::removeFile(@$savedFileId);

                }



                ?>
            </div>
        </div>

        <script>

            var showOnlyErrorsStatus = false;
            $('#showOnlyErrors').on('click', function (e){
                if(showOnlyErrorsStatus === false){
                    $('.noError').hide();
                    $('#showOnlyErrors').text('Показать всё');
                    $('#showOnlyErrors').addClass('btn-success');
                    $('#showOnlyErrors').removeClass('btn-warning');

                    showOnlyErrorsStatus = true;
                }else{
                    $('.noError').show();
                    $('#showOnlyErrors').text('Показать только ошибки');
                    $('#showOnlyErrors').removeClass('btn-success');
                    $('#showOnlyErrors').addClass('btn-warning');

                    showOnlyErrorsStatus = false;

                }
            });

        </script>
        <?php

        break;
        case 'checks':

        engine::TEMPLATE()->setTagValue('pagename','[2/2] Загрузка спецификации к модификации <b>['.$modification['name'].'] '.$product['article'].'</b>');




        try{
        if (!isset($_GET['specFileId']) || $_GET['specFileId'] < 1) throw new Exception('Ошибка в specFileId');
        $file = engine::getFileContent($_GET['specFileId']);
        $data = engine::FACTORY()->parseSpec($file);


        echo '<div class="card card-primary" id="cardMaterials">';
        ?>
        <div class="card-header">
            <h3 class="card-title">Материалы <?= ($data['article'] . ' : ' . $data['mod']) ?></h3>

            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip"
                        title="Collapse">
                    <i class="fas fa-minus"></i></button>
                <!--button type="button" class="btn btn-tool" data-card-widget="remove" data-toggle="tooltip" title="Remove">
                    <i class="fas fa-times"></i></button-->
            </div>
        </div>
        <div class="card-body table-responsive p-0" style="height: 350px;">
            <?php
            echo '<table class="table table-striped  table-head-fixed text-nowrap">';
            echo '<thead>';
            echo '<th>#</th>';
            echo '<th>Артикул</th>';
            echo '<th>Наименование</th>';
            echo '<th>Площадь</th>';
            echo '<th>Масса</th>';
            echo '<th>Длина</th>';
            echo '<th>Количество</th>';
            echo '<th>Ед. изм</th>';
            echo '<th>Группа</th>';
            echo '<th>DIN/ISO</th>';
            echo '</thead>';
            echo '<tbody>';
            $x = 1;

            $groups = [];


            foreach ($data['sum'] as $i => $s) {
                @$groups[@$data['materials'][$i]['pathName']]['length'] = @$groups[@$data['materials'][$i]['pathName']]['length'] + $s['length'];
                @$groups[@$data['materials'][$i]['pathName']]['area'] = $groups[@$data['materials'][$i]['pathName']]['area'] + $s['area'];
                @$groups[@$data['materials'][$i]['pathName']]['weight'] = $groups[@$data['materials'][$i]['pathName']]['weight'] + $s['weight'];
                @$groups[@$data['materials'][$i]['pathName']]['quantity'] = $groups[@$data['materials'][$i]['pathName']]['quantity'] + $s['quantity'];
                echo '<tr>';
                echo '<td>' . $x . '</td>';
                echo '<td>' . $i . '</td>';
                echo '<td><a href="' . @$data['materials'][$i]['meta']['uuidHref'] . '" target="_blank">' . @$data['materials'][$i]['name'] . '</a></td>';
                echo '<td>' . $s['area'] . '</td>';
                echo '<td>' . $s['weight'] . '</td>';
                echo '<td>' . $s['length'] . '</td>';
                echo '<td>' . $s['quantity'] . '</td>';
                echo '<td>' . @$data['materials'][$i]['uom']['name'] . '</td>';
                echo '<td>' . @$data['materials'][$i]['pathName'] . '</td>';
                echo '<td>' . @moySklad::findAttribute($data['materials'][$i]['attributes'], 'стандарт') . '</td>';
                echo '</tr>';
                $x++;
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div></div>';
            //engine::debug_printArray($sum);


            echo '<div class="card card-info">';
            ?>
            <div class="card-header">
                <h3 class="card-title">Покрытия</h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip"
                            title="Свернуть">
                        <i class="fas fa-minus"></i></button>
                    <!--button type="button" class="btn btn-tool" data-card-widget="remove" data-toggle="tooltip" title="Remove">
                        <i class="fas fa-times"></i></button-->
                </div>
            </div>
            <div class="card-body">
                <?
                echo '<table class="table table-striped">';
                echo '<thead>';
                echo '<th>#</th>';
                echo '<th>Покрытие</th>';
                echo '<th>поверхность, м2</th>';
                echo '<th>тип</th>';
                echo '<th>ед изм</th>';
                echo '<th>коэффициент</th>';
                echo '<th>расход</th>';
                echo '</thead>';
                echo '<tbody>';
                $kgP = 0;
                $x = 1;
                $primings = engine::CONFIG()['moysklad']['priming'];
                $additionalCoatingsIds = [];
                foreach ($data['paint'] as $p => $d) {
                    echo '<tr><td colspan="7"><b>' . $p . '</b></td></tr>';
                    $thisPArea = 0;
                    foreach ($d as $xz => $paint) {
                        echo '<tr>';

                        echo '<td>' . $x . '</td>';
                        echo '<td>' . $xz . '</td>';
                        echo '<td>' . $paint['area'] . '</td>';
                        echo '<td><a href="' . $data['mspaints'][$xz]['meta']['uuidHref'] . '" target="_blank">' . $data['mspaints'][$xz]['name'] . '</a></td>';
                        echo '<td>' . $data['mspaints'][$xz]['uom']['name'] . '</td>';

                        $coeff = moySklad::findAttribute($data['mspaints'][$xz]['attributes'], 'расход (гр или мл)');
                        if ($coeff === false) throw new Exception('У ' . $data['mspaints'][$xz]['name'] . '(' . $data['mspaints'][$xz]['article'] . ') не найден коэфицент');
                        $coeff = $coeff / 1000;

                        echo '<td>' . $coeff . '</td>';

                        echo '<td>' . ($paint['area'] * $coeff) . '</td>';
                        $thisPArea += $paint['area'];

                        echo '</tr>';
                        $x++;
                    }

                    foreach ($primings as $pr) {
                        if (!in_array(mb_strtolower($p), $pr['sites'])) continue;

                        $prims = engine::DB()->getAll('SELECT * FROM lgf_products WHERE id IN (?a)', $pr['primings']);

                        $primingsFromDB = [];
                        foreach ($prims as $pri) {
                            $primingsFromDB[$pri['article']] = json_decode($pri['data'], true);
                        }
                        $acid = md5('additionalPriming' . $p . rand(0, 9999));
                        $additionalCoatingsIds[] = $acid;
                        echo '<tr>';
                        echo '<td><div class="icheck-info d-inline"><input type="checkbox" id="ch' . $acid . '" class="addPriming " ' . (($pr['defaultStatus']) ? ' checked="checked"' : '') . '><label for="ch' . $acid . '"</div></td>';
                        echo '<td><select class="choosePriming">';
                        foreach ($primingsFromDB as $k => $primingFromDB) {
                            $coeff = moySklad::findAttribute($primingFromDB['attributes'], 'расход (гр или мл)');
                            $coeff = $coeff / 1000;
                            echo '<option data-id="' . $primingFromDB['id'] . '" data-coeff="' . $coeff . '" data-uom="' . $primingFromDB['uom']['name'] . '" data-rashod="' . $coeff * $thisPArea . '" data-site="' . $p . '">' . $primingFromDB['name'] . '</option>';
                        }
                        echo '</select>';
                        if(@$pr['description']) echo '<span class="" style="display: block;">'.$pr['description'].'</span>';
                        echo '</td>';
                        echo '<td class="thisArea">' . $thisPArea . '</td>';
                        echo '<td></td>';
                        echo '<td class="uom"></td>';
                        echo '<td class="coeff"></td>';
                        echo '<td class="rashod"></td>';
                        echo '</tr>';
                    }
                }
                echo '</tbody>';
                echo '</table>';
                echo '</div></div>';

                $dataForJS = [
                    'article' => $data['article'],
                    'mod' => $data['mod'],
                    'fileId' => $_GET['specFileId'],
                    'cids' => $additionalCoatingsIds
                ];



                ?>

                <div class="card">
                    <div class="card-header">
                        Технические характеристики по группам товаров
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <th>Участок</th>
                                <th>Площадь (м2)</th>
                                <th>Масса (кг)</th>
                                <th>Длина (м)</th>
                                <th>Количество (шт)</th>
                            </thead>
                            <tbody>
                            <?
                            $groupSums = [
                                    'area' => 0,
                                    'weight' => 0,
                                    'length' => 0,
                                    'quantity' => 0,
                            ];
                            foreach($groups as $gname => $g){
                                echo '<tr>';
                                echo '<td>'.$gname.'</td>';
                                echo '<td>'.$g['area'].'</td>';
                                echo '<td>'.$g['weight'].'</td>';
                                echo '<td>'.$g['length'].'</td>';
                                echo '<td>'.$g['quantity'].'</td>';
                                echo '</tr>';
                                $groupSums['area'] += $g['area'];
                                $groupSums['weight'] += $g['weight'];
                                $groupSums['length'] += $g['length'];
                                $groupSums['quantity'] += $g['quantity'];
                            }
                            echo '<tr>';
                            echo '<td><b>Сумма</b></td>';
                            echo '<td><b>'.$groupSums['area'].'</b></td>';
                            echo '<td><b>'.$groupSums['weight'].'</b></td>';
                            echo '<td><b>'.$groupSums['length'].'</b></td>';
                            echo '<td><b>'.$groupSums['quantity'].'</b></td>';
                            echo '</tr>';
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card card-success">
                    <div class="card-header"><h3 class="card-title">Дополнительные поля</h3></div>
                    <div class="card-body row">
                        <div class="col-md-12">
                            <label for="comment">Список изменений</label>
                            <textarea autocomplete="off" class="textarea" id="comment"
                                      placeholder="Введите комментарий к модификации"
                                      style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;"></textarea>
                        </div>

                    </div>

                    <div class="card-footer ">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="icheck-danger d-inline">
                                    <input type="checkbox" id="zkvps">
                                    <label for="zkvps">Модификация запрещена к выпуску</label>
                                </div>
                            </div>
                            <div class="col-md-6" style="text-align: right">
                                <a href="#" id="createModification" class="btn btn-success">Создать модификацию</a>
                            </div>
                        </div>
                    </div>
                </div>
    </form>
    <script src="/engine/template/plugins/summernote/summernote-bs4.min.js"></script>
    <script src="/engine/template/plugins/summernote/lang/summernote-ru-RU.js"></script>

    <script>
        var data = jQuery.parseJSON('<?=json_encode($dataForJS)?>');

        $(document).ready(function () {
            $("input[data-bootstrap-switch]").each(function () {
                $(this).bootstrapSwitch('state', $(this).prop('checked'));
            });
            $('.choosePriming').on('change', function (e) {
                $.each($(".choosePriming option:selected"), function (e, k, x) {
                    $(k).parent('select').parent('td').parent('tr').children('td.uom').text($(k).data('uom'));
                    $(k).parent('select').parent('td').parent('tr').children('td.coeff').text($(k).data('coeff'));
                    $(k).parent('select').parent('td').parent('tr').children('td.rashod').text($(k).data('rashod'));
                })
            });
            $('.choosePriming').trigger('change');
            $('#comment').summernote({lang: 'ru-RU'})
            $('#createModification').on('click', function (e) {
                e.preventDefault();

                let output = {
                    article: data.article,
                    mod: data.mod,
                    canBeProduced: !$('#zkvps')[0].checked,
                    comment: $('#comment').summernote('code'),
                    additionalCoatings: [],
                    fileId: data.fileId
                };

                $.each($(".choosePriming option:selected"), function (e, k, x) {
                    let checkBox = $(k).parent('select').parent('td').parent('tr').find('.addPriming');
                    if ($(checkBox)[0].checked === true) {
                        output.additionalCoatings[output.additionalCoatings.length] = {
                            'site': $(k).data('site'),
                            'id': $(k).data('id'),
                        }
                    }
                });

                $.ajax({
                    type: 'POST',
                    url: '/ajax.php?component=factory&do=createSpecification&ajax=true',
                    data: output,
                    dataType: "json",
                    success: function (data) {
                        if (data.status === true) {
                            window.location = "/?component=factory&page=specification&id=" + data.id;
                        } else {
                            alert('При добавлении модификации произошла ошибка. Обратитесь к администратору.\n' + data.error)
                        }

                    },
                    error: function (e) {
                        alert('Произошла ошибка при отправке формы, попробуйте еще раз.\n' + e);
                    }
                });


            });
            //$( ".choosePriming option:selected" );
        });
    </script>
    <?

} catch (Exception $ex) {
    echo '<div class="alert alert-danger"><div class="alert-heading">Ошибка парсинга спецификации</div>' . $ex->getMessage() . '</div>';

}
    ?>

    <?
    break;
    case 'createmodification':
        try {
            if (!$product) throw new Exception('');
            if (!isset($_GET['specFileId']) || $_GET['specFileId'] < 1) throw new Exception('Ошибка в specFileId');
            $file = engine::getFileContent($_GET['specFileId']);
            $data = engine::FACTORY()->parseSpec($file['data']);
            if (strtoupper($data['article']) != strtoupper($product['article'])) {
                //echo '<div class="alert alert-danger"><h3>Внимание!</h3><br>Вы пытаетесь загрузить спецификацию'.$data['article'].' к артикулу '.$product['article'].'. Спецификация будет загружена к ариткулу <b>'.$data['article'].'</b>.</div>';
                $product = engine::DB('SELECT * FROM lgf_products WHERE UPPER(article) = ?s', strtoupper($data['article']));
                if (!$product) throw new Exception('Артикул ' . strtoupper($data['article']) . ' из спецификации не найден в базе товаров.');
            }
        } catch (Exception $ex) {
            echo '<div class="alert alert-danger"><div class="alert-heading"><h3>Ошибка сохранения спецификации</h3></div>' . $ex->getMessage() . '</div>';
        }
        //engine::debug_printArray($data);
        break;
    default:
        throw new Exception('Mode ' . $mode . ' not found');
        break;
}