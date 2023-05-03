<?php
try {

    if(isset($_POST['settings'])) {
        $checked = (int)$_POST['checked'];
        $id = $_POST['id'];
       // $nameMod = trim($_POST['modName']);
        $message = $_POST['message'];
        $modId = $_POST['modId'];

        $status = engine::FACTORY()->draftMod($_POST['productId'], $checked, $message, $id, $modId);
        print json_encode($status);
    }


} catch (Exception $ex) {
    print json_encode([
        'status' => false,
        'message' => $ex->getMessage()
    ]);
}