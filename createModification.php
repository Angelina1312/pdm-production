<?php
try {
    if (!$_POST['modName']) {
        print json_encode([
            'status' => false,
            'message' => 'Введите название'
        ]);
        exit;
    }
    $name = trim($_POST['modName']);
    $comment = trim($_POST['comment']);
    if (strlen($name) < 3) {
        print json_encode([
            'status' => false,
            'message' => 'Слишком короткое название'
        ]);
        exit;
    }

    $draft = $_POST['draft'];

    $status = engine::FACTORY()->createModification($_POST['productId'], $name, engine::USERS()->getCurrentUser()['id'], $comment, $draft);
    print json_encode($status);


} catch (Exception $ex) {
    print json_encode([
        'status' => false,
        'message' => $ex->getMessage()
    ]);
}