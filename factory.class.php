<?php

class factory
{

    private $engine = null;

    public function __construct(engine $engine)
    {
        require_once 'coefficients.class.php';
        $this->coeffs = new coefficients($engine);
        $this->engine = $engine;
    }


    public function parseSpec($content)
    {
        $a = $content;
        $a = mb_convert_encoding($a, 'UTF-8', 'WINDOWS-1251');

        $lines = explode(PHP_EOL, $a);
        $items = [];
        $names = [];

        $article = null;
        $mod = null;
        $revision = '01';


        foreach ($lines as $ik => $line) {
            if ($ik == 0) continue;
            $eLine = explode('	', $line);
            $item = [
                'article' => null,
                'number' => null,
                'name' => null,
                'materialArticle' => null,
                'quantity' => null,
                'weight' => null,
                'area' => null,
                'coating' => null,
                'layer' => null,
                'site' => null,
                'section' => null,
                'length' => null,
                'description' => null,
                'pathName' => null,
            ];
            if (count($eLine) < 5) continue;
            foreach ($eLine as $k => $el) {
                $el = trim($el);
                switch ($k) {
                    case 0:
                        $item['article'] = $el;
                        if ($article === null && $el) {
                            if (strpos($el, ' ') > 0) {
                                if (strpos($el, 'R') > 0) {
                                    $explodedArticle = explode(' ', $el, 2);
                                    $article = $explodedArticle[1];
                                    $z = explode('R', $explodedArticle[0], 2);
                                    $mod = $z[0];
                                    $revision = $z[1];
                                } else {
                                    $explodedArticle = explode(' ', $el, 2);
                                    $article = $explodedArticle[1];
                                    $mod = $explodedArticle[0];
                                }

                            } else {
                                $article = $el;
                                $mod = engine::CONFIG()['moysklad']['basicModification'];
                            }
                        }
                        break;
                    case 1:
                        $item['number'] = $el;
                        break;
                    case 2:
                        $item['name'] = $el;
                        break;
                    case 3:
                        $item['materialArticle'] = trim($el);
                        if (!empty($el)) $names[] = trim($el);

                        break;
                    case 4:
                        $item['quantity'] = $el;
                        break;
                    case 5:
                        $item['weight'] = $el;
                        break;
                    case 6:
                        $item['area'] = $el;
                        break;
                    case 7:
                        $item['coating'] = $el;
                        break;
                    case 8:
                        $item['layer'] = $el;
                        break;
                    case 9:
                        $item['site'] = $el;
                        break;
                    case 10:
                        $item['section'] = trim($el);
                        break;
                    case 11:
                        $item['length'] = $el;
                        break;
                    case 12:
                        $item['description'] = $el;
                        break;
                    default:
                        throw new Exception('Ошибка');
                }
            }
            if (count($item) > 2) $items[] = $item;

        }

        if ($mod) {
            if (strpos($mod, 'R') > 0) {
                $s = explode($mod, 'R', 2);
                $mod = $s[0];
                $revision = $s[1];
            }
        }


        $names = array_unique($names);
        $materialNamesFromDB = engine::DB()->getAll('SELECT * FROM lgf_products WHERE article IN (?a)', $names);
        $materials = [];
        foreach ($materialNamesFromDB as $m) {
            $d = json_decode($m['data'], true);
            $materials[$d['article']] = $d;
        }

        $maxLevel = 0;

        $hierarchy = [];
        foreach ($items as $k => $item) {
            $exp = explode('.', $item['number']);
            if (count($exp) > 1) {
                $level = count($exp);
                unset($exp[count($exp) - 1]);
                $parent = implode('.', $exp);
            } else {
                $parent = false;
                $level = 1;
            }
            $item['pathName'] = @$materials[$item['materialArticle']]['pathName'];
            $item['parent'] = $parent;
            $item['level'] = $level;
            if ($maxLevel < $level) $maxLevel = $level;
            $hierarchy[$item['number']] = $item;
        }
        foreach ($hierarchy as $k => $item) {
            if ($item['parent'] && array_key_exists($item['parent'], $hierarchy)) {
                if (!isset($hierarchy[$item['parent']]['child'])) $hierarchy[$item['parent']]['child'] = [];
                $hierarchy[$item['parent']]['child'][] = $k;
            }
        }


        $sum = [];
        //engine::debug_printArray($hierarchy);
        //exit;
        //$hierarchyForSum = $hierarchy;
        //usort($hierarchyForSum, function ($item1, $item2) {
        //    return $item1['level'] < $item2['level'];
        //});

        function getQuantity($hierarchy, $item)
        {
            $quantity = $item['quantity'];
            $parent = $item['parent'];
            while ($parent != false) {
                $quantity = @$hierarchy[$parent]['quantity'] * $quantity;
                $parent = @$hierarchy[$parent]['parent'];
            }
            return $quantity;
        }

        $counterByGroups = [];
        foreach ($hierarchy as $item) {
            //if()
            $parent = null;
            if (@$item['section'] != 'Д') continue;
            if (!$item['materialArticle']) continue;
            if (!array_key_exists($item['materialArticle'], $sum))
                $sum[$item['materialArticle']] = ['area' => 0, 'weight' => 0, 'length' => 0, 'quantity' => 0, 'paintArea' => 0, 'coating' => false];

            //if($item['materialArticle'] == 'Труба 40x20x2') {
            //    echo '<br>now: ' . $sum[$item['materialArticle']]['quantity'];
            //    echo '<br>plus: ' .(($item['quantity'] ? $item['quantity'] : 1) * ($parent['quantity'] ? $parent['quantity'] : 1));;
            //}

            //if($item['parent'] == false) break;
            $parent = @$hierarchy[$item['parent']];
            $q = getQuantity($hierarchy, $item);
            $sum[$item['materialArticle']]['area'] += floatval($item['area']) * $q * 0.000001;
            $sum[$item['materialArticle']]['weight'] += floatval($item['weight']) * $q / 1000;
            $sum[$item['materialArticle']]['length'] += floatval($item['length']) * $q / 1000;
            $sum[$item['materialArticle']]['quantity'] += $q;
            $sum[$item['materialArticle']]['paintArea'] += floatval($item['area']) * $q * (floatval($item['layer']) > 0 ? floatval($item['layer']) : 1) * 0.000001;


            //$sum[$item['materialArticle']]['area'] += (floatval($item['area']) * ($item['quantity'] ? $item['quantity'] : 1)) * ($parent['quantity'] ? $parent['quantity'] : 1) * 0.000001;
            //$sum[$item['materialArticle']]['weight'] += (floatval($item['weight']) * ($item['quantity'] ? $item['quantity'] : 1)) * ($parent['quantity'] ? $parent['quantity'] : 1) / 1000;
            //$sum[$item['materialArticle']]['length'] += (floatval($item['length']) * ($item['quantity'] ? $item['quantity'] : 1)) * ($parent['quantity'] ? $parent['quantity'] : 1) / 1000;
            //$sum[$item['materialArticle']]['quantity'] += (($item['quantity'] ? $item['quantity'] : 1) * ($parent['quantity'] ? $parent['quantity'] : 1));
            //$sum[$item['materialArticle']]['paintArea'] += (floatval($item['area']) * ($item['quantity'] ? $item['quantity'] : 1)) * ($parent['quantity'] ? $parent['quantity'] : 1) * (floatval($item['layer']) > 0 ? floatval($item['layer']) : 1) * 0.000001;
            if ($item['coating']) $sum[$item['materialArticle']]['coating'] = ($item['coating']) ? $item['coating'] : false; //else
            if ($parent) if (@$parent['coating']) $sum[$item['materialArticle']]['coating'] = ($parent['coating']) ? $parent['coating'] : false;

            //if($item['materialArticle'] == 'Труба 40x20x2') {
            //    echo '<br>=  : ' . $sum[$item['materialArticle']]['quantity'];
            //    echo ' ('.$item['level'].')';
            //}

        }


        function getArea($items, $index)
        {
            $sum = 0;
            //if(isset($items[$index]['child']) && is_array($items[$index]['child']) && count($items[$index]['child']) > 0){
            //    foreach($items[$index]['child'] as $child){
            //        $sum += getArea($items, $child);
            //    }
            //}

            if ($items[$index]['area']) $sum += $items[$index]['area'];

            $sum = $sum * $items[$index]['quantity'];
            if ($items[$index]['layer']) $sum = $sum * $items[$index]['layer'];

            return $sum;
        }

        $paint = [];
        foreach ($hierarchy as $k => $item) {
            if (!@$item['coating']) continue;
            if (!isset($paint[$item['site']]))
                $paint[$item['site']] = [];
            if (!isset($paint[$item['site']][$item['coating']]))
                $paint[$item['site']][$item['coating']] = ['area' => 0];
            $area = getArea($hierarchy, $k) * 0.000001;
            if ($item['parent']) $area = $hierarchy[$item['parent']]['quantity'] * $area;
            $paint[$item['site']][$item['coating']]['area'] += $area;
        }


        $tgfd = [];
        foreach ($paint as $site) {
            foreach ($site as $k => $v) {
                $tgfd[] = $k;
            }
        }
        $tgfd = array_unique($tgfd);

        $mspaints = [];
        if (count($tgfd) > 0) {
            $paintsFromDB = engine::DB()->getAll('SELECT * FROM @[prefix]products WHERE article IN (?a)', $tgfd);
            foreach ($paintsFromDB as $p) {
                $d = json_decode($p['data'], true);
                $mspaints[$d['article']] = $d;
            }
        }
        $return = ['article' => $article, 'mod' => $mod, 'revision' => $revision, 'hierarchy' => $hierarchy, 'paint' => $paint, 'mspaints' => $mspaints, 'materials' => $materials, 'sum' => $sum];

        $errors = $this->checkErrors($return);
        $return['errors'] = $errors;


        return $return;
    }

