<?php
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

$base = Uri::root(true) . '/administrator/components/com_bie_members/media/angular/';
?>

<base href="<?= $base ?>" />
<link rel="icon" href="<?= $base ?>favicon.ico">
<link rel="stylesheet" href="<?= $base ?>styles-5INURTSO.css">

<!-- Main Angular App -->
<app-root></app-root>

<script type="module" src="<?= $base ?>main-B6TSMP47.js"></script> 
<script src="<?= $base ?>polyfills-FFHMD2TL.js"></script>
