<?php

namespace OCA\CMSPico;

use OCA\CMSPico\Controller\NavigationController;
use OCP\AppFramework\Http\TemplateResponse;

$app = new AppInfo\Application();

/** @var TemplateResponse $response */
$response = $app->getContainer()
				->query(NavigationController::class)
				->personal();

return $response->render();


