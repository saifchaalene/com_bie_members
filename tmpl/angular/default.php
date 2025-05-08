<?php
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

$base = Uri::root(true) . '/administrator/components/com_bie_members/media/angular/';
?>


<base href="<?= $base ?>" />

<link rel="icon" href="<?= $base ?>favicon.ico">
<link rel="stylesheet" href="<?= $base ?>styles-5INURTSO.css">

<app-root></app-root>

<script type="module" src="<?= $base ?>main-4RP3EEPK.js"></script>
<script type="module" src="<?= $base ?>polyfills-FFHMD2TL.js"></script>
