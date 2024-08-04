<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

define('VG_ACCESS', true);

header('Content-Type:text/html;charset=utf-8');
session_start();

require_once 'config.php';
require_once 'core/base/settings/internal_settings.php';
require_once 'libraries/functions.php';

use core\base\exceptions\RouteException;
use core\base\controller\BaseRoute;
use core\base\exceptions\DbException;

try{
    BaseRoute::routeDirection();
}
catch (RouteException $e){
    exit($e->showMessage());
}
catch (DbException $e){
    exit($e->showMessage());
}


