<?php
// $Id: reference.php 821 2011-12-08 23:46:19Z i.bitcero $
// --------------------------------------------------------------
// Ability Help
// http://www.redmexico.com.mx
// http://www.exmsystem.net
// --------------------------------------------
// @author BitC3R0 <i.bitcero@gmail.com>
// @license: GPL v2

define('AH_LOCATION', 'references');
require __DIR__ . '/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die(_MS_AH_NOID);
}

$ref = new AHReference($id);
if ($ref->isNew()) {
    die(_MS_AH_NOEXISTS);
}

echo $ref->reference();