    public function createModification($productId, $modificationName, $executorId, $comment, $draft, $pp = false)
    {
        $m = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE productId = ?s AND LOWER(`name`) = ?s', $productId, $modificationName);
        if ($m) return ['status' => false, 'reason' => 'Модификация уже существует'];
        $width = 0;
        $height = 0;
        $length = 0;
        $dropHeight = '';
        $age = '';
        engine::DB()->query('INSERT INTO @[prefix]modifications (productId, `name`, created, lastupdate, createdBy, createdFromPP, ppid, draft, comment, width, height, `length`, drop_height, age) VALUES (?s, ?s, NOW(), NOW(), ?i, ?i, ?s, ?s, ?s, ?i, ?i, ?i, ?s, ?s)', $productId, $modificationName, $executorId, ((!$pp) ? 0 : 1), (($pp) ? $pp : ''), $draft, $comment, $width, $height, $length, $dropHeight, $age);
        $insertId = engine::DB()->insertId();
        $product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE id = ?s', $productId);
        engine::TG()->sendMessage(
            engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' создал модификацию <b>' . $modificationName . '</b> для <a href="' . engine::CONFIG()['main']['mainAddr'] . '/index.php?component=moysklad&page=product&productId=' . $productId . '">' . $product['article'] . '</a>'
        );
            $modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE `name` = ?s', $modificationName);
        // записываем в логи
        $param = 'modification';
        engine::DB()->query('INSERT INTO @[prefix]logs (`id`, `entity`, `productId`, `modificationId`, `data`, `userId`, `date`) VALUES (?s, ?s, ?s, ?s, ?s, ?s, NOW()) ON DUPLICATE KEY UPDATE `entity` = ?s, `productId` = ?s, `modificationId` = ?s, `data` = ?s, `userId` = ?s, `date` = NOW()', $param, 'модификация', $productId, $modification['id'], 'создал модификацию', engine::USERS()->getCurrentUser()['id'], 'модификация', $productId, $modification['id'], 'создал модификацию', engine::USERS()->getCurrentUser()['id']);

        return ['status' => true, 'id' => $insertId];
    }

