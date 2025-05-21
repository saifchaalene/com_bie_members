<?php
defined('_JEXEC') or die;

use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

$base = Uri::root(true) . '/administrator/components/com_bie_members/media/angular/';

$tokenKey = Session::getFormToken();
$tokenValue = '1'; 
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>AngularBie</title>

  <meta name="csrf-token-key" content="<?= $tokenKey ?>">
  <meta name="csrf-token" content="<?= $tokenValue ?>">

  <base href="<?= Uri::base(true) ?>/index.php?option=com_bie_members&view=angular&tmpl=component&">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="<?= $base ?>favicon.ico">
  <link rel="stylesheet" href="<?= $base ?>styles-QYJRYY4Y.css">
</head>
<body>
  <app-root></app-root>

  <script type="module" src="<?= $base ?>polyfills-EJ46DL77.js"></script>
  <script type="module" src="<?= $base ?>main-YJIQDJMC.js"></script>
</body>
</html>