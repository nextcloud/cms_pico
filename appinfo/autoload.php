<?php

namespace OCA\CMSPico\AppInfo;

$composerDir = __DIR__ . '/../vendor/';

if (is_dir($composerDir) && file_exists($composerDir . 'autoload.php')) {
	require_once $composerDir . 'autoload.php';
}

