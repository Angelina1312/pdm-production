<?php
try {
    $width = $_POST['width'];
    $height = $_POST['height'];
    $length = $_POST['length'];
    $dropHeight = $_POST['dropHeight'];
    $age = $_POST['age'];
    $modId = $_POST['modId'];
    $productId = $_POST['productId'];


    $status = engine::FACTORY()->sendModParams($width, $height, $length, $dropHeight, $age, $modId, $productId);
    print json_encode($status);


} catch (Exception $ex) {
    print json_encode([
        'status' => false,
        'message' => $ex->getMessage()
    ]);
}