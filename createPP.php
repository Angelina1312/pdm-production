<?php
if(!@$_POST['specId']) exit(json_encode(['status'=>false, 'message'=>'no specId']));


$calculate = engine::FACTORY()->coeffs->calculatev1($_POST['specId']);


if(!$calculate) exit(json_encode([
    'status' => false,
    'message' => 'Ошибка при создании тех. карты #1',
]));

$result = false;
try{
    $result = engine::FACTORY()->createProcessingPlan($_POST['specId'], $calculate);
    if($result)
        exit(json_encode([
            'status' => true,
            'message' => 'Тех. карта успешно создана',
        ]));
    else
        exit(json_encode([
            'status' => false,
            'message' => 'Ошибка при создании тех. карты #2',
        ]));
}catch (Exception $ex){
    exit(json_encode([
        'status' => false,
        'message' => $ex->getMessage(),
    ]));
}