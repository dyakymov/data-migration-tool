<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

error_reporting(E_ALL);

$magentoDir = require __DIR__ . '/etc/magento_path.php';
require_once "{$magentoDir}/app/autoload.php";
use Magento\Framework\App\Bootstrap;

register_shutdown_function(function() {
    $fatalErrorFlag = E_ERROR | E_USER_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR;
    $error = error_get_last();
    if ($error && $error['type'] & $fatalErrorFlag) {
        echo "Fatal Error: '{$error['message']}' in '{$error['file']}' on line {$error['line']}" . PHP_EOL;
    }
});

$params = [];
$bootstrap = Bootstrap::create($magentoDir, $params);
/** @var Migration\Migration $application */
$application = $bootstrap->createApplication('Migration\Migration', ['entryPoint' => basename(__FILE__)]);
$bootstrap->run($application);
