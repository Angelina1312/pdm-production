<?php
if (!isset($_GET['id'])) throw new Exception('Не указан id');

$id = $_GET['id'];
$specification = engine::DB()->getRow('SELECT * FROM @[prefix]specification WHERE id=?i', $id);
if (!$specification) throw new Exception('Спецификация не найдена');
$modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id=?i', $specification['modificationId']);
//engine::debug_printArray($specification);
$product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE id=?s', $specification['productId']);


engine::TEMPLATE()->setTagValue('pagename', $product['article'] . '.' . $modification['name'] . 'R' . $specification['revision']);

module::getModuleClass('breadcrumbs')->addElement('/index.php?component=moysklad&page=product&productId=' . $product['id'], $product['article']);;
module::getModuleClass('breadcrumbs')->addElement('#!', 'Модификация ' . $modification['name']);;
module::getModuleClass('breadcrumbs')->addElement('/index.php?component=factory&page=specification&id=' . $specification['id'], 'Спецификация ' . 'R' . $specification['revision']);;

$modificationsByProduct = engine::DB()->getAll('SELECT id, `name` FROM @[prefix]modifications WHERE productId = ?s ORDER BY `name` DESC', $product['id']);
$specificationsByProduct = engine::DB()->getAll('SELECT id, revision, modificationId FROM @[prefix]specification WHERE modificationId IN(?a) ORDER BY id DESC', array_column($modificationsByProduct, 'id'));

$toDiff = [];
$toDiffMods = [];
foreach ($modificationsByProduct as $m) {
    $toDiffMods[$m['id']] = $m;
}
foreach ($specificationsByProduct as $s) {
    if ($s['id'] == $specification['id']) continue;
    $toDiff[$s['modificationId']][] = $s;
}

?>

    <div class="card card-default">
        <div class="card-header">
            <?
            if (count($toDiff) > 0) {
                echo '<div class="btn-group">
                    <button type="button" class="btn btn-info" data-toggle="dropdown" aria-expanded="false">Сравнение спецификаций</button>
                    <div class="dropdown-menu" role="menu">';
                foreach ($toDiff as $modId => $mod) {
                    echo '<div class="dropdown-item-text">МОД-' . $toDiffMods[$modId]['name'] . '</div>';
                    foreach ($mod as $spec) {
                        echo '<a class="dropdown-item text-right" href="/index.php?component=factory&page=diff&first=' . $specification['id'] . '&second=' . $spec['id'] . '">R' . $spec['revision'] . '</a>';
                    }
                }
                echo '</div></div>';
            }
            ?>

        </div>
        <div class="card-body">
            <div class="row ">
                <div class="col-md-4">
                    <div class="card card-default">
                        <div class="card-header">Информация</div>
                        <div class="card-body">
                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b>Продукт</b> <span class="float-right"><a
                                                href="/index.php?component=moysklad&page=product&productId=<?= $product['id'] ?>"><?= $product['name']; ?></a></span>
                                </li>
                                <li class="list-group-item">
                                    <b>Модификация</b> <span class="float-right"><?= $modification['name'] ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b>Запрещен к производству</b> <span
                                            class="float-right"><?= ($specification['canBeProduced'] ? '<span style="color: #39d739">Нет</span>' : '<span style="color: #fc5f5f">Да</span>'); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b>Дата загрузки</b> <span
                                            class="float-right"><?= engine::format_datetime($specification['created']) ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b>Кем загружен</b> <span
                                            class="float-right"><?= engine::format_user($specification['createdBy']); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-default">
                        <div class="card-header">Расчёты по спецификации</div>
                        <div class="card-body">
                            <?
                            $calcs = engine::FACTORY()->getCalculationsListBySpecification($specification['id']);
                            if ($calcs) {
                                ?>
                                <table class="table table-striped">
                                    <thead style="text-align: center;">
                                    <th>Дата</th>
                                    <th>СС производства</th>
                                    <th>Закупочная</th>
                                    </thead>
                                    <tbody>
                                    <?php

                                    foreach ($calcs as $calculation) {
                                        echo '<tr>';
                                        echo '<td><a href="' . helper::getLink('calculation', (int)$calculation['id']) . '">' . date('Y-m-d H:i:s', strtotime($calculation['calculationDate'])) . '</a></td>';
                                        echo '<td class="text-right"><a href="' . helper::getLink('calculation', (int)$calculation['id']) . '">' . engine::format_price($calculation['costPrice']) . '</a></td>';
                                        echo '<td class="text-right"><a href="' . helper::getLink('calculation', (int)$calculation['id']) . '">' . engine::format_price($calculation['purchasePrice']) . '</a></td>';
                                        echo '</tr>';
                                    }

                                    ?>
                                    </tbody>
                                </table>
                                <?
                            } else {
                                echo '<div class="alert alert-danger">Расчётов пока нет</div>';
                            }
                            $createSpecificationLink = helper::getLink('createCalculation', $specification['id']);
                            ?>

                        </div>
                        <div class="card-footer">
                            <button href="#" class="createCalculation btn btn-outline-info btn-block"
                                    data-specid="<?= $specification['id'] ?>">Создать расчёт
                            </button>
                            <!--a class="btn btn-outline-info btn-block" href="<?= $createSpecificationLink ?>">Создать расчёт</a-->
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-default">
                        <div class="card-header">МойСклад: Тех. карта</div>
                        <div class="card-body">
                            <?
                            if ($specification['ppid']) {
                                $pp = engine::DB()->getRow('SELECT * FROM @[prefix]pp WHERE id=?s', $specification['ppid']);
                                $pp['data'] = json_decode($pp['data'], true);
                                echo '<p>Текущая спецификация была создана из техкарты МС:</p>';
                                echo '<p><a target="_blank" href="' . $pp['data']['meta']['uuidHref'] . '">' . $pp['data']['name'] . '</a></p>';
                            } else {
                                $pp = engine::DB()->getRow('SELECT * FROM @[prefix]moysklad_processingPlans WHERE specificationId = ?i', $specification['id']);
                                if ($pp) {
                                    ?>

                                    <table class="table">
                                        <thead>
                                        <th>Когда</th>
                                        <th>Кто</th>
                                        </thead>
                                        <tbody>
                                        <td><a target="_blank"
                                               href="<?= $pp['uuidHref'] ?>"><?= engine::format_datetime($pp['creationDate']) ?></a>
                                        </td>
                                        <td><?= engine::format_user($pp['createdBy']) ?></td>
                                        </tbody>
                                    </table>

                                    <?
                                }
                            }


                            ?>
                        </div>
                        <div class="card-footer">
                            <? if (!$pp) { ?>
                                <button href="#" class="createPP btn btn-outline-info btn-block"
                                        data-specid="<?= $specification['id'] ?>">Создать тех. карту
                                </button>
                                <!--a class="btn btn-outline-info btn-block" href="<?= $createSpecificationLink ?>">Создать расчёт</a-->
                            <? } ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

