<?php
//TODO заблокировать удаление если модификация не пуста
if (!isset($_POST['modId'])) exit(json_encode(['status' => false, 'message' => 'Не указан ID модификации']));

//$mod = engine::FACTORY()->getModificationData($_POST['modId']);
//if(!$mod) exit(json_encode(['status'=> false, 'message'=>'Модификация не найдена']));

$a = engine::FACTORY()->removeModification($_POST['modId']);
if ($a)
    exit(json_encode(['status' => true, 'message' => 'Модификация удалена']));
else
    exit(json_encode(['status' => false, 'message' => 'При удалении произошла ошибка']));