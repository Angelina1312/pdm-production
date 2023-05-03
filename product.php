<?php


if (!isset($_GET['productId']) || $_GET['productId'] == '') throw new Exception('No product id');


$product = engine::moySklad()->getProduct($_GET['productId']);

module::getModuleClass('breadcrumbs')->addElement('/index.php?component=moysklad&page=product&productId=' . $product['id'], $product['name']);;
engine::TEMPLATE()->setTagValue('pagename', $product['name']);

$processingPlans = engine::FACTORY()->getProcessingPlansByProductId($product['id']);
if ($processingPlans) foreach ($processingPlans as $k => $p) {
    $processingPlans[$k]['data'] = json_decode($p['data'], true);
}

$processingOrders = engine::FACTORY()->getProcessingOrdersByProductId($product['id']);

// вывести id supplier

$counterparty = engine::moySklad()->getSupplier($product['id']);


$path = explode('/', $product['pathName']);
$path = implode('<br><i class="fas fa-arrow-right"></i> ', $path);

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

// закупочная цена в мс
//engine::debug_printArray($product['buyPrice']['value']);

// список возрастных групп
$ageList = engine::moySklad()->query('https://online.moysklad.ru/api/remap/1.2/entity/customentity/349819b5-f8de-11ea-0a80-0185000a5fdd');

?>
<div class="modal fade" id="modal-uploadSpec">
    <div class="modal-dialog">
        <form method="POST"
              action="/index.php?component=factory&page=uploadSpecification&productId=<?= $product['id'] ?>"
              enctype="multipart/form-data">
            <div class="modal-content bg-primary">
                <div class="modal-header">
                    <h4 class="modal-title">Загрузка спецификации</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть"><span
                                aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <label class="" for="specFile">Спецификация (.txt) <span class="required">*</span></label>
                    <input type="hidden" class="inputWithModId" name="modId" value="null">
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" data-browse="Выбрать" name="specFile" accept=".txt"
                               id="specFile">
                        <label class="custom-file-label" for="specFile">выберите файл</label>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Закрыть</button>
                    <input type="submit" class="btn btn-success" name="submit" value="Добавить">
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal-uploadPackage">
    <div class="modal-dialog">
        <form method="POST"
              action="/index.php?component=warehouse&page=uploadPackage&productId=<?= $product['id'] ?>"
              enctype="multipart/form-data">
            <div class="modal-content bg-primary">
                <div class="modal-header">
                    <h4 class="modal-title">Загрузка упаковки</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть"><span
                                aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <label class="" for="specFile">Упаковка (.txt, .xls) <span class="required">*</span></label>
                    <input type="hidden" class="inputWithModId" name="modId" value="null">
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" data-browse="Выбрать" name="specFile"
                               accept=".txt,.xls"
                               id="specFile">
                        <label class="custom-file-label" for="specFile">выберите файл</label>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Закрыть</button>
                    <input type="submit" class="btn btn-success" name="submit" value="Добавить">
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal-uploadFile">
    <div class="modal-dialog">
        <form method="POST"
              action="/ajax.php?component=factory&do=uploadFile"
              enctype="multipart/form-data">
            <div class="modal-content bg-primary">
                <div class="modal-header">
                    <h4 class="modal-title" id="fileUploadModalTitle">***</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть"><span
                                aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" class="inputWithModId" name="modId" value="1">
                    <input type="hidden" class="inputWithFileType" name="fileType" value="1">
                    <input type="hidden" class="inputWithBackUrl" name="backUrl" value="1">
                    <label class="" for="customFileUploadInput" id="filetype"></label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" data-browse="Выбрать" id="customFileUploadInput"
                               name="file">
                        <label class="custom-file-label" for="customFileUploadInput">выберите файл</label>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Закрыть</button>
                    <input type="submit" class="btn btn-success" name="submit" value="Добавить">
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal-newMod">
    <div class="modal-dialog">
        <form method="POST"
              action=""
              enctype="multipart/form-data" id="formCreateMod">
            <div class="modal-content bg-primary">
                <div class="modal-header">
                    <h4 class="modal-title">Создание модификации</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть"><span
                                aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="" for="modname">Наименование<span class="required">*</span></label>
                        <input type="text" class="input" placeholder="0000-00" id="inputNewModName"
                               value="<?= date('Y-m'); ?>"
                               id="newModName" name="modname" required>
                    </div>
                    <div class="form-group">
                        <label class="" for="modname">Список изменений<span class="required">*</span></label>
                        <textarea class="input" id="inputNewComment" style="width: 250px; height: 70px;"
                                  required></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Закрыть</button>
                    <input type="submit" class="btn btn-success" name="submit" id="createModButton" value="Добавить">
                </div>
            </div>
        </form>
    </div>
