<?php

namespace OCA\CMSPico\AppInfo;

use OCP\AppFramework\App;

class Application extends App {

	const APP_NAME = 'cms_pico';

	/**
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct(self::APP_NAME, $params);

		$this->registerHooks();

		$toto = new \Pico();
	}


	/**
	 * Register Hooks
	 */
	public function registerHooks() {
	}



	/**
	 * Register Navigation Tab
	 */
	public function registerNavigation() {

		$this->getContainer()
			 ->getServer()
			 ->getNavigationManager()
			 ->add(
				 function() {
					 $urlGen = \OC::$server->getURLGenerator();
					 $navName = \OC::$server->getL10N(self::APP_NAME)
											->t('Pico CMS');

					 return [
						 'id' => self::APP_NAME,
						 'order' => 5,
						 'href' => $urlGen->linkToRoute('cms_pico.Navigation.navigate'),
						 'icon' => $urlGen->imagePath(self::APP_NAME, 'ruler.svg'),
						 'name' => $navName
					 ];
				 }
			 );
	}


	public function registerSettingsAdmin() {
		\OCP\App::registerAdmin(self::APP_NAME, 'lib/admin');
	}

	public function registerSettingsPersonal() {
		\OCP\App::registerPersonal(self::APP_NAME, 'lib/personal');
	}
}

