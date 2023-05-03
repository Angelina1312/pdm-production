<?php
try {
    $comment = $_POST['comment'];
    $name = $_POST['modName'];

    $status = engine::FACTORY()->editComment($_POST['productId'], $name, $comment);
    print json_encode($status);


} catch (Exception $ex) {
    print json_encode([
        'status' => false,
        'message' => $ex->getMessage()
    ]);
}