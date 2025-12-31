<?php
declare(strict_types=1);

namespace OCA\CMSPico\AppInfo;

use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCA\CMSPico\Listener\UserDeletedEventListener;
use OCA\CMSPico\Listener\GroupDeletedEventListener;
use OCA\CMSPico\Listener\ExternalStorageBackendEventListener;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\User\Events\UserDeletedEvent;

class Bootstrap implements IBootstrap {
    public function register(IRegistrationContext $context): void {
        // ✅ Enregistrement des listeners
        $context->registerEventListener(UserDeletedEvent::class, UserDeletedEventListener::class);
        $context->registerEventListener(GroupDeletedEvent::class, GroupDeletedEventListener::class);
        $context->registerEventListener('OCA\\Files_External::loadAdditionalBackends', ExternalStorageBackendEventListener::class);
    }

    public function boot(IBootContext $context): void {
        // ✅ Charger l’autoloader Composer
        $autoload = \dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }
    }
}