    // убираем галочку черновика
    public function draftMod($productId, $checked, $message, $id, $modificationId)
    {
        engine::DB()->query('UPDATE @[prefix]modifications SET draft = ?s, lastupdate = NOW() WHERE id = ?i', $checked, $modificationId);
        $insertId = engine::DB()->insertId();
        $product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE id = ?s', $productId);
        $modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id = ?s', $modificationId);
        engine::TG()->sendMessage(
            engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' ' . $message . ' <b>' . $modification['name'] . '</b> для <a href="' . engine::CONFIG()['main']['mainAddr'] . '/index.php?component=moysklad&page=product&productId=' . $productId . '">' . $product['article'] . '</a>'
        );
        // записываем в логи
        $param = 'draftMod';
        engine::DB()->query('INSERT INTO @[prefix]logs (`id`, `entity`, `productId`, `modificationId`, `data`, `userId`, `date`) VALUES (?s, ?s, ?s, ?s, ?s, ?s, NOW()) ON DUPLICATE KEY UPDATE `entity` = ?s, `productId` = ?s, `modificationId` = ?s, `data` = ?s, `userId` = ?s, `date` = NOW()', $param, 'черновик', $productId, $modificationId, $message, engine::USERS()->getCurrentUser()['id'], 'черновик', $productId, $modificationId, $message, engine::USERS()->getCurrentUser()['id']);
        return ['status' => true, 'id' => $insertId];
    }

    // редактируем комментарий в модификации
    public function editComment($productId, $modificationName, $comment)
    {
        engine::DB()->query('UPDATE @[prefix]modifications SET comment = ?s, lastupdate = NOW() WHERE `productId` = ?s', $comment, $productId);
        $insertId = engine::DB()->insertId();
        $modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE name = ?s', $modificationName);
        $product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE id = ?s', $productId);
        engine::TG()->sendMessage(
            engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' обновил комментарий  <b>' . $modificationName . '</b> для <a href="' . engine::CONFIG()['main']['mainAddr'] . '/index.php?component=moysklad&page=product&productId=' . $productId . '">' . $product['article'] . '</a>'
        );
        // записываем в логи
        $param = 'comment';
        engine::DB()->query('INSERT INTO @[prefix]logs (`id`, `entity`, `productId`, `modificationId`, `data`, `userId`, `date`) VALUES (?s, ?s, ?s, ?s, ?s, ?s, NOW()) ON DUPLICATE KEY UPDATE `entity` = ?s, `productId` = ?s, `modificationId` = ?s, `data` = ?s, `userId` = ?s, `date` = NOW()', $param, 'комментарий', $productId, $modification['id'], 'обновил комментарий', engine::USERS()->getCurrentUser()['id'], 'комментарий', $productId, $modification['id'], 'обновил комментарий', engine::USERS()->getCurrentUser()['id']);
        return ['status' => true, 'id' => $insertId];
    }

