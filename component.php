<?php
$content = '';
$operationType = 'page';
if (isset($ajax) && in_array(@$_GET['do'], ['getProducts', 'createModification', 'uploadFile', 'createSpecification', 'deleteModification', 'createCalculation', 'coeff', 'createPP', 'createModificationFromPP', 'archivePP', 'recreateOrder', 'deleteDraft', 'editComment', 'sendModParams', 'updateBuyPrice', 'timeInterval'])) $operationType = 'ajax';

if ($operationType == 'ajax') {
    if (file_exists(__DIR__ . '/ajax/' . $_GET['do'] . '.php') && !is_dir(__DIR__ . '/ajax/' . $_GET['do'] . '.php')) {
        //header('Content-type: application/json');
        require __DIR__ . '/ajax/' . $_GET['do'] . '.php';
    } else exit(json_encode([
        'status' => 'false',
        'message' => 'Action not found',
    ]));
    exit;
}


$template = 'main.tpl';


switch (strtolower($page)) {
    case 'specifications':
        ob_start();
        require __DIR__ . '/pages/specifications.php';
        $content = ob_get_clean();
        break;
    case 'specification':
        ob_start();
        require __DIR__ . '/pages/specification.php';
        $content = ob_get_clean();
        break;
    case 'uploadspecification':
        ob_start();
        require __DIR__ . '/pages/uploadSpecification.php';
        $content = ob_get_clean();
        break;
    case 'calculation':
        ob_start();
        require __DIR__ . '/pages/calculation.php';
        $content = ob_get_clean();
        break;
    case 'createcalculation':
        ob_start();
        require __DIR__ . '/pages/createCalculation.php';
        $content = ob_get_clean();
        break;
    case 'coefficients':
        ob_start();
        require __DIR__ . '/pages/coefficients.php';
        $content = ob_get_clean();
        break;
	case 'schedule':
		ob_start();
		require __DIR__ . '/pages/schedule.php';
		$content = ob_get_clean();
		break;
	case 'diff':
		ob_start();
		require __DIR__ . '/pages/diff.php';
		$content = ob_get_clean();
		break;
    case 'scheduleindev':
        ob_start();
        require __DIR__ . '/pages/scheduleInDev.php';
        $content = ob_get_clean();
        break;
    case 'schedule.old':
        ob_start();
        require __DIR__ . '/pages/schedule.old.php';
        $content = ob_get_clean();
        break;
    case 'schedule.all':
        ob_start();
        require __DIR__ . '/pages/schedule.all.php';
        $content = ob_get_clean();
        break;
    case 'schedule.oktoberfest':
        ob_start();
        require __DIR__ . '/pages/schedule.oktoberfest.php';
        $content = ob_get_clean();
        break;
    case 'schedule.2022':
        ob_start();
        require __DIR__ . '/pages/schedule.2022.php';
        $content = ob_get_clean();
        break;
    case 'schedule.2022.settings':
        ob_start();
        require __DIR__ . '/pages/schedule.2022.settings.php';
        $content = ob_get_clean();
        break;
    case 'schedule.2022.generate':
        ob_start();
        require __DIR__ . '/pages/schedule.2022.generate.php';
        $content = ob_get_clean();
        break;
    default:
        throw new Exception('Page \'' . $page . '\' not found');
}

engine::TEMPLATE()->setTagValue('content', $content);


