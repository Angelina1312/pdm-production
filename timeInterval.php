<?php
try {
    $productId = $_POST['productId'];

    $status = engine::FACTORY()->timeInterval($productId);
    print json_encode($status);


} catch (Exception $ex) {
    print json_encode([
        'status' => false,
        'message' => $ex->getMessage()
    ]);
}