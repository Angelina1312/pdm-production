<?php


if($_POST['action'] == 'new')   $msg = engine::FACTORY()->COEFFS()->addNewCoeff(@$_POST['pathName'], @$_POST['coeffs']); else
if($_POST['action'] == 'edit')  $msg = engine::FACTORY()->COEFFS()->updateCoeff(@$_POST['id'], @$_POST['pathName'], @$_POST['coeffs']);

exit(json_encode($msg));