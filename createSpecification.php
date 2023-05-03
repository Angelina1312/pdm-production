<?php
//get revision
//check file
//save modification
//save specification
//save materials
//save coatings + additionalCoatings
//return new modification ID

//engine::debug_printArray($_POST);
try {
    if (!isset($_POST['article']) || !$_POST['article']) throw new Exception('Не указан артикул');
    $product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE article = ?s', $_POST['article']);

    if (!$product) throw new Exception('Товар с артикулом "' . $_POST['article'] . '" не найден');
    if (!isset($_POST['fileId']) || $_POST['fileId'] < 1) throw new Exception('Ошибка в fileId');
    $file = engine::getFileContent($_POST['fileId']);
    $data = engine::FACTORY()->parseSpec($file);

    $userId = engine::USERS()->getCurrentUser()['id'];
    engine::DB()->query('SET autocommit = 0');
    engine::DB()->query('START TRANSACTION');

    $modFromDB = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE productId=?s AND LOWER(`name`)=?s', $product['id'], mb_strtolower($_POST['mod']));
    if (!$modFromDB) {
        $userId = engine::USERS()->getCurrentUser()['id'];
        engine::DB()->query('INSERT INTO @[prefix]modifications (productId, `name`, created, lastupdate, createdBy) VALUES (?s, ?s, NOW(), NOW(), ?i)',
            $product['id'], $_POST['mod'], $userId);
        $modId = engine::DB()->insertId();
    } else {
        $modId = $modFromDB['id'];
    }

    $specificationFromDB = engine::DB()->getRow('SELECT * FROM @[prefix]specification WHERE modificationId = ?i ORDER BY id DESC', $modId);

    if (!$specificationFromDB) {
        $revision = 1;
    } else {
        $previousRevision = $specificationFromDB['revision'];
        $revision = (int)$previousRevision + 1;

    }

    if((int)$revision < (int)$data['revision']){
        $revision = $data['revision'];
    }

    engine::DB()->query('INSERT INTO @[prefix]specification (modificationId, revision, productId, fileId, canBeProduced, comment, created, createdBy) VALUES (?i, ?s, ?s, ?i, ?i, ?s, NOW(), ?i)', $modId, (int)$revision, $product['id'], $_POST['fileId'], ($_POST['canBeProduced'] ? 1 : 0), $_POST['comment'], $userId);
    $specificationId = engine::DB()->insertId();

    //engine::debug_printArray($data['materials']);
    //exit;

    foreach ($data['hierarchy'] as $item) {
            engine::DB()->query('INSERT INTO @[prefix]specification_source
                    (specificationId, article, `number`, `name`, materialProductId, quantity, weight, area, coating, coatingProductId, layer, site, `section`, `length`, `description`)
            VALUES (?i,             ?s,        ?s,     ?s,      ?s,                 ?i,     ?s,     ?s,     ?s,     ?s,             ?s,     ?s,     ?s,        ?s,      ?s)',
            $specificationId,
            $item['article'],
            $item['number'],
            $item['name'],
            (isset($data['materials'][$item['materialArticle']]['id']) ? $data['materials'][$item['materialArticle']]['id'] : ''),
            (int)$item['quantity'],
            (float)$item['weight'],
            (float)$item['area'],
            $item['coating'],
            ($item['coating'] ? $data['mspaints'][$item['coating']]['id'] : NULL),
            (float)$item['layer'],
            $item['site'],
            $item['section'],
            (float)$item['length'],
            $item['description']
        );
    }


    foreach ($data['paint'] as $p => $d) {
        $thisSiteArea = 0;
        foreach ($d as $xz => $paint) {
            $thisSiteArea += $paint['area'];
            engine::DB()->query('INSERT INTO @[prefix]specification_coatings (specificationId, coatingProductId, area, site) VALUES (?i, ?s, ?s, ?s)', $specificationId, $data['mspaints'][$xz]['id'], $paint['area'], $p);
        }
        if (isset($_POST['additionalCoatings'])) {
            foreach ($_POST['additionalCoatings'] as $addCoating) {
                if ($addCoating['site'] == $p) {
                    engine::DB()->query('INSERT INTO @[prefix]specification_coatings (specificationId, coatingProductId, area, site) VALUES (?i, ?s, ?s, ?s)', $specificationId, $addCoating['id'], $thisSiteArea, $p);
                }
            }
        }
    }

    foreach ($data['sum'] as $i => $s) {
        if(!isset($data['materials'][$i]['id'])) continue; //TODO DELETE THIS
        engine::DB()->query('INSERT INTO @[prefix]specification_materials
 (`specificationId`, `materialArticle`, materialId, `area`, `weight`, `length`, `quantity`) VALUES 
 (?i, ?s, ?s, ?s, ?s, ?s, ?i)',
            $specificationId,
            $i,
            @$data['materials'][$i]['id'],
            $s['area'],
            $s['weight'],
            $s['length'],
            $s['quantity']
        );
    }

    print json_encode([
        'status' => true,
        'id' => $specificationId
    ], JSON_UNESCAPED_UNICODE);
    engine::DB()->query('UPDATE @[prefix]modifications SET lastupdate=NOW() WHERE id=?i', $modFromDB['id']);

    engine::DB()->query('COMMIT;');

    engine::TG()->sendMessage(
        engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' загрузил спецификацию <a href="'.engine::CONFIG()['main']['mainAddr'].'/index.php?component=factory&page=specification&id='.$specificationId.'">'.$modFromDB['name'].'R'.$revision.'</a> для <a href="'.engine::CONFIG()['main']['mainAddr'].'/index.php?component=moysklad&page=product&productId='.$modFromDB['productId'].'">'.$product['article'].'</a>'
    );

//id	specificationId	coatingProductId	area	site
//            $item = [
//                'article' => null,
//                'number' => null,
//                'name' => null,
//                'materialArticle' => null,
//                'quantity' => null,
//                'weight' => null,
//                'area' => null,
//                'coating' => null,
//                'layer' => null,
//                'site' => null,
//                'section' => null,
//                'length' => null,
//                'description' => null
//            ];
} catch (Exception $ex) {
    engine::DB()->query('ROLLBACK;');
    print json_encode([
        'status' => false,
        'error' => $ex->getMessage(),
        'stacktrace' => $ex->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}