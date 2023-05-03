<?php
try {
    $allowedFileTypes = ['packageScheme', 'assemblingScheme', 'scheme', 'dwgScheme', 'topView', 'sideView', 'stepView'];
    if (!isset($_POST['modId'])) throw new Exception('Error #nf1');
    if (!isset($_POST['fileType'])) throw new Exception('Error #nf2');
    if (!in_array($_POST['fileType'], $allowedFileTypes)) throw new Exception('Error #nf3');
    if (!array_key_exists('file', $_FILES)) throw new Exception('Err #nf4');

    $modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id=?i', $_POST['modId']);
    if(!$modification) throw new Exception('Модификация "'.$_POST['modId'].'" не найдена');
    $product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE id = ?s', $modification['productId']);
    if(!$product) throw new Exception('Товар с id "'.$modification['productId'].'" не найден');
    //engine::saveFile('file');
    //if()



    $fileId = engine::saveFile('file', $_POST['fileType']);
    if (!$fileId) throw new Exception('Произошла ошибка при сохранении файла. Попробуйте еще раз. Err #nf5');

        switch (strtolower($_POST['fileType'])) {
            case 'packagescheme':
                $typeFilePack = '';
                $fileType = engine::DB()->query('SELECT * FROM `@[prefix]files`, @[prefix]pack WHERE @[prefix]files.id LIKE @[prefix]pack.fileId');
                foreach ($fileType as $type) {
                    if ($type['contentType'] == 'text/plain') {
                        $typeFilePack = '.txt';
                    }
                    if ($type['contentType'] == 'application/vnd.ms-excel')  {
                        $typeFilePack = '.xls';
                    }
                    if ($type['contentType'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                        $typeFilePack = '.xlsx';
                    }
                }
                $lastRev = engine::DB()->getRow('SELECT * FROM @[prefix]packageSchemes WHERE modId = ?i ORDER BY id DESC', $_POST['modId']);
                $revision = 1;
                if ($lastRev) {
                    $lastRevision = (int)$lastRev['revision'];
                    $revision = $lastRevision;
                    $revision++;
                    #if ($revision < 10) $revision = '0' . $revision;
                }
                engine::DB()->query('INSERT INTO  @[prefix]packageSchemes (modId, revision, uploadDate, uploadedBy, fileId) VALUES (?i, ?s, NOW(), ?i, ?i)', $_POST['modId'], (int)$revision, engine::USERS()->getCurrentUser()['id'], $fileId);
                engine::DB()->query('UPDATE @[prefix]modifications SET lastupdate=NOW() WHERE id=?i', $_POST['modId']);
                engine::TG()->sendMessage(
                    engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' загрузил схему упаковки <a href="'.engine::CONFIG()['main']['mainAddr'].'/downloadFile.php?fileId='.$fileId.'&type=packSpecification&created='.$modification['created'].'&revision=R'.$revision.'&article='.$product['article'].$typeFilePack.'">'.$modification['name'].'R'.$revision.'</a> для <a href="'.engine::CONFIG()['main']['mainAddr'].'/index.php?component=moysklad&page=product&productId='.$product['id'].'">'.$product['article'].'</a>'
                );
                if ($_POST['backUrl'])
                    header('location: ' . $_POST['backUrl']);
                else {
                    echo 'Файл успешно загружен';
                }
                break;
            case 'assemblingscheme':
                $lastRev = engine::DB()->getRow('SELECT * FROM @[prefix]assemblingSchemes WHERE modId = ?i ORDER BY id DESC', $_POST['modId']);
                $revision = 1;
                if ($lastRev) {
                    $lastRevision = (int)$lastRev['revision'];
                    $revision = $lastRevision;
                    $revision++;
                    //if ($revision < 10) $revision = '0' . $revision;
                }
                engine::DB()->query('INSERT INTO  @[prefix]assemblingSchemes (modId, revision, uploadDate, uploadedBy, fileId) VALUES (?i, ?s, NOW(), ?i, ?i)', $_POST['modId'], (int)$revision, engine::USERS()->getCurrentUser()['id'], $fileId);
                engine::DB()->query('UPDATE @[prefix]modifications SET lastupdate=NOW() WHERE id=?i', $_POST['modId']);
                engine::TG()->sendMessage(
                    engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' загрузил схему сборки <a href="'.engine::CONFIG()['main']['mainAddr'].'/downloadFile.php?fileId='.$fileId.'&watermarkText=R'. $revision .'&mod=assemblingSchemes&latest='. true .'&created='.$modification['created'].'&article='.$product['article'].'">'.$modification['name'].'R'.$revision.'</a> для <a href="'.engine::CONFIG()['main']['mainAddr'].'/index.php?component=moysklad&page=product&productId='.$product['id'].'">'.$product['article'].'</a>'
                );
                if ($_POST['backUrl'])
                    header('location: ' . $_POST['backUrl']);
                else {
                    echo 'Файл успешно загружен';
                }
                break;
            case 'scheme':
                $lastRev = engine::DB()->getRow('SELECT * FROM @[prefix]schemes WHERE modId = ?i ORDER BY id DESC', $_POST['modId']);
                $revision = 1;
                if ($lastRev) {
                    $lastRevision = (int)$lastRev['revision'];
                    $revision = $lastRevision;
                    $revision++;
                    //if ($revision < 10) $revision = '0' . $revision;
                }
                engine::DB()->query('INSERT INTO @[prefix]schemes (modId, revision, uploadDate, uploadedBy, fileId) VALUES (?i, ?s, NOW(), ?i, ?i)', $_POST['modId'], (int)$revision, engine::USERS()->getCurrentUser()['id'], $fileId);
                engine::DB()->query('UPDATE @[prefix]modifications SET lastupdate=NOW() WHERE id=?i', $_POST['modId']);
                engine::TG()->sendMessage(
                    engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' загрузил чертеж <a href="'.engine::CONFIG()['main']['mainAddr'].'/downloadFile.php?fileId='.$fileId.'&watermarkText=R'. $revision .'&mod=schemes&latest='. true .'&created='.$modification['created'].'&article='.$product['article'].'">'.$modification['name'].'R'.$revision.'</a> для <a href="'.engine::CONFIG()['main']['mainAddr'].'/index.php?component=moysklad&page=product&productId='.$product['id'].'">'.$product['article'].'</a>'
                );

                if ($_POST['backUrl'])
                    header('location: ' . $_POST['backUrl']);
                else {
                    echo 'Файл успешно загружен';
                }
                break;
            case 'dwgscheme':
                $lastRev = engine::DB()->getRow('SELECT * FROM @[prefix]dwgSchemes WHERE modId = ?i ORDER BY id DESC', $_POST['modId']);
                $revision = 1;
                if ($lastRev) {
                    $lastRevision = (int)$lastRev['revision'];
                    $revision = $lastRevision;
                    $revision++;
                    //if ($revision < 10) $revision = '0' . $revision;
                }
                engine::DB()->query('INSERT INTO @[prefix]dwgSchemes (modId, revision, uploadDate, uploadedBy, fileId) VALUES (?i, ?s, NOW(), ?i, ?i)', $_POST['modId'], (int)$revision, engine::USERS()->getCurrentUser()['id'], $fileId);
                engine::DB()->query('UPDATE @[prefix]modifications SET lastupdate=NOW() WHERE id=?i', $_POST['modId']);
                engine::TG()->sendMessage(
                    engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' загрузил DWG <a href="'.engine::CONFIG()['main']['mainAddr'].'/downloadFile.php?fileId='.$fileId.'&watermarkText=R'. $revision .'&mod=dwg&latest='. true .'&created='.$modification['created'].'&article='.$product['article'].'">'.$modification['name'].'R'.$revision.'</a> для <a href="'.engine::CONFIG()['main']['mainAddr'].'/index.php?component=moysklad&page=product&productId='.$product['id'].'">'.$product['article'].'</a>'
                );

                if ($_POST['backUrl'])
                    header('location: ' . $_POST['backUrl']);
                else {
                    echo 'Файл успешно загружен';
                }
                break;
            // вид сверху
            case 'topview':
                $typeTopImage = '';
                $fileType = engine::DB()->query('SELECT * FROM `@[prefix]files`, @[prefix]topView WHERE @[prefix]files.id LIKE @[prefix]topView.fileId');
                foreach ($fileType as $type) {
                    if ($type['contentType'] == 'image/jpeg') {
                        $typeTopImage = '.jpg';
                    }
                    if ($type['contentType'] == 'image/png') {
                        $typeTopImage = '.png';
                    }
                }
                $lastRev = engine::DB()->getRow('SELECT * FROM @[prefix]topView WHERE modId = ?i ORDER BY id DESC', $_POST['modId']);
                $revision = 1;
                if ($lastRev) {
                    $lastRevision = (int)$lastRev['revision'];
                    $revision = $lastRevision;
                    $revision++;
                    //if ($revision < 10) $revision = '0' . $revision;
                }
                engine::DB()->query('INSERT INTO @[prefix]topView (modId, revision, uploadDate, uploadedBy, fileId) VALUES (?i, ?s, NOW(), ?i, ?i)', $_POST['modId'], (int)$revision, engine::USERS()->getCurrentUser()['id'], $fileId);
                engine::DB()->query('UPDATE @[prefix]modifications SET lastupdate=NOW() WHERE id=?i', $_POST['modId']);
                engine::TG()->sendMessage(
                    engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' загрузил вид сверху <a href="'.engine::CONFIG()['main']['mainAddr'].'/downloadFile.php?fileId='.$fileId.'&watermarkText=R'. $revision .'&mod=topView&latest='. true .'&created='.$modification['created'].'&article='.urlencode(" Вид сверху".$typeTopImage).'">'.$modification['name'].'R'.$revision.'</a> для <a href="'.engine::CONFIG()['main']['mainAddr'].'/index.php?component=moysklad&page=product&productId='.$product['id'].'">'.$product['article'].'</a>'
                );

                if ($_POST['backUrl'])
                    header('location: ' . $_POST['backUrl']);
                else {
                    echo 'Файл успешно загружен';
                }
                break;
            // вид сбоку
            case 'sideview':
                $typeSideImage = '';
                $fileType = engine::DB()->query('SELECT * FROM `@[prefix]files`, @[prefix]sideView WHERE @[prefix]files.id LIKE @[prefix]sideView.fileId');
                foreach ($fileType as $type) {
                    if ($type['contentType'] == 'image/jpeg') {
                        $typeSideImage = '.jpg';
                    }
                    if ($type['contentType'] == 'image/png') {
                        $typeSideImage = '.png';
                    }
                }
                $lastRev = engine::DB()->getRow('SELECT * FROM @[prefix]sideView WHERE modId = ?i ORDER BY id DESC', $_POST['modId']);
                $revision = 1;
                if ($lastRev) {
                    $lastRevision = (int)$lastRev['revision'];
                    $revision = $lastRevision;
                    $revision++;
                    //if ($revision < 10) $revision = '0' . $revision;
                }
                engine::DB()->query('INSERT INTO @[prefix]sideView (modId, revision, uploadDate, uploadedBy, fileId) VALUES (?i, ?s, NOW(), ?i, ?i)', $_POST['modId'], (int)$revision, engine::USERS()->getCurrentUser()['id'], $fileId);
                engine::DB()->query('UPDATE @[prefix]modifications SET lastupdate=NOW() WHERE id=?i', $_POST['modId']);
                engine::TG()->sendMessage(
                    engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' загрузил вид сбоку <a href="'.engine::CONFIG()['main']['mainAddr'].'/downloadFile.php?fileId='.$fileId.'&watermarkText=R'. $revision .'&mod=sideView&latest='. true .'&created='.$modification['created'].'&article='.urlencode(" Вид сбоку".$typeSideImage).'">'.$modification['name'].'R'.$revision.'</a> для <a href="'.engine::CONFIG()['main']['mainAddr'].'/index.php?component=moysklad&page=product&productId='.$product['id'].'">'.$product['article'].'</a>'
                );

                if ($_POST['backUrl'])
                    header('location: ' . $_POST['backUrl']);
                else {
                    echo 'Файл успешно загружен';
                }
                break;
            // step файл
            case 'stepview':
                $lastRev = engine::DB()->getRow('SELECT * FROM @[prefix]stepView WHERE modId = ?i ORDER BY id DESC', $_POST['modId']);
                $revision = 1;
                if ($lastRev) {
                    $lastRevision = (int)$lastRev['revision'];
                    $revision = $lastRevision;
                    $revision++;
                    //if ($revision < 10) $revision = '0' . $revision;
                }
                engine::DB()->query('INSERT INTO @[prefix]stepView (modId, revision, uploadDate, uploadedBy, fileId) VALUES (?i, ?s, NOW(), ?i, ?i)', $_POST['modId'], (int)$revision, engine::USERS()->getCurrentUser()['id'], $fileId);
                engine::DB()->query('UPDATE @[prefix]modifications SET lastupdate=NOW() WHERE id=?i', $_POST['modId']);
                engine::TG()->sendMessage(
                    engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' загрузил .step файл <a href="'.engine::CONFIG()['main']['mainAddr'].'/downloadFile.php?fileId='.$fileId.'&watermarkText=R'. $revision .'&mod=stepView&latest='. true .'&created='.$modification['created'].'&article='.$product['article'].'.step">'.$modification['name'].'R'.$revision.'</a> для <a href="'.engine::CONFIG()['main']['mainAddr'].'/index.php?component=moysklad&page=product&productId='.$product['id'].'">'.$product['article'].'</a>'
                );

                if ($_POST['backUrl'])
                    header('location: ' . $_POST['backUrl']);
                else {
                    echo 'Файл успешно загружен';
                }
                break;
            default:
                throw new Exception('Произошла ошибка Err #nf6');
                break;
        }


} catch (Exception $ex) {
    echo $ex->getMessage();
}