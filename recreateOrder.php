<?php
$out = [
    'status' => false
];
try{
    if(!@$_POST['action']) throw new Exception('no action');
    if(!@$_POST['orderId']) throw new Exception('no order');

    $orderReady = engine::DB()->getRow('SELECT ready FROM @[prefix]moysklad_processingOrders WHERE id=?s', $_POST['orderId']);


    if($orderReady['ready'] == 0) {
        switch($_POST['action']){
            case 'getData':
                $data = [];
                $order = engine::DB()->getRow('SELECT productId, `name`, `uuidHref`, `processingPlan` FROM @[prefix]moysklad_processingOrders WHERE id=?s', $_POST['orderId']);
                if(!@$order['productId']) throw new Exception('order not found');
                $data['order'] = $order;

                $product = engine::DB()->getRow('SELECT `name`, `article`, `uuidhref` FROM @[prefix]products WHERE id=?s', $order['productId']);
                if(!$product) throw new Exception('product not found #2');
                $data['product'] = $product;

                $pps = engine::DB()->getAll('SELECT `id`, `name` FROM @[prefix]pp WHERE archived = 0 AND deleted = 0 AND productId=?s ORDER BY `name` DESC', $order['productId']);
                foreach($pps as $k=>$pp){
                    $pps[$k]['current'] = ($order['processingPlan'] == $pp['id']);
                }
                $data['pp'] = $pps;

                $out['status'] = true;
                $out['data'] = $data;

                break;
            case 'recreate':
                if(!@$_POST['newPp']) throw new Exception('no processingPlan');
                $check = engine::DB()->getOne('SELECT orderId FROM @[prefix]schedule_recreate WHERE orderId=?s AND status=0', $_POST['orderId']);
                if($check) throw new Exception('Заказ уже пересоздается, обновите страницу через минуту');
                $currentOrder = engine::DB()->getRow('SELECT * FROM @[prefix]moysklad_processingOrders WHERE id=?s', $_POST['orderId']);



                engine::DB()->query('SET autocommit = 0;');
                engine::DB()->query('START TRANSACTION;');
                engine::DB()->query('INSERT INTO @[prefix]schedule_recreate (orderId, ppid, newPpId, userId, creationDate) VALUES (?s, ?s, ?s, ?i, NOW())', $currentOrder['id'], $currentOrder['processingPlan'], $_POST['newPp'], engine::USERS()->getCurrentUser()['id']);


                $status = engine::moySklad()->recreateProcessingOrder($_POST['orderId'], $_POST['newPp']);
                if($status['status']){
                    engine::DB()->query('UPDATE @[prefix]schedule_recreate SET newOrderId=?s, recreationDate = NOW(), status = 1 WHERE orderId =?s', $status['id'], $_POST['orderId']);



                    ob_start();
                    $schedule = engine::getClass('schedule');
                    $schedule->setDate(date('Y-m-d'));
                    $schedule->tick();
                    $out['tick'] = ob_get_clean();

                    engine::DB()->query('COMMIT;');
                    $out['data'] = 'Заказ успешно пересоздан';
                    $out['status'] = true;

                    $product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE id=?s', $currentOrder['productId']);
                    engine::TG()->sendMessage(engine::format_user(
                            engine::USERS()->getCurrentUser()['id']) . ' пересоздал заказ на производство <b>'.@$currentOrder['name'].'</b> на товар [<b>'.@$product['article'].'] '.@$product['name'].'</b>.
<a href="'.engine::CONFIG()['main']['mainAddr'].'/noauth.php?component=factory&page=schedule&hidelines=1#order'.@$status['id'].'">Новый заказ в графике</a>
<a href="https://online.moysklad.ru/app/#processingorder/edit?id='.@$status['id'].'">Новый заказ в МС</a>
<a href="https://online.moysklad.ru/app/#processingorder/edit?id='.$_POST['orderId'].'">Старый заказ в МС</a>'
                    );

                }else{
                    engine::DB()->query('ROLLBACK;');
                    $out['status'] = false;
                    $out['message'] = 'При пересоздании заказа произошла ошибка. Обновите страницу и попробуйте еще раз.';
                    $out['ms'] = @$status['ms'];
                    $out['request'] = @$status['request'];
                }


                break;
            default:
                throw new Exception('action not found');
        }
    } else {
        engine::DB()->query('ROLLBACK;');
        $out['status'] = false;
        $out['message'] = 'Невозможно пересоздать заказ из-за сданной продукции. Снимите проводку у связанных техопераций, пересоздайте заказ и создайте новые техоперации для нового заказа.';
    }



}catch (Exception $ex){
    $out['status'] = false;
    $out['message'] = $ex->getMessage();
    $out['stacktrace'] = $ex->getTraceAsString();
    engine::DB()->query('ROLLBACK;');

}



echo json_encode($out);