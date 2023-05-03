<?php
try {
    $price = floatval($_POST['price']);
    $productId = $_POST['productId'];
    $modificationId = $_POST['modificationId'];


    $status = engine::FACTORY()->updateBuyPrice($price, $productId, $modificationId);
    print json_encode($status);


} catch (Exception $ex) {
    print json_encode([
        'status' => false,
        'message' => $ex->getMessage()
    ]);
}