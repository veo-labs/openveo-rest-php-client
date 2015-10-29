<?php

/**
 * Use this autoloader if you don't use composer.
 */

define('OPENVEO_ROOT', __DIR__ . '/src/');

spl_autoload_register(function($class) {
  $location = OPENVEO_ROOT . str_replace(array('Openveo\\', '\\'), array('', '/'), $class) . '.php';
  if(!is_readable($location)) return;
  require_once $location;
});

?>