</div>
<div class="row">
    <div class="col-md-3">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <img class="profile-user-img img-fluid"
                         src="https://office.lebergroup.ru:9994/tg/<?= @$product['article'] ?>.jpg" alt=""
                         this.onerror=null;this.width=100;this.src='https://tech.lebergroup.ru/moysklad/pages/schedule/notfound.png';>
                </div>

                <h3 class="profile-username text-center"><?= @$product['article'] ?></h3>

                <p class="text-muted text-center"><?= $product['name'] ?> <a target="_blank"
                                                                             href="<?= $product['meta']['uuidHref'] ?>">ms</a>
                </p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Группа</b> <a class="float-right"><?= $path ?></a>
                    </li>
                    <li class="list-group-item">
                        <b>Закупочная цена</b> <a
                                class="float-right"><?= number_format($product['buyPrice']['value'] / 100, 2, '.', ' ') ?></a>
                    </li>
                    <li class="list-group-item">
                        <?php if ($counterparty) {
                            echo '<b>Поставщик</b> <a class="float-right">' . $counterparty['name'] . '</a>';
                        }
                        ?>

                    </li>
                </ul>

            </div>
        </div>
        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h3 class="card-title">Техкарты в МС</h3>
            </div>
            <div class="card-body box-profile">
                <?

                $pps = engine::moySklad()->getPPByProductId($product['id'], true);
                if ($pps === false) echo '<div class="text-center"><p>Непривязанные техкарты в МС отсутствуют</p></div>';
                else {
                    foreach ($pps as $pp) {
                        echo '<p><a href="#" class="createModFromPP" data-id="' . $pp['id'] . '">' . $pp['name'] . '</a></p>';
                    }
                }

                ?>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card">
            <div class="card-body" style="text-align: right;">
                <a href="#" id="actNewMod" class="btn btn-success">
                    <i class="fas fa-plus "></i> Создать модификацию
                </a>
            </div>
        </div>
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Модификации</h3>
            </div>
            <style>
                .docBlock {
                    width: 120px;
                    height: 160px;
                    display: inline-block;
                    text-align: center;
                    border: 1px dotted #a1a1a1;
                    background: #fff;
                    border-radius: 7px;
                    margin-left: 5px;
                    line-height: 16px;
                }

                .docBlock span {
                    margin-top: 5px;
                    width: 100%;
                    font-size: 12px;
                    display: block;
                }

                .docBlock a {
                    display: block;
                    font-size: 12px;
                }

                .docBlock i {
                    text-align: center;
                    font-size: 40px;
                    margin-top: 10px;
                }
            </style>
            <?php
            //$modifications = engine::DB()->getAll('SELECT * FROM lgf_modifications WHERE productid = ?s ORDER BY id DESC', $product['id']);
            $modifications = engine::FACTORY()->getModificationsByProductId($product['id']);
            ?>
            <div class="card-body">
                <div class="tab-pane" id="activity">
                    <div class="timeline timeline-inverse">
                        <?php
                        if ($modifications)
                            foreach ($modifications as $mod) {
                                $fileType = engine::DB()->query('SELECT * FROM @[prefix]files');


                                //$specifications = engine::DB()->getAll('SELECT * FROM lgf_specification WHERE modificationId = ?i', $mod['id']);
                                //engine::debug_printArray($specifications);


                                $delBtn = '';
                                if ($mod['isNull'] === true) $delBtn = '<a href="#" class="delEmptyModification" data-modid="' . $mod['id'] . '" style="color: #ff0000;" title="удалить пустую модификацию"><i class="fas fa-times-circle"></i></a>';
                                echo '<div class="time-label"><span class="bg-success"><a href="#">МОД <b>' . $mod['name'] . '</b></a></span>' . $delBtn . '<a href="#" id="commentEdit" class="disabled"><i class="fa-solid fa-gear time-label" style="float: right; margin-top: 10px;"></i></a></div>';


                                $checked = $mod['draft'];

                                ?>
                                <div>
                                    <i class="fas fa-code-branch bg-gray"></i>
                                    <div class="timeline-item">
                                        <form action="" type="POST" class="float-right"
                                              style="margin: 5px 10px 0 10px;">
                                            <label>Черновик</label>
                                            <input type="checkbox" name="draft" class="draft"
                                                   value="<?= $mod['draft'] ?>"
                                                   data-modid="<?= $mod['id'] ?>"
                                                   autocomplete="off" <?=($checked ? 'checked="checked"' : '')?>>
                                        </form>
                                        <span class="time">Создал <?= engine::format_user($mod['createdBy']); ?> <i
                                                    class="far fa-clock"></i> обновлено <?= date('Y.m.d H:i:s', strtotime($mod['lastupdate'])) ?></span>
                                        <h3 class="timeline-header">Документы модификации</h3>
                                        <form action="" method="POST" data-modid="<?= $mod['id']?>" class="form-row sendModParamsForm" style="margin-top: 15px;">
                                            <div class="form-group mx-sm-3 mb-2">
                                                <label>Ширина</label>
                                                <input type="text" class="form-control setWidth" style="width: 130px; height: 30px;" data-modid="<?= $mod['id'] ?>" value="<?= $mod['width'] ?>">
                                            </div>
                                            <div class="form-group mx-sm-3 mb-2">
                                                <label>Длина</label>
                                                <input type="text" class="form-control setLength" style="width: 130px; height: 30px;" data-modid="<?= $mod['id'] ?>" value="<?= $mod['length'] ?>">
                                            </div>
                                            <div class="form-group mx-sm-3 mb-2">
                                                <label>Высота</label>
                                                <input type="text" class="form-control setHeight" style="width: 130px; height: 30px;" data-modid="<?= $mod['id'] ?>" value="<?= $mod['height'] ?>">
                                            </div>
                                            <div class="form-group mx-sm-3 mb-2">
                                                <label>Макс. высота падения</label>
                                                <input type="text" class="form-control dropHeight" style="width: 162px; height: 30px;" data-modid="<?= $mod['id'] ?>" value="<?= $mod['drop_height'] ?>">
                                            </div>
                                            <div class="form-group mx-sm-3 mb-2">
                                                <label>Возрастная группа</label>
<!--                                                <input type="text" class="form-control age" style="width: 140px; height: 30px;" data-modid="<?/*= $mod['id'] */?>" value="<?/*= $mod['age']*/?>">-->
                                                <select class="form-control age" style="width: 140px; height: 35px;" data-modid="<?= $mod['id'] ?>" >
                                                    <!-- тут будет foreach из мс и будут выводиться все элементы списка -->
                                                    <? foreach ($ageList['rows'] as $nameAge) {
                                                        ?>
                                                        <option value="<?= $nameAge['name'] ?>" <?= (($mod['age'] == $nameAge['name']) ? ' selected' : '')?>><?= $nameAge['name'] ?></option>
                                                    <?   } ?>
                                                </select>
                                            </div>
                                            <div class="form-group mx-sm-3" style="margin-top: 25px;">
                                            <input type="submit" name="submit" class="btn btn-success sendModParamsButton <?=
                                            (in_array($currentUser, $usersId)) ? '' : 'disabled';
                                                ?>" value="Установить" data-modid="<?= $mod['id'] ?>">
                                            </div>
                                        </form>
                                        <div class="modal fade" id="modal-editComment">
                                            <div class="modal-dialog">
                                                <form method="POST"
                                                      action=""
                                                      enctype="multipart/form-data" id="formEditComment">
                                                    <div class="modal-content bg-primary">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title">Редактировать комментарий</h4>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Закрыть"><span
                                                                        aria-hidden="true">&times;</span></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <textarea data-modname="<?= $mod['name'] ?>"
                                                                          class="input" id="editComment"
                                                                          required><?= $mod['comment'] ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer justify-content-between">
                                                            <button type="button" class="btn btn-danger"
                                                                    data-dismiss="modal">Закрыть
                                                            </button>
                                                            <input type="submit" class="btn btn-success" name="submit"
                                                                   id="editCommentButton"
                                                                   value="Редактировать">
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="timeline-body row">
                                        <span class="docBlock">
                                              <i class="fas fa-clipboard-list"></i>
                                              <span>Спецификация</span>
                                              <?php
                                              if (@$mod['specifications']) {

                                                  echo '<a class="btn-xs btn-info" href="/index.php?component=factory&page=specification&id=' . $mod['specifications'][0]['id'] . '">Ревизия R' . $mod['specifications'][0]['revision'] . '</a>';
                                                  if (@!$mod['specifications'][0]['ppid'])
                                                      echo '<a href="/downloadFile.php?fileId=' . $mod['specifications'][0]['fileId'] . '&revision=R' . $mod['specifications'][0]['revision'] . '&type=specification&created=' . $mod['created'] . '&article=' . $product['article'] . '">Скачать</a>';

                                                  if (count($mod['specifications']) != 1) {
                                                      echo '<div class="dropdown show">
  <a class=" dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Открыть</a>
  <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">';
                                                      foreach ($mod['specifications'] as $specification) {
                                                          echo '<a class="dropdown-item" href="/index.php?component=factory&page=specification&id=' . $specification['id'] . '">Ревизия <b>R' . $specification['revision'] . '</b></a>';
                                                      }
                                                      echo '</div></div>';
                                                  }
                                              } else {
                                                  echo '<span>нет спецификации</span>';
                                              }
                                              echo '<hr style="margin: 1px;"><a href="#" class="addSpecification" data-modid="' . $mod['id'] . '">Загрузить</a>';
                                              ?>
                                          </span>
                                            <span class="docBlock">
                                              <i class="fas fa-calculator"></i>
                                              <span>Расчёт</span>
                                              <?php
                                              if (@$mod['specifications'][0]['calculations'][0]) {
                                                  $calc = $mod['specifications'][0]['calculations'][0];

                                                  echo '<a class="btn-xs btn-info" href="/index.php?component=factory&page=calculation&id=' . $calc['id'] . '&revision=R' . $mod['specifications'][0]['revision'] . '&created=' . $mod['created'] . '&type=calculation&article=' . $product['article'] . '">' . engine::format_datetime($calc['calculationDate']) . '</a>';

                                                  echo '<span>Закупочная цена <br><b>' . engine::format_price($calc['purchasePrice']) . '</b>₽</span>';

                                              } else {
                                                  if (@$mod['specifications'][0]) echo '<span>нет спецификации</span>'; else echo '<span>еще не создан</span>';
                                              }
                                              ?>
                                          </span>
                                            <span class="docBlock">
                                              <i class="fas fa-cogs"></i>
                                              <span>Тех. карта</span>
                                              <?php
                                              if (@$mod['specifications'][0]['processingPlan'] && @$mod['specifications'][0]['processingPlan']['archived'] == 0) {
                                                  $pp = $mod['specifications'][0]['processingPlan'];
                                                  echo '<a class="btn-xs btn-info" target="_blank" href="' . $pp['uuidHref'] . '">Открыть</a>';

                                                  echo '<span>Создана<br>' . engine::format_datetime($pp['creationDate']) . '</span>';

                                              } elseif (@!$mod['specifications'][0]['ppid']) {
                                                  if (@$mod['specifications'][0]['processingPlan']['archived'] == 1) echo '<span><b>в архиве</b></span>'; else
                                                      if (!@$mod['specifications'][0]) echo '<span>нет спецификации</span>'; else echo '<span>еще не создана</span>';
                                              } else {
                                                  echo '<a class="btn-xs btn-info" target="_blank" href="https://online.moysklad.ru/app/#ProcessingPlan/edit?id=' . $mod['specifications'][0]['ppid'] . '">Открыть</a>';

                                                  echo '<span>Спецификация создана из тех. карты</span>';
                                                  echo '<span>' . engine::format_datetime($mod['specifications'][0]['created']) . '</span>';
                                              }
                                              ?>
                                          </span>
                                            <span class="docBlock">
                                                  <i class="fas fa-pencil-ruler"></i>
                                                  <span>Чертеж</span>
                                                  <?php

                                                  if (@$mod['schemes']) { //Чертеж
                                                      echo '<a class="btn-xs btn-info" target="_blank" href="/downloadFile.php?fileId=' . $mod['schemes'][0]['fileId'] . '&watermarkText=R' . $mod['schemes'][0]['revision'] . '&mod=schemes&latest=' . true . '&created=' . $mod['created'] . '&article=' . $product['article'] . '">Ревизия R' . $mod['schemes'][0]['revision'] . '</a>';
                                                  } else {
                                                      echo '<span>не был загружен</span>';
                                                  }
                                                  echo '<hr style="margin: 1px;"><a href="#" class="addScheme" data-modid="' . $mod['id'] . '">Загрузить</a>';

                                                  ?>
                                        </span>
                                            <span class="docBlock">
                                                  <i class="fas fa-boxes"></i>
                                                  <span>Упаковка</span>
                                                  <?php
                                                  if (@$mod['packs']) {

                                                      $typeFilePack = '';
                                                      $fileType = engine::DB()->query('SELECT * FROM `@[prefix]files`, @[prefix]pack WHERE @[prefix]files.id LIKE @[prefix]pack.fileId');
                                                      foreach ($fileType as $type) {
                                                          if ($type['contentType'] == 'text/plain') {
                                                              $typeFilePack = '.txt';
                                                          }
                                                          if ($type['contentType'] == 'application/vnd.ms-excel') {
                                                              $typeFilePack = '.xls';
                                                          }
                                                          if ($type['contentType'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                                                              $typeFilePack = '.xlsx';
                                                          }
                                                      }

                                                      echo '<a class="btn-xs btn-info" href="/index.php?component=warehouse&page=package&id=' . $mod['packs'][0]['id'] . '">Ревизия R' . $mod['packs'][0]['revision'] . '</a>';
                                                      echo '<a href="/downloadFile.php?fileId=' . $mod['packs'][0]['fileId'] . '&type=packSpecification&created=' . $mod['created'] . '&revision=R' . $mod['packs'][0]['revision'] . '&article=' . $product['article'] . $typeFilePack . '">Скачать</a>';

                                                      if (count($mod['packs']) != 1) {
                                                          #echo '<a class="btn-xs btn-info" href="/index.php?component=warehouse&page=package&id='. $mod['packs'][0]['id'].'">Открыть</a>';

                                                          echo '<div class="dropdown show">
  <a class=" dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Другие</a>
  <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">';
                                                          foreach ($mod['packs'] as $pack) {
                                                              echo '<a class="dropdown-item" href="/index.php?component=warehouse&page=package&id=' . $pack['id'] . '">Ревизия <b>R' . $pack['revision'] . '</b></a>';
                                                          }
                                                          echo '</div></div>';
                                                      }
                                                  } else {
                                                      echo '<span>нет упаковки</span>';
                                                  }
                                                  echo '<hr style="margin: 1px;"><a href="#" class="addPackage" data-modid="' . $mod['id'] . '">Загрузить</a>';

                                                  ?>
                                        </span>
                                            <span class="docBlock">
                                                  <i class="fas fa-wrench"></i>
                                                  <span>Схема сборки</span>
                                                  <?php

                                                  if (@$mod['assemblingSchemes']) { //Схема сборки

                                                      echo '<a class="btn-xs btn-info" target="_blank" href="/downloadFile.php?fileId=' . $mod['assemblingSchemes'][0]['fileId'] . '&watermarkText=R' . $mod['assemblingSchemes'][0]['revision'] . '&mod=assemblingSchemes&latest=' . true . '&created=' . $mod['created'] . '&article=' . $product['article'] . '">Ревизия R' . $mod['assemblingSchemes'][0]['revision'] . '</a>';
                                                  } else {
                                                      echo '<span>не была загружена</span>';
                                                  }
                                                  echo '<hr style="margin: 1px;"><a href="#" class="addAssemblingScheme" data-modid="' . $mod['id'] . '">Загрузить</a>';

                                                  ?>
                                        </span>
                                            <?php
                                            if (@$mod['packageSchemes']) {
                                                echo '<span class="docBlock">';
                                                echo '<i class="fas fa-box"></i>';
                                                echo ' <span>Схема упаковки</span>';
                                                echo '<a class="btn-xs btn-info" target="_blank" href="/downloadFile.php?fileId=' . $mod['packageSchemes'][0]['fileId'] . '&watermarkText=R' . $mod['packageSchemes'][0]['revision'] . '&mod=packageSchemes&latest=' . true . '&created=' . $mod['created'] . '&article=' . $product['article'] . '">Ревизия R' . $mod['packageSchemes'][0]['revision'] . '</a>';
                                                echo '<hr style="margin: 1px;"><a href="#" class="addPackageScheme" data-modid="' . $mod['id'] . '">Загрузить</a>';
                                            } else {
                                                echo '<span class="docBlock" style="display: none;">';
                                            }
                                            ?>
                                            </span>
                                            <span class="docBlock">
                                                  <i class="fas fa-drafting-compass"></i>
                                                  <span>DWG</span>
                                                  <?php

                                                  if (@$mod['dwgSchemes']) { //Схема упаковки
                                                      echo '<a class="btn-xs btn-info" target="_blank" href="/downloadFile.php?fileId=' . $mod['dwgSchemes'][0]['fileId'] . '&watermarkText=R' . $mod['dwgSchemes'][0]['revision'] . '&mod=dwg&latest=' . true . '&created=' . $mod['created'] . '&article=' . $product['article'] . '">Ревизия R' . $mod['dwgSchemes'][0]['revision'] . '</a>';
                                                  } else {
                                                      echo '<span>не была загружена</span>';
                                                  }
                                                  echo '<hr style="margin: 1px;"><a href="#" class="addDwgScheme" data-modid="' . $mod['id'] . '">Загрузить</a>';

                                                  ?>
                                        </span>
                                            <span class="docBlock">
                                                  <i class="fas fa-list-ul"></i>
                                                  <span>Упаковочная ведомость</span>
                                                  <?php
                                                  //$fileName = urlencode('Этикетки '.$product['article'].' МОД '.$modification['name'].' '.'R'.$pack['revision']);
                                                  if (@$mod['packs']) { //Комплектация
                                                      $fileNameK = urlencode($mod['name'] . 'R' . $mod['packs'][0]['revision'] . ' ' . $product['article'] . ' упаковочная ведомость');
                                                      echo '<span>Ревизия R' . $mod['packs'][0]['revision'] . '</span>';
                                                      echo '<a href="/index.php?component=system&do=generateXLSX&template=packinglist&type=xlsx&id=' . $mod['packs'][0]['id'] . '&filename=' . $fileNameK . '&created=' . $mod['created'] . '&revision=R' . $mod['packs'][0]['revision'] . '&article=' . $product['article'] . '">XLSX</a>';
                                                      #echo '<a href="/index.php?component=system&do=generateXLSX&template=packinglist&type=pdf&id='.$mod['packs'][0]['id'].'&filename='.$fileNameK.'">PDF</a>'; #todo fix auto row height in pdf
                                                  } else {
                                                      echo '<span>нет упаковки</span>';
                                                      echo '<span>генерируется автоматически</span>';
                                                  }

                                                  ?>
                                        </span>
                                            <span class="docBlock">
                                                  <i class="fas fa-tags"></i>
                                                  <span>Этикетки</span>
                                                  <?php
                                                  //$fileName = urlencode('Этикетки '.$product['article'].' МОД '.$modification['name'].' '.'R'.$pack['revision']);
                                                  if (@$mod['packs']) { //Комплектация
                                                      $fileNameK = urlencode($mod['name'] . 'R' . $mod['packs'][0]['revision'] . ' ' . $product['article'] . ' этикетки');

                                                      echo '<span>Ревизия R' . $mod['packs'][0]['revision'] . '</span>';
                                                      echo '<a href="/index.php?component=system&do=generateXLSX&template=package&type=xlsx&id=' . $mod['packs'][0]['id'] . '&filename=' . $fileNameK . '&created=' . $mod['created'] . '&revision=R' . $mod['packs'][0]['revision'] . '&article=' . $product['article'] . '">XLSX</a>';
                                                      echo '<a href="/index.php?component=system&do=generateXLSX&template=package&type=pdfcropped&id=' . $mod['packs'][0]['id'] . '&filename=' . $fileNameK . '&created=' . $mod['created'] . '&revision=R' . $mod['packs'][0]['revision'] . '&article=' . $product['article'] . '">PDF</a>';
                                                  } else {
                                                      echo '<span>нет упаковки</span>';
                                                      echo '<span>генерируются автоматически</span>';
                                                  }

                                                  ?>
                                        </span>
                                            <span class="docBlock">
                                                  <i class="fa-solid fa-circle-down"></i>
                                                  <span>Вид сверху</span>
                                                  <?php
                                                  $typeTopImage = '';
                                                  $fileType = engine::DB()->query('SELECT * FROM `@[prefix]files`, @[prefix]topView WHERE @[prefix]files.id LIKE @[prefix]topView.fileId');
                                                  foreach ($fileType as $type) {
                                                      if ($type['contentType'] == 'image/jpeg') {
                                                          $typeTopImage = '.jpg';
                                                      }
                                                      if ($type['contentType'] == 'image/png') {
                                                          $typeTopImage = '.png';
                                                      }
                                                  }

                                                  if (@$mod['topView']) { //Вид сверху
                                                      echo '<a class="btn-xs btn-info" target="_blank" href="/downloadFile.php?fileId=' . $mod['topView'][0]['fileId'] . '&watermarkText=R' . $mod['topView'][0]['revision'] . '&mod=topView&latest=' . true . '&created=' . $mod['created'] . '&article=' . urlencode(" Вид сверху" . $typeTopImage) . '">Ревизия R' . $mod['topView'][0]['revision'] . '</a>';
                                                  } else {
                                                      echo '<span>не был загружен</span>';
                                                  }
                                                  echo '<hr style="margin: 1px;"><a href="#" class="addTopView" data-modid="' . $mod['id'] . '">Загрузить</a>';
                                                  ?>
                                        </span>
                                            <span class="docBlock">
                                                  <i class="fa-solid fa-share"></i>
                                                  <span>Вид сбоку</span>
                                                  <?php

                                                  $typeSideImage = '';
                                                  $fileType = engine::DB()->query('SELECT * FROM `@[prefix]files`, @[prefix]sideView WHERE @[prefix]files.id LIKE @[prefix]sideView.fileId');
                                                  foreach ($fileType as $type) {
                                                      if ($type['contentType'] == 'image/jpeg') {
                                                          $typeSideImage = '.jpg';
                                                      }
                                                      if ($type['contentType'] == 'image/png') {
                                                          $typeSideImage = '.png';
                                                      }
                                                  }

                                                  if (@$mod['sideView']) { //Вид сбоку
                                                      echo '<a class="btn-xs btn-info" target="_blank" href="/downloadFile.php?fileId=' . $mod['sideView'][0]['fileId'] . '&watermarkText=R' . $mod['sideView'][0]['revision'] . '&mod=sideView&latest=' . true . '&created=' . $mod['created'] . '&article=' . urlencode(" Вид сбоку" . $typeSideImage) . '">Ревизия R' . $mod['sideView'][0]['revision'] . '</a>';
                                                  } else {
                                                      echo '<span>не был загружен</span>';
                                                  }
                                                  echo '<hr style="margin: 1px;"><a href="#" class="addSideView" data-modid="' . $mod['id'] . '">Загрузить</a>';

                                                  ?>
                                        </span>
                                            <span class="docBlock">
                                                  <i class="fa-brands fa-unity"></i>
                                                  <span>Загрузить .step</span>
                                                  <?php

                                                  if (@$mod['stepView']) { //step файл
                                                      echo '<a class="btn-xs btn-info" target="_blank" href="/downloadFile.php?fileId=' . $mod['stepView'][0]['fileId'] . '&watermarkText=R' . $mod['stepView'][0]['revision'] . '&mod=stepView&latest=' . true . '&created=' . $mod['created'] . '&article=' . $product['article'] . '.step">Ревизия R' . $mod['stepView'][0]['revision'] . '</a>';
                                                  } else {
                                                      echo '<span>не был загружен</span>';
                                                  }
                                                  echo '<hr style="margin: 1px;"><a href="#" class="addStepView" data-modid="' . $mod['id'] . '">Загрузить</a>';

                                                  ?>
                                        </span>
                                        </div>
                                    </div>
                                </div>
                                <?
                            }
                        ?>
                    </div>
                </div>
            </div>
            <!-- /.tab-content -->
        </div>
        <?php
        if ($processingPlans) {
            ?>
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Тех. карты в МойСклад</h3>
                </div>
                <div class="card-body">

                    <table class="table">
                        <thead>
                        <tr>
                            <th>Последнее обновление</th>
                            <th>Название</th>
                            <th>Модификация</th>
                            <th>Ревизия</th>
                            <th>В архиве</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php

                        foreach ($processingPlans as $p) {

                            $actions = '';
                            if (@!$p['data']['archived']) {
                                $actions .= '<a href="#" class="processingPlan_archive btn btn-danger" data-id="' . $p['data']['id'] . '" data-name="' . htmlspecialchars($p['data']['name']) . '">Отправить в архив</a>';
                            } else {
                                $actions .= '<a href="#" class="processingPlan_unArchive btn btn-success" data-id="' . $p['data']['id'] . '" data-name="' . htmlspecialchars($p['data']['name']) . '">Достать из архива</a>';
                            }
                            echo '<tr>';
                            echo '<td>' . engine::format_datetime($p['data']['updated']) . '</td>';
                            echo '<td><a target="_blank" href="' . $p['data']['meta']['uuidHref'] . '">' . $p['data']['name'] . '</a></td>';
                            echo '<td>' . (@$p['modificationName'] ?: '---') . '</td>';
                            echo '<td>' . (@$p['revision'] || (@$p['revision'] . '' == '0') ? $p['revision'] : '---') . '</td>';
                            echo '<td class="' . (@$p['data']['archived'] ? 'text-red' : 'text-green') . '">' . (@$p['data']['archived'] ? 'В архиве' : 'В работе') . '</td>';
                            echo '<td>' . $actions . '</td>';
                            echo '</tr>';
                        }

                        ?>
                        </tbody>
                    </table>

                </div>
            </div>
            <?php
        }
        ?>

        <?php
        if ($processingOrders) {
            ?>
            <div class="card card-light">
                <div class="card-header">
                    <h3 class="card-title">Заказы на производство</h3>
                </div>
                <div class="card-body">

                    <table class="table">
                        <thead>
                        <tr>
                            <th>Дата запуска</th>
                            <th>Название</th>
                            <th>Произведено</th>
                            <th>Модификация</th>
                            <th>Ревизия</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $ready = 0;
                        $quantity = 0;
                        $modsAndLastSpecId = [];
                        #engine::debug_printArray($modifications);
                        foreach ($modifications as $m) {
                            if (array_key_exists(0, $m['specifications'])) $modsAndLastSpecId[$m['id']] = $m['specifications'][0]['id'];
                        }

                        foreach ($processingOrders as $p) {
                            $isItLastRevision = false;
                            if (array_key_exists($p['modificationId'], $modsAndLastSpecId) && $modsAndLastSpecId[$p['modificationId']] == @$p['specificationId']) $isItLastRevision = true;
                            echo '<tr>';
                            echo '<td>' . engine::format_datetime($p['moment']) . '</td>';
                            echo '<td><a href="' . $p['uuidHref'] . '" target="_blank">' . $p['name'] . '</a></td>';
                            echo '<td>' . $p['ready'] . ' из ' . $p['quantity'] . '</td>';
                            echo '<td class="">' . $p['modificationName'] . '</td>';
                            echo '<td class="' . ($isItLastRevision ? '' : 'bg-gradient-red') . '">' . $p['revision'] . '</td>';
                            echo '<td>действия</td>';
                            echo '</tr>';
                            $ready += $p['ready'];
                            $quantity += $p['quantity'];
                        }

                        echo '<tr>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td><b>' . $ready . '</b> из <b>' . $quantity . '</b></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '</tr>'; #я знаю про colspan
                        ?>
                        </tbody>
                    </table>

                </div>
            </div>
            <?php
        }

        if ((date("H:i:s")) == (date("H:i:s", strtotime("+5 minutes")))) {
            // вывод текущего времени
           // echo date('H:i:s') . '<br>';
           // echo date('H:i:s', strtotime('+5 minutes'));
        }
        ?>
    </div>

</div>

<script>
    var ppLinkBlocked = false;
    $(document).ready(function () {
        //$(".draft").attr("checked", "checked");

        $('#actNewMod').click(function (e) {
            e.preventDefault();
            $('#modal-newMod').modal();

        })

        //    модальное окно для изменения коммента
        $('#commentEdit').click(function (e) {
            e.preventDefault();
            $('#modal-editComment').modal();
        })

        bsCustomFileInput.init();

        var sendBlocked = false;
        $(".modal form").submit(function (event) {
            if (sendBlocked === true) {
                event.preventDefault();
                alert('Дождитесь окончания загрузки!');
            } else {
                sendBlocked = true;
            }
        });

        $('.addSpecification').on('click', function (e) {
            e.preventDefault();
            $('.inputWithModId').val($(e.target).data('modid'));
            $('#modal-uploadSpec').modal();
        });
        $('.addPackage').on('click', function (e) {
            e.preventDefault();

            $('.inputWithModId').val($(e.target).data('modid'));
            $('#modal-uploadPackage').modal();

        });

        $('.addAssemblingScheme').on('click', function (e) {
            e.preventDefault();

            $('#fileUploadModalTitle').text('Загрузка схемы сборки');

            $('.inputWithModId').val($(e.target).data('modid'));
            $('.inputWithFileType').val('assemblingScheme');
            $('#customFileUploadInput').attr('accept', '.pdf');
            $('.inputWithBackUrl').val(window.location);

            $('#filetype').html('Схема сборки (.pdf) <span class="required">*</span>');

            $('#modal-uploadFile').modal();

        });

        $('.addScheme').on('click', function (e) {
            e.preventDefault();

            $('#fileUploadModalTitle').text('Загрузка чертежа');

            $('.inputWithModId').val($(e.target).data('modid'));
            $('.inputWithFileType').val('scheme');
            $('#customFileUploadInput').attr('accept', '.pdf');
            $('.inputWithBackUrl').val(window.location);

            $('#filetype').html('Чертеж (.pdf) <span class="required">*</span>');

            $('#modal-uploadFile').modal();

        });

        $('.addPackageScheme').on('click', function (e) {
            e.preventDefault();
            $('#fileUploadModalTitle').text('Загрузка схемы упаковки');

            $('.inputWithModId').val($(e.target).data('modid'));
            $('.inputWithFileType').val('packageScheme');
            $('#customFileUploadInput').attr('accept', '.pdf');
            $('.inputWithBackUrl').val(window.location);


            $('#filetype').html('Схема упаковки (.pdf) <span class="required">*</span>');
            $('#modal-uploadFile').modal();
        });

        $('.addDwgScheme').on('click', function (e) {
            e.preventDefault();
            $('#fileUploadModalTitle').text('Загрузка DWG');

            $('.inputWithModId').val($(e.target).data('modid'));
            $('.inputWithFileType').val('dwgScheme');
            $('#customFileUploadInput').attr('accept', '.dwg');
            $('.inputWithBackUrl').val(window.location);


            $('#filetype').html('Схема dwg (.dwg) <span class="required">*</span>');
            $('#modal-uploadFile').modal();
        });

        // вид сверху
        $('.addTopView').on('click', function (e) {
            e.preventDefault();
            $('#fileUploadModalTitle').text('Загрузка вида сверху');

            $('.inputWithModId').val($(e.target).data('modid'));
            $('.inputWithFileType').val('topView');
            $('#customFileUploadInput').attr('accept', '.png, .jpg, .jpeg');
            $('.inputWithBackUrl').val(window.location);


            $('#filetype').html('Вид сверху (.png, .jpg, .jpeg) <span class="required">*</span>');
            $('#modal-uploadFile').modal();
        });

        // вид сбоку
        $('.addSideView').on('click', function (e) {
            e.preventDefault();
            $('#fileUploadModalTitle').text('Загрузка вида сбоку');

            $('.inputWithModId').val($(e.target).data('modid'));
            $('.inputWithFileType').val('sideView');
            $('#customFileUploadInput').attr('accept', '.png, .jpg, .jpeg');
            $('.inputWithBackUrl').val(window.location);


            $('#filetype').html('Вид сбоку (.png, .jpg, .jpeg) <span class="required">*</span>');
            $('#modal-uploadFile').modal();
        });

        // вид сверху
        $('.addStepView').on('click', function (e) {
            e.preventDefault();
            $('#fileUploadModalTitle').text('Загрузка .step файла');

            $('.inputWithModId').val($(e.target).data('modid'));
            $('.inputWithFileType').val('stepView');
            $('#customFileUploadInput').attr('accept', '.step');
            $('.inputWithBackUrl').val(window.location);


            $('#filetype').html('Step файл (.step) <span class="required">*</span>');
            $('#modal-uploadFile').modal();
        });

        $('#formCreateMod').submit(function (e) {
            e.preventDefault();
            $('#createModButton').attr('disabled', true);
            $.ajax({
                dataType: "json",
                method: 'POST',
                url: '/ajax.php?component=factory&do=createModification',
                data: {
                    productId: '<?=$product['id']?>',
                    modName: $('#inputNewModName').val(),
                    comment: $('#inputNewComment').val(),
                    draft: 1
                },
                success: function (response) {
                    if (response.status === true) {
                        window.location.reload();
                    } else {
                        alert(response.reason);
                        $('#createModButton').attr('disabled', false);
                    }
                }
            });
        })
        $('a.delEmptyModification').on('click', function (e) {
            e.preventDefault();
            $.ajax({
                dataType: "json",
                method: 'POST',
                url: '/ajax.php?component=factory&do=deleteModification',
                data: {modId: $(this).data('modid')},
                success: function (response) {
                    if (response.status === true) {
                        window.location.reload();
                    } else {
                        alert(response.message);
                    }
                }
            });
        });

        $('a.createModFromPP').on('click', function (e) {
            e.preventDefault();
            $.ajax({
                dataType: "json",
                method: 'POST',
                url: '/ajax.php?component=factory&do=createModificationFromPP',
                data: {ppid: $(e.target).data('id')},
                success: function (response) {
                    if (response.status === true) {
                        window.location.reload();
                    } else {
                        alert(response.message);
                    }
                }
            });
        });

        $('.processingPlan_archive').on('click', function (e) {
            if (ppLinkBlocked === true) {
                alert('Дождитесь окончания действия или обновите страницу');
                e.preventDefault();
                return;
            }
            e.preventDefault();
            ppLinkBlocked = true;
            $.ajax({
                dataType: "json",
                method: 'POST',
                url: '/ajax.php?component=factory&do=archivePP',
                data: {ppid: $(e.target).data('id'), archive: 1, ppname: $(e.target).data('name')},
                success: function (response) {
                    if (response.status === true) {
                        window.location.reload();
                    } else {
                        alert(response.message);
                    }
                    ppLinkBlocked = false;
                }
            });
        });
        $('.processingPlan_unArchive').on('click', function (e) {
            if (ppLinkBlocked === true) {
                alert('Дождитесь окончания действия или обновите страницу');
                e.preventDefault();
                return;
            }
            e.preventDefault();
            ppLinkBlocked = true;
            $.ajax({
                dataType: "json",
                method: 'POST',
                url: '/ajax.php?component=factory&do=archivePP',
                data: {ppid: $(e.target).data('id'), archive: 0, ppname: $(e.target).data('name')},
                success: function (response) {
                    if (response.status === true) {
                        window.location.reload();
                    } else {
                        alert(response.message);
                    }
                    ppLinkBlocked = false;
                }
            });
        });
        // окно для редактирования комментария
        $('#formEditComment').submit(function (e) {
            e.preventDefault();
            $('#editCommentButton').attr('disabled', true);
            $.ajax({
                dataType: "json",
                method: 'POST',
                url: '/ajax.php?component=factory&do=editComment',
                data: {
                    productId: '<?=$product['id']?>',
                    modName: $("#editComment").data('modname'),
                    comment: $('#editComment').val()
                },
                success: function (response) {
                    if (response.status === true) {
                        window.location.reload();
                    } else {
                        alert(response.reason);
                        $('#editCommentButton').attr('disabled', false);
                    }
                }
            });
        })
        // проверка checkbox черновика на checked
        $(".draft").on("click", function () {
            if ($(this).is(":checked")) {
                $.ajax({
                    url: '/ajax.php?component=factory&do=deleteDraft',
                    type: 'POST',
                    data: {
                        settings: 1,
                        id: this.value,
                        checked: 1,
                        productId: '<?=$product['id']?>',
                        modId: $(this).data('modid'),
                        message: 'установил галочку черновик'
                    },
                    beforeSend: function () {
                        $(".draft").disabled = true;
                    },
                    complete: function () {
                        $(".draft").disabled = false;
                    },
                    success: function (response) {
                        console.log(response);
                    }
                });
            } else {
                $.ajax({
                    url: '/ajax.php?component=factory&do=deleteDraft',
                    type: 'POST',
                    data: {
                        settings: 0,
                        id: this.value,
                        checked: 0,
                        productId: '<?=$product['id']?>',
                        modId: $(this).data('modid'),
                        message: 'убрал галочку черновик'
                    },
                    beforeSend: function () {
                        $(".draft").disabled = true;
                    },
                    complete: function () {
                        $(".draft").disabled = false;
                    },
                    success: function (response) {
                        console.log(response);
                    }
                });
            }
        })

         //отправка размеров
        $('.sendModParamsForm').submit(function (e) {
            e.preventDefault();
            let target = e.target;
            let modIdVal = $(target).find('input.setWidth').data('modid');
            let widthVal = $(target).find('input.setWidth').val();
            let lengthVal = $(target).find('input.setLength').val();
            let heightVal = $(target).find('input.setHeight').val();
            let dropHeightVal = $(target).find('input.dropHeight').val();
            let ageVal = $(target).find('select.age option:selected').val();
            // для теста
         ///   let ageVal = '15 лет';
            let sendButtVal = $(target).find('input.sendModParamsButton');
         //   return ;
            sendButtVal.attr('disabled', true);
            $.ajax({
                dataType: "json",
                method: 'POST',
                url: '/ajax.php?component=factory&do=sendModParams',
                data: {
                    productId: '<?=$product['id']?>',
                    modId: modIdVal,
                    width: widthVal,
                    height: heightVal,
                    length: lengthVal,
                    dropHeight: dropHeightVal,
                    age: ageVal
                },
                success: function (response) {
                    if (response.status === true) {
                        window.location.reload();
                    } else {
                        alert(response.reason);
                        sendButtVal.attr('disabled', false);
                    }
                }
            });
        });

        // отправка в тг каждые 5 мин
        function timeMessage() {
            $.ajax({
                dataType: "json",
                method: 'POST',
                url: '/ajax.php?component=factory&do=timeInterval',
                data: {
                    productId: '<?=$product['id']?>'
                },
                success: function (response) {
                    if (response.status === true) {

                    } else {
                        alert(response.reason);
                    }
                }
            });
        }

        // вызов функции каждые 5 минут
        setInterval(timeMessage, 300000);

    })
</script>
