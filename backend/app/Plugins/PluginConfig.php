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

class PluginConfig
{
    public static function getRequired(): array
    {
        return [
            'name' => 'string',
            'identifier' => 'string',
            'description' => 'string',
            'flags' => 'array',
            'version' => 'string',
            'target' => 'string',
            'author' => 'array',
            'icon' => 'string',
            'dependencies' => 'array',
        ];
    }

    /**
     * Check if the plugin config is valid.
     *
     * @param string $identifier The plugin identifier
     *
     * @return bool If the plugin identifier is valid
     */
    public static function isValidIdentifier(string $identifier): bool
    {
        App::getInstance(true)->getLogger()->debug('Checking identifier: ' . $identifier);
        if (empty($identifier)) {
            return false;
        }
        if (preg_match('/\s/', $identifier)) {
            return false;
        }
        if (preg_match('/^[a-zA-Z0-9_]+$/', $identifier) === 1) {
            App::getInstance(true)->getLogger()->debug('Plugin id is allowed: ' . $identifier);

            return true;
        }
        App::getInstance(true)->getLogger()->warning('Plugin id is not allowed: ' . $identifier);

        return false;
    }

    /**
     * Check if the plugin config is valid.
     *
     * @param array $config The plugin config
     *
     * @return bool If the plugin config is valid
     */
    public static function isConfigValid(array $config): bool
    {
        try {
            $app = App::getInstance(true);
            if (empty($config)) {
                $app->getLogger()->warning('Plugin config is empty.');

                return false;
            }

            $config_Requirements = self::getRequired();
            $config = $config['plugin'];

            if (!array_key_exists('identifier', $config)) {
                $app->getLogger()->warning('Missing identifier for plugin.');

                return false;
            }

            foreach ($config_Requirements as $key => $value) {
                if (!array_key_exists($key, $config)) {
                    $app->getLogger()->warning('Missing key for plugin: ' . $config['identifier'] . ' key: ' . $key);

                    return false;
                }

                if (gettype($config[$key]) !== $value) {
                    $app->getLogger()->warning('Invalid type for plugin: ' . $config['identifier'] . ' key: ' . $key);

                    return false;
                }
            }

            if (!PluginFlags::validFlags($config['flags'])) {
                $app->getLogger()->warning('Invalid flags for plugin: ' . $config['identifier']);

                return false;
            }

            if (self::isValidIdentifier($config['identifier']) == false) {
                $app->getLogger()->warning('Invalid identifier for plugin.');

                return false;
            }

            // Validate mixins if they exist
            if (isset($config['mixins']) && !self::validateMixins($config['mixins'], $config['identifier'])) {
                $app->getLogger()->warning('Invalid mixins configuration for plugin: ' . $config['identifier']);

                return false;
            }

            $app->getLogger()->debug('Done processing: ' . $config['name']);

            return true;

        } catch (\Exception $e) {
            $app->getLogger()->error('Error processing plugin config: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get the mixins configuration for a plugin.
     *
     * @param string $identifier The plugin identifier
     *
     * @return array The mixins configuration
     */
    public static function getPluginMixinsConfig(string $identifier): array
    {
        $config = self::getConfig($identifier);

        return $config['mixins'] ?? [];
    }

    /**
     * Get the plugin config.
     *
     * @param string $identifier The plugin identifier
     *
     * @return array The plugin config
     */
    public static function getConfig(string $identifier): array
    {
        return PluginHelper::getPluginConfig($identifier);
    }

    /**
     * Get the required configuration fields for plugin admin setup.
     *
     * @param string $identifier The plugin identifier
     *
     * @return array The required config fields with their specifications
     */
    public static function getPluginRequiredAdminConfig(string $identifier): array
    {
        $config = self::getConfig($identifier);

        return $config['config'] ?? [];
    }

    /**
     * Validate a specific config value against its definition.
     *
     * @param array $configDef The config field definition
     * @param mixed $value The value to validate
     *
     * @return bool Whether the value is valid
     */
    public static function validateConfigValue(array $configDef, mixed $value): bool
    {
        // Handle nullable fields
        if ($value === null) {
            return $configDef['nullable'] ?? false;
        }

        // Validate type
        return match ($configDef['type']) {
            'string' => is_string($value),
            'integer', 'int' => is_int($value),
            'float', 'double' => is_float($value),
            'boolean', 'bool' => is_bool($value),
            'array' => is_array($value),
            default => false,
        };
    }

    /**
     * Validate the provided admin configuration against the required fields.
     *
     * @param string $identifier The plugin identifier
     * @param array $providedConfig The configuration to validate
     *
     * @return array Array with validation result and errors if any
     */
    public static function validateAdminConfig(string $identifier, array $providedConfig): array
    {
        $requiredConfig = self::getPluginRequiredAdminConfig($identifier);
        $errors = [];

        foreach ($requiredConfig as $field) {
            $fieldName = $field['name'];

            // Check if required field is provided
            if (!isset($providedConfig[$fieldName])) {
                if (!($field['nullable'] ?? false)) {
                    $errors[] = "Missing required field: {$fieldName}";
                }
                continue;
            }

            // Validate the value
            if (!self::validateConfigValue($field, $providedConfig[$fieldName])) {
                $errors[] = "Invalid value for field {$fieldName}: expected {$field['type']}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get default values for plugin configuration.
     *
     * @param string $identifier The plugin identifier
     *
     * @return array The default configuration values
     */
    public static function getDefaultConfig(string $identifier): array
    {
        $requiredConfig = self::getPluginRequiredAdminConfig($identifier);
        $defaults = [];

        foreach ($requiredConfig as $field) {
            $defaults[$field['name']] = $field['default'] ?? null;
        }

        return $defaults;
    }

    /**
     * Validate mixins configuration.
     *
     * @param array $mixins The mixins configuration
     * @param string $pluginIdentifier The plugin identifier
     *
     * @return bool True if valid, false otherwise
     */
    private static function validateMixins(array $mixins, string $pluginIdentifier): bool
    {
        try {
            $app = App::getInstance(true);
            $logger = $app->getLogger();

            // Mixins must be defined as an associative array
            foreach ($mixins as $mixinId => $mixinConfig) {
                if (!is_string($mixinId)) {
                    $logger->warning("Mixin identifier must be a string in plugin: {$pluginIdentifier}");

                    return false;
                }

                // If mixin config is provided, it must be an array
                if ($mixinConfig !== null && !is_array($mixinConfig)) {
                    $logger->warning("Mixin configuration must be an array in plugin: {$pluginIdentifier}, mixin: {$mixinId}");

                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            $app->getLogger()->error('Error validating mixins: ' . $e->getMessage());

            return false;
        }
    }
}
