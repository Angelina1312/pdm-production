<?php
if(!@$_POST['ppid']) exit(json_encode(['status'=>false, 'message'=>'no ppid']));

$result = false;
try{
    $result = engine::FACTORY()->archiveProcessingPlans([$_POST['ppid']], (@$_POST['archive'] == 1 ? false : true));
    if($result) {
        $msg = @$_POST['archive'] == 1 ? 'отправил в архив' : 'достал из архива';
        engine::TG()->sendMessage(
            engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' ' . $msg . ' тех. карту <a target="_blank" href="https://online.moysklad.ru/app/#processingplan/edit?id=' . $_POST['ppid'] . '">' . @$_POST['ppname'] . '</a>'
        );
        exit(json_encode([
            'status' => true,
            'message' => 'Тех. карта успешно обновлена',
        ]));
    }
    else
        exit(json_encode([
            'status' => false,
            'message' => 'Ошибка при обновлении тех. карты #2',
        ]));
}catch (Exception $ex){
    exit(json_encode([
        'status' => false,
        'message' => $ex->getMessage(),
    ]));
}