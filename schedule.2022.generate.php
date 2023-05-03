<?php
if(!@$_COOKIE['schedule_switcher']) setcookie('schedule_switcher', 'hide', time()+60*60*24*30);

engine::TEMPLATE()->setTagValue('pagename', 'График производства');

module::getModuleClass('breadcrumbs')->addElement('#!', 'Производство ');;
mod_breadcrumbs::$disableDefault = true;
module::getModuleClass('breadcrumbs')->addElement('/index.php?component=factory&page=scheduleInDev', 'График производства');;
module::getModuleClass('breadcrumbs')->addElement('/index.php?component=system&do=generateXLSX&template=schedule&type=xlsx&mode=2022&id=15&filename='.urlencode('График производства '.date('Y-m-d')), '<i class="fa fa-file-excel"></i> excel');

$schedule = engine::getClass('schedule');

#$conf = $schedule->getCurrentScheduleSettings();
#if(isset($_GET['new'])){
#    $conf['generateOnDate'] = $schedule->getFirstOrderMoment();
#    $conf['ignoreFixed'] = true;
#    $schedule->saveRevision($schedule->generateRevision($conf), $conf);
#}else{
#    $check = $schedule->check(); //['minNewOrderTime' => $minNewOrderTime, 'newOrders' => $newOrders];
#    if($check['minNewOrderTime'] > 0) $schedule->saveRevision($schedule->generateRevision($conf, $check['minNewOrderTime'], $check['newOrders']), $conf);
#    else $schedule->setRedZone();
#    #else echo 'nothing to do';
#}
#
$schedule->setDate(date('Y-m-d'));
$schedule->tick();