<?php
if(!isset($_GET['documentType'])) throw new Exception('No document type');
if(!isset($_GET['step']) || (int) $_GET['step'] < 1) $step = 0; else $step = $_GET['step'];
if(!isset($_GET['modificationId'])) throw new Exception('No modificationId');


switch($_GET['documentType']){
    case 'specification':
        require __DIR__.'/parse/specification.php';
        break;
    case 'package':
        require __DIR__.'/parse/package.php';
        break;
    default:
        throw new Exception('Unknown documentType');
        break;
}