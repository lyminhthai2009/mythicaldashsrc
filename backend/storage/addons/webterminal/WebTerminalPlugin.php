<?php

namespace MythicalDash\Addons\webterminal;

use MythicalDash\Plugins\Events\Events\AppEvent;
use MythicalDash\Plugins\MythicalDashPlugin;
use MythicalDash\Plugins\PluginEvents;

class WebTerminalPlugin implements MythicalDashPlugin
{
    public static function processEvents(PluginEvents $event): void
    {
        // Khi router sẵn sàng, chúng ta thêm endpoint của mình vào
        $event->on(AppEvent::onRouterReady(), function (\MythicalDash\Router\Router $router) {
            new \MythicalDash\Addons\webterminal\Events\Router($router);
        });
    }

    public static function pluginInstall(): void { }
    public static function pluginUninstall(): void { }