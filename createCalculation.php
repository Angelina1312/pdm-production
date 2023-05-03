<?php
if(!@$_POST['specId']) exit(json_encode(['status'=>false, 'message'=>'no specId']));
$specId = $_POST['specId'];
$cd = engine::CONFIG()['factory']['calculationsCD'];
if($cd && !in_array(engine::USERS()->getCurrentUser()['username'], ['aristov', 'ivandaf'])){
    $sc = engine::DB()->getOne('SELECT COUNT(id) FROM @[prefix]calculations WHERE specificationId = ?i AND calculationDate > (NOW() - INTERVAL ?i MINUTE)', $specId, $cd);
    if($sc > 0){
        exit(json_encode([
            'status'=>false,
            'message'=>'Можно создавать расчёты не чаще чем раз в '.$cd.' минут',
        ]));
    }
}
try{
    $calculation = engine::FACTORY()->coeffs->calculatev1($specId);
    $status = engine::FACTORY()->coeffs->saveCalculation($specId, $calculation);
    if($status)
        exit(json_encode([
            'status'=>true,
            'message'=>'Расчёт успешно создан',
        ]));
    else
        exit(json_encode([
            'status'=>false,
            'message'=>'При создании расчёта произошла ошибка',
        ]));
}catch(Exception $ex){
    exit(json_encode([
        'status'=>false,
        'message'=>$ex->getMessage(),
    ]));
}