<?php

$specificationMaterials = engine::DB()->getAll('SELECT * FROM @[prefix]specification_materials WHERE specificationId = ?i', $id);
$specificationCoatings = engine::DB()->getAll('SELECT * FROM @[prefix]specification_coatings WHERE specificationId =?i', $id);
?>

    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">Материалы</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip"
                        title="Collapse">
                    <i class="fas fa-minus"></i></button>
                <!--button type="button" class="btn btn-tool" data-card-widget="remove" data-toggle="tooltip" title="Remove">
                    <i class="fas fa-times"></i></button-->
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped fixtable">
                <thead>
                <th>Артикул материала</th>
                <th>Площадь, м2</th>
                <th>Масса</th>
                <th>Длина</th>
                <th>Количество</th>
                </thead>
                <tbody>
                <?php
                foreach ($specificationMaterials as $material) {
                    echo '<tr>';
                    echo '<td><a href="/index.php?component=moysklad&page=product&productId=' . $material['materialId'] . '">' . $material['materialArticle'] . '</a></td>';
                    echo '<td>' . $material['area'] . '</td>';
                    echo '<td>' . $material['weight'] . '</td>';
                    echo '<td>' . $material['length'] . '</td>';
                    echo '<td>' . $material['quantity'] . '</td>';
                    echo '</tr>';
                }

                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-purple">
        <div class="card-header">
            <h3 class="card-title">Покрытия</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip"
                        title="Collapse">
                    <i class="fas fa-minus"></i></button>
                <!--button type="button" class="btn btn-tool" data-card-widget="remove" data-toggle="tooltip" title="Remove">
                    <i class="fas fa-times"></i></button-->
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped fixtable">
                <thead>
                <th>Участок</th>
                <th>Краска</th>
                <th>Площадь, м2</th>
                </thead>
                <tbody>
                <?php
                $coats = [];
                foreach ($specificationCoatings as $coating) {
                    $coats[] = $coating['coatingProductId'];
                }
                $coatingProductsFromDB = engine::DB()->getAll('SELECT * FROM lgf_products WHERE id IN (?a)', $coats);
                $coatingProducts = [];
                foreach ($coatingProductsFromDB as $cp) {
                    $coatingProducts[$cp['id']] = $cp;
                }

                foreach ($specificationCoatings as $coating) {
                    echo '<tr>';
                    echo '<td>' . $coating['site'] . '</td>';
                    echo '<td><a href="/index.php?component=moysklad&page=product&productId=' . $coating['coatingProductId'] . '">' . $coatingProducts[$coating['coatingProductId']]['name'] . '</a></td>';
                    echo '<td>' . $coating['area'] . '</td>';
                    echo '</tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Комментарий</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip"
                        title="Collapse">
                    <i class="fas fa-minus"></i></button>
                <!--button type="button" class="btn btn-tool" data-card-widget="remove" data-toggle="tooltip" title="Remove">
                    <i class="fas fa-times"></i></button-->
            </div>
        </div>
        <div class="card-body" style="border: 1px #000 dotted; margin: 20px;">
            <?= $specification['comment'] ?>
        </div>
    </div>
    <script>
        $(document).ready(function (e) {

            $('.createCalculation').on('click', function (e) {
                e.preventDefault();
                $(this).attr('disabled', true);
                $.ajax({
                    dataType: "json",
                    method: 'POST',
                    url: '/ajax.php?component=factory&do=createCalculation',
                    data: {specId: $(this).data('specid')},
                    success: function (response) {
                        $(this).attr('disabled', false);
                        console.log(response);
                        if (response.status === true) {
                            window.location.reload();
                        } else {
                            alert(response.message);
                        }


                    },
                    error: function (response) {
                        console.log(response);
                        alert(response.message);
                        $(this).attr('disabled', false);

                    }
                });

            })

            $('.createPP').on('click', function (e) {
                e.preventDefault();
                //$(this).attr('disabled', true);
                $.ajax({
                    dataType: "json",
                    method: 'POST',
                    url: '/ajax.php?component=factory&do=createPP',
                    data: {specId: $(this).data('specid')},
                    success: function (response) {
                        $(this).attr('disabled', false);
                        if (response.status === true) {
                            window.location.reload();
                        } else {
                            alert(response.message);
                            console.log(response);
                        }


                    },
                    error: function (response) {
                        console.log(response);
                        //alert(response);
                        $(this).attr('disabled', false);

                    }
                });

            })

        })
    </script>
<?php


