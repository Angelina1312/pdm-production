<?php
if (!isset($_GET['modId'])) throw new Exception('No modification id');
$modification = engine::DB()->getRow('SELECT * FROM @[prefix]modifications WHERE id=?i', $_GET['modId']);


#todo modification page