    // добавляем размеры у модификации
    public function sendModParams($width, $height, $length, $dropHeight, $age, $modificationId, $productId)
    {
        engine::DB()->query('UPDATE @[prefix]modifications SET width = ?i, height = ?i, `length` = ?i, drop_height = ?s, age = ?s, lastupdate = NOW() WHERE `id` = ?s', $width, $height, $length, $dropHeight, $age, $modificationId);
        $insertId = engine::DB()->insertId();
        $product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE id = ?s', $productId);
        $modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id = ?s', $modificationId);
        // записываем в логи
        $param = 'size';
        engine::DB()->query('INSERT INTO @[prefix]logs (`id`, `entity`, `productId`, `modificationId`, `data`, `userId`, `date`) VALUES (?s, ?s, ?s, ?s, ?s, ?s, NOW()) ON DUPLICATE KEY UPDATE `entity` = ?s, `productId` = ?s, `modificationId` = ?s, `data` = ?s, `userId` = ?s, `date` = NOW()', $param, 'размеры', $productId, $modificationId, 'добавил размеры', engine::USERS()->getCurrentUser()['id'], 'размеры', $productId, $modificationId, 'добавил размеры', engine::USERS()->getCurrentUser()['id']);

        engine::TG()->sendMessage(
            engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' добавил размеры  <b>' . $modification['name'] . '</b> для <a href="' . engine::CONFIG()['main']['mainAddr'] . '/index.php?component=moysklad&page=product&productId=' . $productId . '">' . $product['article'] . '</a>'
        );

        // обновляем размеры в мс
        $productMS = engine::moySklad()->query('https://online.moysklad.ru/api/remap/1.2/entity/product/' . $productId);
        $ageAttributes = engine::moySklad()->query('https://online.moysklad.ru/api/remap/1.2/entity/customentity/349819b5-f8de-11ea-0a80-0185000a5fdd');

        foreach ($ageAttributes['rows'] as $rowAttr) {
            foreach ($productMS['attributes'] as $k => $attr) {
                if ($attr['name'] == 'Ширина') {
                    $productMS['attributes'][$k]['value'] = intval($width);
                }
                if ($attr['name'] == 'Длина') {
                    //  engine::debug_printArray($attr['value']);
                    $productMS['attributes'][$k]['value'] = intval($length);
                }
                if ($attr['name'] == 'Высота') {
                    //  engine::debug_printArray($attr['value']);
                    $productMS['attributes'][$k]['value'] = intval($height);
                }
                if ($attr['name'] == 'Высота падения') {
                    //  engine::debug_printArray($attr['value']);
                    $productMS['attributes'][$k]['value'] = intval($dropHeight);
                }
                if ($attr['name'] == 'Ограничения по возрастной группе') {
                    // engine::debug_printArray($attr['value']['name']);
                    $productMS['attributes'][$k]['value']['name'] = strval($age);
                    // передавать id аттрибута и менять ссылку
                    // engine::debug_printArray($productMS['attributes'][$k]['value']['meta']['href']);
                    if ($rowAttr['name'] == $productMS['attributes'][$k]['value']['name']) {
                        $productMS['attributes'][$k]['value']['meta']['href'] = $rowAttr['meta']['href'];
                        $productMS['attributes'][$k]['id'] = $rowAttr['id'];
                    }
                }
            }
        }
        engine::moySklad()->sendJson('https://online.moysklad.ru/api/remap/1.2/entity/product/' . $productId, $productMS, 'PUT');
        //  engine::debug_printArray(engine::moySklad()->sendJson('https://online.moysklad.ru/api/remap/1.2/entity/product/'.$productId, $productMS, 'PUT'));

        return ['status' => true, 'id' => $insertId];
    }

    // обновляем цену в мс
    public function updateBuyPrice($price, $productId, $modificationId)
    {
        engine::DB()->query('UPDATE @[prefix]products SET buyprice = ?i, timeOfLastUpdate = NOW() WHERE `id` = ?s', $price, $productId);
        $insertId = engine::DB()->insertId();
        $product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE id = ?s', $productId);
        $modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id = ?s', $modificationId);
        engine::TG()->sendMessage(
            engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' обновил закупочную цену  <b>' . $modification['name'] . '</b> для <a href="' . engine::CONFIG()['main']['mainAddr'] . '/index.php?component=moysklad&page=product&productId=' . $productId . '">' . $product['article'] . '</a>'
        );

        // записываем в логи
        $param = 'price';
        engine::DB()->query('INSERT INTO @[prefix]logs (`id`, `entity`, `productId`, `modificationId`, `data`, `userId`, `date`) VALUES (?s, ?s, ?s, ?s, ?s, ?s, NOW()) ON DUPLICATE KEY UPDATE `entity` = ?s, `productId` = ?s, `modificationId` = ?s, `data` = ?s, `userId` = ?s, `date` = NOW()', $param, 'цена', $productId, $modificationId, 'обновил цену', engine::USERS()->getCurrentUser()['id'], 'цена', $productId, $modificationId, 'обновил цену', engine::USERS()->getCurrentUser()['id']);

        // обновляем цену в мс
        $productMS = engine::moySklad()->query('https://online.moysklad.ru/api/remap/1.2/entity/product/' . $productId);
        $productMS['buyPrice']['value'] = $price;
        engine::moySklad()->sendJson('https://online.moysklad.ru/api/remap/1.2/entity/product/' . $productId, $productMS, 'PUT');
        //  engine::debug_printArray(engine::moySklad()->sendJson('https://online.moysklad.ru/api/remap/1.2/entity/product/'.$productId, $productMS, 'PUT'));
        return ['status' => true, 'id' => $insertId];
    }

