<?php
define('included', true);

if(!@$_COOKIE['schedule_switcher']) setcookie('schedule_switcher', 'hide', time()+60*60*24*30);

engine::TEMPLATE()->setTagValue('pagename', 'График производства: настройка');

module::getModuleClass('breadcrumbs')->addElement('#!', 'Производство ');;
mod_breadcrumbs::$disableDefault = true;
module::getModuleClass('breadcrumbs')->addElement('/index.php?component=factory&page=schedule.2022', 'График производства');
module::getModuleClass('breadcrumbs')->addElement('/index.php?component=factory&page=schedule.2022.settings', 'Настройка и тестирование');
#module::getModuleClass('breadcrumbs')->addElement('/index.php?component=system&do=generateXLSX&template=schedule&type=xlsx&mode=2022&id=15&filename='.urlencode('График производства '.date('Y-m-d')), '<i class="fa fa-file-excel"></i> excel');
$schedule = engine::getClass('schedule');



$showSchedule = false;
#if(!@$_GET['testparams']) $mode = 'showRevision'; else $mode = 'test';
#if($mode == 'showRevision'){
#    $revision = $schedule->getRevision(@$_GET['revision']);
#}
if(@$_POST['act_test'] == 'yes'){
    $config = $schedule->defaults;
    foreach($config as $k=>$v){
        if(is_bool($v)){
            $config[$k] = array_key_exists($k, $_POST);
        }else{
            $config[$k] = $_POST[$k];
        }
    }

    $revision = $schedule->generateRevision($config);
    $showSchedule = true;
}

if(@$revision['params']) $currentSettings = $revision['params'];
else $currentSettings = $schedule->getCurrentScheduleSettings();

$firstOrder = $schedule->getFirstOrderMoment();



//    private array $defaults = [
//        'period' => 7, //период графика, дней
//        'velocity' => 5000000, //скорость производства, руб (в период)
//        'daysForStart' => 14, //время на запуск, дней
//        'weekEnd' => 6, //последний рабочий день недели
//        'generateOnDate' => 'today',
//        'ignoreSumOfRedZone' => false,
//        'subSumGroupForRedZone' => true
//    ];


#engine::debug_printArray($_POST);
#engine::debug_printArray(@$revision['params']);
?>

    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title">Основные параметры</h3>
        </div>
        <form method="POST">
            <input type="hidden" name="test">
            <!-- /.card-header -->
            <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <!-- text input -->
                            <div class="form-group">
                                <label>period - период графика, дней</label>
                                <input type="number" class="form-control" name="period" value="<?=$currentSettings['period']?>" placeholder="<?=$currentSettings['period']?>">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label>velocity, скорость производства, руб (в период)</label>
                                <input type="number" class="form-control" step="100000" name="velocity" value="<?=$currentSettings['velocity']?>" placeholder="<?=$currentSettings['velocity']?>">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label>daysForStart, время на запуск, дней</label>
                                <input type="number" class="form-control" name="daysForStart" value="<?=$currentSettings['daysForStart']?>" placeholder="<?=$currentSettings['daysForStart']?>">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label>weekEnd, последний рабочий день недели (1-7)</label>
                                <input type="number" min="1" max="7" class="form-control" name="weekEnd" value="<?=$currentSettings['weekEnd']?>" placeholder="<?=$currentSettings['weekEnd']?>">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label>generateOnDate, сгенерировать на дату</label>
                                <input type="text" class="form-control" name="generateOnDate" value="<?=($currentSettings['generateOnDate'] == 'today' ? $firstOrder : $currentSettings['generateOnDate'])?>" placeholder="<?=$firstOrder;?>">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label></label>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="customCheckbox1" value="checked" name="ignoreSumOfRedZone" <?=($currentSettings['ignoreSumOfRedZone']) ? 'checked' : ''?>>
                                    <label for="customCheckbox1" class="custom-control-label">ignoreSumOfRedZone, игнорировать сумму в штрафной зоне</label>
                                </div>
                            </div>

                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label></label>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="customCheckbox2" value="checked" name="subSumGroupForRedZone" <?=($currentSettings['subSumGroupForRedZone']) ? 'checked' : ''?>>
                                    <label for="customCheckbox2" class="custom-control-label">subSumGroupForRedZone, считать красную зону отдельно</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label></label>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="customCheckbox3" value="checked" name="ignoreFixed" <?=($currentSettings['ignoreFixed']) ? 'checked' : 'checked'?>>
                                    <label for="customCheckbox3" class="custom-control-label">ignoreFixed, игнорировать зафиксированные значения</label>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
            <!-- /.card-body -->
            <div class="card-footer">
                <button type="submit" class="btn btn-info" value="yes" name="act_test">Тестировать настройки</button>
                <button type="submit" class="btn btn-info" value="yes" disabled name="act_save">Сохранить настройки</button>
                <button type="submit" class="btn btn-info" value="yes" disabled name="act_createRevision">Создать ревизию графика сохранив настройки</button>
            </div>
        </form>
    </div>

<?php

if($showSchedule){
    $processingOrders = [];
    $factoryData = $schedule->getFactoryData();

    $processingOrders = $revision['orders'];
    $products = $revision['products'];
    include 'schedule.2022.php';
}

