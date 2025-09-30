<?php

/*
 * This file is part of MythicalDash.
 *
 * MIT License
 *
 * Copyright (c) 2020-2025 MythicalSystems
 * Copyright (c) 2020-2025 Cassian Gherman (NaysKutzu)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * Please rather than modifying the dashboard code try to report the thing you wish on our github or write a plugin
 */

namespace MythicalDash\Plugins;

use MythicalDash\App;
use MythicalDash\Plugins\Mixins\MixinManager;

class PluginProcessor
{
    private static array $pluginCache = [];
    private static array $validationCache = [];
    private static array $mixinCache = [];

    /**
     * Get the event class for a plugin.
     *
     * @param string $identifier The plugin identifier
     *
     * @return MythicalDashPlugin|null The event class instance or null if not found
     */
    public static function getEventProcessor(string $identifier): ?MythicalDashPlugin
    {
        // Return cached instance if available
        if (isset(self::$pluginCache[$identifier])) {
            return self::$pluginCache[$identifier];
        }

        $logger = App::getInstance(true)->getLogger();
        $logger->debug('Initializing event processor for plugin: ' . $identifier);

        try {
            // Get and validate plugin config
            $config = PluginHelper::getPluginConfig($identifier);
            if (empty($config)) {
                $logger->warning('Invalid or empty config for plugin: ' . $identifier);

                return null;
            }

            $entryClass = $config['plugin']['name'];

            $eventClass = "MythicalDash\\Addons\\{$identifier}\\{$entryClass}";
            if (!class_exists($eventClass)) {
                $logger->warning("Event class not found: {$eventClass}");

                return null;
            }

            if (!is_subclass_of($eventClass, MythicalDashPlugin::class)) {
                $logger->warning("Class {$eventClass} does not implement MythicalDashPlugin");

                return null;
            }

            // Create and cache instance
            $instance = new $eventClass();
            self::$pluginCache[$identifier] = $instance;

            $logger->debug('Successfully initialized event processor for: ' . $identifier);

            return $instance;

        } catch (\Throwable $e) {
            $logger->error('Failed to initialize plugin event processor: ' . $e->getMessage(), false);

            return null;
        }
    }

    /**
     * Check if a plugin has a valid event implementation.
     *
     * @param string $identifier The plugin identifier
     *
     * @return bool True if plugin has valid event, false otherwise
     */
    public static function hasValidEvent(string $identifier): bool
    {
        // Use cached validation result if available
        if (isset(self::$validationCache[$identifier])) {
            return self::$validationCache[$identifier];
        }

        $result = self::getEventProcessor($identifier) !== null;
        self::$validationCache[$identifier] = $result;

        return $result;
    }

    /**
     * Process an event for a plugin.
     *
     * @param string $identifier The plugin identifier
     * @param PluginEvents $event The event to process
     */
    public static function process(string $identifier, PluginEvents $event): void
    {
        $logger = App::getInstance(true)->getLogger();
        $logger->debug('Processing event for plugin: ' . $identifier);

        try {
            $processor = self::getEventProcessor($identifier);
            if ($processor === null) {
                $logger->warning('No valid event processor found for plugin: ' . $identifier);

                return;
            }

            $processor->processEvents($event);
            $logger->debug('Successfully processed event for plugin: ' . $identifier);

        } catch (\Throwable $e) {
            $logger->error('Failed to process plugin event', false);
        }
    }

    /**
     * Get mixin for a specific plugin.
     *
     * @param string $identifier The plugin identifier
     * @param string $mixinId The mixin identifier
     *
     * @return object|null The mixin instance or null if not found
     */
    public static function getMixin(string $identifier, string $mixinId): ?object
    {
        $cacheKey = "{$identifier}:{$mixinId}";

        // Return cached result if available
        if (isset(self::$mixinCache[$cacheKey])) {
            return self::$mixinCache[$cacheKey];
        }

        $logger = App::getInstance(true)->getLogger();
        $logger->debug("Getting mixin '{$mixinId}' for plugin: {$identifier}");

        try {
            $mixin = MixinManager::getMixin($identifier, $mixinId);

            if ($mixin === null) {
                $logger->warning("Mixin '{$mixinId}' not found for plugin: {$identifier}");

                return null;
            }

            // Cache the result
            self::$mixinCache[$cacheKey] = $mixin;

            return $mixin;
        } catch (\Throwable $e) {
            $logger->error("Failed to get mixin '{$mixinId}' for plugin '{$identifier}': " . $e->getMessage());

            return null;
        }
    }

    /**
     * Get all mixins for a plugin.
     *
     * @param string $identifier The plugin identifier
     *
     * @return array The mixins associated with the plugin
     */
    public static function getMixins(string $identifier): array
    {
        try {
            return MixinManager::getMixinsForPlugin($identifier);
        } catch (\Throwable $e) {
            $logger = App::getInstance(true)->getLogger();
            $logger->error("Failed to get mixins for plugin '{$identifier}': " . $e->getMessage());

            return [];
        }
    }

    /**
     * Check if a plugin has a specific mixin.
     *
     * @param string $identifier The plugin identifier
     * @param string $mixinId The mixin identifier
     *
     * @return bool True if the plugin has the mixin, false otherwise
     */
    public static function hasMixin(string $identifier, string $mixinId): bool
    {
        try {
            return MixinManager::pluginHasMixin($identifier, $mixinId);
        } catch (\Throwable $e) {
            $logger = App::getInstance(true)->getLogger();
            $logger->error("Failed to check if plugin '{$identifier}' has mixin '{$mixinId}': " . $e->getMessage());

            return false;
        }
    }
}