    // отправка в тг каждые 5 мин
    public function timeInterval($productId)
    {
        $outMessage = ' ';
        $messages = engine::DB()->getAll('SELECT * FROM @[prefix]logs WHERE `date` >= DATE_SUB(NOW() , INTERVAL 5 MINUTE)');
        $modId = '';
        foreach ($messages as $message) {
            $outMessage .= "\n";
            $outMessage .= ' - ' . $message['data'];
            $modId = $message['modificationId'];
        }
        $modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id = ?s', $modId);
        $product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE id = ?s', $productId);

        if (strlen($outMessage) > 1) {
            engine::TG()->sendMessageSize(
                engine::format_user(engine::USERS()->getCurrentUser()['id']) . "\r внёс изменения в модификацию: " . $outMessage . "\n" .  ' Для модификации [' . $modification['name'] . '] <a href="' . engine::CONFIG()['main']['mainAddr'] . '/index.php?component=moysklad&page=product&productId=' . $productId . '">' . $product['article'] . ' ' . $product['name'] . '</a>'
            );
        }
        $insertId = engine::DB()->insertId();
        return ['status' => true, 'id' => $insertId];
    }


    public function getModificationsByProductId($productId)
    {
        $modIds = engine::DB()->getCol('SELECT id FROM @[prefix]modifications WHERE productId=?s ORDER BY created DESC', $productId);
        if (!$modIds) return false;
        $mods = [];
        foreach ($modIds as $modId) {
            $mods[] = $this->getModificationData($modId);
        }
        return $mods;
    }

    public function getModificationData($modificationId)
    {

        $mod = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id=?i', $modificationId);
        if (!$mod) return false;
        $mod['isNull'] = false;

        $specifications = engine::DB()->getAll('SELECT * FROM @[prefix]specification WHERE modificationId = ?i ORDER BY id DESC', $modificationId);

        $specs = array_column($specifications, 'id');
        $allCalcs = engine::DB()->getAll('SELECT id, specificationId, calculationDate, createdBy, costPrice, purchasePrice FROM @[prefix]calculations WHERE specificationId IN(?a) ORDER BY `id` DESC', $specs);
        $allPps = engine::DB()->getAll('SELECT * FROM `@[prefix]moysklad_processingPlans` WHERE specificationId IN(?a) ORDER BY `id` ASC', $specs);

        $indexToSpec = [];
        foreach ($specifications as $sk => $v) {
            $specifications[$sk]['calculations'] = [];
            $specifications[$sk]['processingPlan'] = false;
            $indexToSpec[$v['id']] = $sk;
        }

        foreach ($allCalcs as $calc) {
            $specifications[$indexToSpec[$calc['specificationId']]]['calculations'][] = $calc;
        }
        foreach ($allPps as $pps) {
            $specifications[$indexToSpec[$pps['specificationId']]]['processingPlan'] = $pps;
        }

        $mod['specifications'] = ($specifications) ? $specifications : [];

        $packs = engine::DB()->getAll('SELECT * FROM @[prefix]pack WHERE modificationId = ?i ORDER BY id DESC', $mod['id']);
        $mod['packs'] = ($packs) ? $packs : [];

        $assemblingSchemes = engine::DB()->getAll('SELECT * FROM @[prefix]assemblingSchemes WHERE modId = ?i ORDER BY id DESC', $mod['id']);
        $mod['assemblingSchemes'] = ($assemblingSchemes) ? $assemblingSchemes : [];

        $packageSchemes = engine::DB()->getAll('SELECT * FROM @[prefix]packageSchemes WHERE modId = ?i ORDER BY id DESC', $mod['id']);
        $mod['packageSchemes'] = ($packageSchemes) ? $packageSchemes : [];

        $schemes = engine::DB()->getAll('SELECT * FROM @[prefix]schemes WHERE modId = ?i ORDER BY id DESC', $mod['id']);
        $mod['schemes'] = ($schemes) ? $schemes : [];

        $dwgSchemes = engine::DB()->getAll('SELECT * FROM @[prefix]dwgSchemes WHERE modId = ?i ORDER BY id DESC', $mod['id']);
        $mod['dwgSchemes'] = ($dwgSchemes) ? $dwgSchemes : [];

        // вид сверху
        $topView = engine::DB()->getAll('SELECT * FROM @[prefix]topView WHERE modId = ?i ORDER BY id DESC', $mod['id']);
        $mod['topView'] = ($topView) ? $topView : [];
        // вид сбоку
        $sideView = engine::DB()->getAll('SELECT * FROM @[prefix]sideView WHERE modId = ?i ORDER BY id DESC', $mod['id']);
        $mod['sideView'] = ($sideView) ? $sideView : [];
        // step файл
        $stepView = engine::DB()->getAll('SELECT * FROM @[prefix]stepView WHERE modId = ?i ORDER BY id DESC', $mod['id']);
        $mod['stepView'] = ($stepView) ? $stepView : [];


        if (count($specifications) == 0 && count($schemes) == 0 && count($packs) == 0 && count($assemblingSchemes) == 0 && count($packageSchemes) == 0) $mod['isNull'] = true;

        return $mod;
    }

    public function removeModification($modId)
    {
        $mod = $this->getModificationData($modId);
        if (!$mod) return false;
        if ($mod['isNull'] !== true) return false;
        $q = engine::DB()->query('DELETE FROM @[prefix]modifications WHERE id=?i', $modId);
        return true;
    }


    public function getCalculationsListBySpecification($specId)
    {
        $calculations = engine::DB()->getAll('SELECT * FROM @[prefix]calculations WHERE specificationId = ?i ORDER BY id DESC', $specId);
        return $calculations;
    }

    public function createPackageScheme($fileId, $modId)
    {

    }

    public function getSpecification($specId)
    {
        $specification = engine::DB()->getRow('SELECT * FROM @[prefix]specification WHERE id = ?i', $specId);
        if (!$specification) return false;
        $coatings = engine::DB()->getAll('SELECT * FROM @[prefix]specification_coatings WHERE specificationId=?i', $specId);
        $materials = engine::DB()->getAll('SELECT * FROM @[prefix]specification_materials WHERE specificationId=?i', $specId);
        $source = engine::DB()->getAll('SELECT * FROM @[prefix]specification_source WHERE specificationId=?i', $specId);

        $specification['data'] = [
            'coatings' => $coatings,
            'materials' => $materials,
            'source' => $source
        ];

        return $specification;
    }

    public function archiveProcessingPlans($processingPlans, $undo = false)
    {
        $arrayToMs = [];
        foreach ($processingPlans as $ppid) {
            #$pp = engine::DB()->getRow('SELECT * FROM @[prefix]pp WHERE id=?s', $ppid);
            #if($pp){
            #$ppData = json_decode($pp['data'], true);

            $arrayToMs[] = [
                'meta' => [
                    'href' => 'https://online.moysklad.ru/api/remap/1.2/entity/processingplan/' . $ppid,
                    'metadataHref' => 'https://online.moysklad.ru/api/remap/1.2/entity/processingplan/metadata',
                    'mediaType' => 'application/json',
                    'type' => 'processingplan',
                    'uuidHref' => 'https://online.moysklad.ru/app/#processingplan/edit?id=' . $ppid
                ],
                'archived' => !$undo,
                #'name' => $ppData['name'].' (АРХИВ)'
            ];
            #}

        }
        $response = engine::moySklad()->sendJson('https://online.moysklad.ru/api/remap/1.2/entity/processingplan?expand=group,materials,products,materials.product,products.product,materials.product.uom,materials.product.group', $arrayToMs);

        foreach ($response as $row) {
            $productId = $row['products']['rows'][0]['product']['id'];
            if (!$productId) continue;
            $archived = (int)$row['archived'];
            $name = $row['name'];
            $data = json_encode($row);
            $id = $row['id'];

            engine::DB()->query('INSERT INTO @[prefix]pp (id, `name`, productId, archived, `data`, `lastupdate`) VALUES (?s, ?s, ?s, ?i, ?s, NOW()) ON DUPLICATE KEY UPDATE `name`=?s, productId=?s, archived=?i, `data`=?s, lastupdate= NOW()',
                $id, $name, $productId, $archived, $data, $name, $productId, $archived, $data
            );
        }

        if ($response) engine::DB()->query('UPDATE @[prefix]moysklad_processingPlans SET archived=?i WHERE processingPlanId_moySklad IN(?a)', ($undo ? 0 : 1), $processingPlans);
        else throw new Exception('Произошла ошибка при архивировании тех. карты, попробуйте еще раз.');
        #engine::debug_printArray($response);
        return true;

    }

    public function getProcessingPlansByProductId($productId)
    {
        $processingPlans = engine::DB()->getAll('
                SELECT @[prefix]pp.*, @[prefix]moysklad_processingPlans.specificationId as specificationId, @[prefix]specification.revision as revision, @[prefix]modifications.name as modificationName
                FROM @[prefix]pp 
                LEFT JOIN @[prefix]moysklad_processingPlans ON @[prefix]moysklad_processingPlans.processingPlanId_moySklad = @[prefix]pp.id
                LEFT JOIN @[prefix]specification ON @[prefix]specification.id = @[prefix]moysklad_processingPlans.specificationId OR @[prefix]specification.ppid = @[prefix]pp.id
                LEFT JOIN @[prefix]modifications ON @[prefix]modifications.id = @[prefix]specification.modificationId
                WHERE @[prefix]pp.productId = ?s
                ORDER BY @[prefix]modifications.name, @[prefix]specification.revision DESC', $productId);

        return $processingPlans;

    }

    public function getProcessingOrdersByProductId($productId)
    {
        $processingOrders = engine::DB()->getAll('
            SELECT @[prefix]moysklad_processingOrders.*, @[prefix]moysklad_processingPlans.specificationId as specificationId, @[prefix]specification.revision as revision, @[prefix]specification.id as specificationId, @[prefix]modifications.name as modificationName, @[prefix]modifications.id as modificationId
            FROM @[prefix]moysklad_processingOrders
            LEFT JOIN @[prefix]moysklad_processingPlans ON @[prefix]moysklad_processingPlans.processingPlanId_moySklad = @[prefix]moysklad_processingOrders.processingPlan
            LEFT JOIN @[prefix]specification ON @[prefix]specification.id = @[prefix]moysklad_processingPlans.specificationId OR @[prefix]specification.ppid = @[prefix]moysklad_processingOrders.processingPlan
            LEFT JOIN @[prefix]modifications ON @[prefix]modifications.id = @[prefix]specification.modificationId
            WHERE ready < quantity AND applicable=1 AND @[prefix]moysklad_processingOrders.productId = ?s
            ORDER BY moment ASC
            ', $productId);
        return $processingOrders;
    }

    public function createProcessingPlan($specId, $calculation)
    {

        //МодRXX артикул название
        //Если архив, то + (архив)
        $specification = engine::DB()->getRow('SELECT * FROM @[prefix]specification WHERE id=?i', $specId);
        $modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id=?i', $specification['modificationId']);
        $product = engine::DB()->getRow('SELECT * FROM @[prefix]products WHERE id=?s', $modification['productId']);
        $product['data'] = json_decode($product['data'], true);
        #$archive = (bool) engine::DB()->getOne('SELECT COUNT(id) FROM @[prefix]specification WHERE modificationId=?i AND id > ?i', $modification['id'], $specification['id']);
        $archive = false;
        $name = $modification['name'] . 'R' . $specification['revision'] . ' ' . $product['data']['article'] . ' ' . $product['data']['name'] . ($archive ? ' (архив)' : '') . ' (PDM)';

        $c = engine::DB()->getOne('SELECT COUNT(id) FROM @[prefix]moysklad_processingPlans WHERE specificationId = ?i', $specification['id']);

        if ($c != 0) throw new Exception('Можно создать только одну тех. карту');

        $materialsToMoySklad = [];

        foreach ($calculation['materialsLittleArray'] as $k => $material) {
            $materialsToMoySklad[] = [
                'accountId' => $calculation['materialsFromDB'][$k]['accountId'],
                'product' => [
                    'meta' => $calculation['materialsFromDB'][$k]['meta']
                ],
                'quantity' => $material[$material['calculateField']],
            ];
        }

        foreach ($calculation['coatingsLittleArray'] as $k => $coating) {
            $materialsToMoySklad[] = [
                'accountId' => $calculation['coatingsFromDB'][$k]['accountId'],
                'product' => [
                    'meta' => $calculation['coatingsFromDB'][$k]['meta']
                ],
                'quantity' => $coating['expenseinms'],
            ];
        }

        $arrayToMs = [
            'name' => $name,
            'cost' => (int)($calculation['coeffSums']['zp'] * 100),
            'materials' => $materialsToMoySklad,
            'products' => [
                [
                    'product' => [
                        'meta' => $calculation['product']['data']['meta']
                    ],
                    'quantity' => 1
                ],
            ],
            'parent' => ['meta' => engine::CONFIG()['moysklad']['processingPlansPathName']],
        ];


        $response = engine::moySklad()->sendJson('https://online.moysklad.ru/api/remap/1.2/entity/processingplan', $arrayToMs);
        //engine::debug_printArray($response);
        if (!@$response['id']) throw new Exception((@$response['errors'][0]['error'] ? $response['errors'][0]['error'] : 'Ошибка'));
        $anotherSpecs = engine::DB()->getCol('SELECT id FROM @[prefix]specification WHERE modificationId = ?i', $modification['id']);
        $anotherPPs = engine::DB()->getCol('SELECT processingPlanId_moySklad FROM @[prefix]moysklad_processingPlans WHERE specificationId IN(?a)', $anotherSpecs);
        if (count($anotherPPs) > 0) engine::FACTORY()->archiveProcessingPlans($anotherPPs);
        engine::DB()->query('INSERT INTO @[prefix]moysklad_processingPlans (creationDate, createdBy, specificationId, processingPlanId_moySklad, uuidHref) VALUES (NOW(), ?i, ?i, ?s, ?s)', engine::USERS()->getCurrentUser()['id'], $specification['id'], $response['id'], $response['meta']['uuidHref']);
        engine::TG()->sendMessage(
            engine::format_user(engine::USERS()->getCurrentUser()['id']) . ' создал <a href="' . $response['meta']['uuidHref'] . '">техкарту</a> для <a href="#">' . $name . '</a>'
        );
        return true;
        //$updateRequest = [
        //    'pathName' => engine::CONFIG()['moysklad']['processingPlansPathName'],
        //];
        //$response = engine::moySklad()->sendJson('https://online.moysklad.ru/api/remap/1.2/entity/processingplan/'.$response['id'], $updateRequest, 'PUT');
        //return $response;
    }

    public function checkErrors($parsed)
    {

        $errors = [
            '%common%' => []
        ];
        $checkFasteners = false;

        foreach ($parsed['hierarchy'] as $item) {
            if (@$item['pathName'] == 'ПРОИЗВОДСТВО/Крепеж') $checkFasteners = true;

            if (@$item['pathName'] == 'ПРОИЗВОДСТВО/Новые материалы и комплектующие') $errors[$item['number']][] = [
                'type' => 'warning',
                'message' => 'Материал из группы "Новые материалы и комплектующие"'
            ];

            if (mb_strtolower(@$item['section']) == 'д' && (empty(@$item['materialArticle']) || !$item['materialArticle'])) $errors[$item['number']][] = [
                'type' => 'error',
                'message' => 'Нет артикула',
            ];

            if (!empty(@$item['materialArticle']) && !array_key_exists(@$item['materialArticle'], $parsed['materials'])) $errors[$item['number']][] = [
                'type' => 'error',
                'message' => 'Артикул !материала! не найден в МС',
            ];

            if (!empty(@$item['coating']) && !array_key_exists(@$item['coating'], $parsed['mspaints'])) $errors[$item['number']][] = [
                'type' => 'error',
                'subtype' => 'paint',
                'message' => 'Артикул !покрытия! не найден в МС',
            ];

            if (@$item['materialArticle'] && array_key_exists(@$item['materialArticle'], $parsed['materials'])) {
                #engine::debug_printArray();
                #exit;
                if ($parsed['materials'][$item['materialArticle']]['archived'] == true) $errors[$item['number']][] = [
                    'type' => 'error',
                    'message' => 'Материал в архиве',
                ];
                $calculateField = helper::uomToField($parsed['materials'][$item['materialArticle']]['uom']['name']);
                if ($item[$calculateField] <= 0) $errors[$item['number']][] = [
                    'type' => 'error',
                    'message' => 'Значение поля "' . mb_strtolower(helper::uomFieldToRussian($calculateField)) . '" меньше или равно нулю, это поле является единицей измерения для товара',
                ];
            }


            //'article'
            //'number'
            //'name'
            //'materialArticle'
            //'quantity'
            //'weight'
            //'area'
            //'coating'
            //'layer'
            //'site'
            //'section'
            //'length'
            //'description'
            //'pathName'
            //           if($item['name'] && (!$item['weight'] || !$item['site'] || !$item['section'])) $errors[$item['number']][] = [

            if (mb_strtolower(@$item['section']) == 'д' && @$item['materialArticle'] && (!$item['weight'] || !$item['site'])) $errors[$item['number']][] = [
                'type' => 'error',
                'message' => 'Раздел Д и есть артикул, но нет массы или участка',
            ];


            if (mb_strtolower(@$item['section']) !== 'д' && mb_strtolower(@$item['section']) !== 'сб') $errors[$item['number']][] = [
                'type' => 'error',
                'message' => 'Раздел должен быть "Д", либо "СБ"',
            ];

            // проверка на отсутствие вложенности в разделе "Д"
            if (mb_strtolower(@$parsed['hierarchy'][@$item['parent']]['section']) == 'д') $errors[$item['number']][] = [
                'type' => 'error',
                'message' => 'Не может быть вложенности в разделе "Д"',
            ];


        }


        if ($checkFasteners === false) $errors['%common%'][] = [
            'type' => 'warning',
            'message' => 'В спецификации отсутствует крепеж'
        ];

        if (empty(@$parsed['paint'])) $errors['%common%'][] = [
            'type' => 'warning',
            'message' => 'В спецификации отсутствуют покрытия'
        ];

        return $errors;
    }


    /**
     * @return coefficients
     */
    public function COEFFS()
    {
        return $this->coeffs;
    }


}
