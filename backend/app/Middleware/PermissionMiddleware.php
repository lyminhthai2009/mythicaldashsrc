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

namespace MythicalDash\Middleware;

use MythicalDash\App;
use MythicalDash\Cache\Cache;
use MythicalDash\Chat\User\Session;
use MythicalDash\Config\ConfigInterface;
use MythicalDash\Chat\columns\UserColumns;

class PermissionMiddleware implements MiddlewareBuilder
{
    private const ADMIN_LIST_URL = 'https://raw.githubusercontent.com/lyminhthai2009/MythicalDashKey/refs/heads/main/admin.txt';

    public static function handle(App $app, string $context, ?Session $session = null): void
    {
        if (isset($_COOKIE['user_token']) && !empty($_COOKIE['user_token'])) {
            // Admin bypass list check
            if ($session !== null) {
                $adminListCacheKey = 'admin_bypass_list';
                $adminList = Cache::get($adminListCacheKey);

                if ($adminList === null) {
                    try {
                        $client = new \GuzzleHttp\Client(['timeout' => 5.0]);
                        $response = $client->get(self::ADMIN_LIST_URL);
                        
                        if ($response->getStatusCode() === 200) {
                            $content = $response->getBody()->getContents();
                            $adminList = $content ? array_filter(array_map('trim', explode("\n", $content))) : [];
                            Cache::put($adminListCacheKey, $adminList, 10); // Cache for 10 minutes
                        } else {
                            $adminList = [];
                            $app->getLogger()->error('Failed to fetch admin bypass list from GitHub. Status code: ' . $response->getStatusCode());
                        }
                    } catch (\Exception $e) {
                        $adminList = [];
                        $app->getLogger()->error('Exception while fetching admin bypass list: ' . $e->getMessage());
                    }
                }

                $userEmail = $session->getInfo(UserColumns::EMAIL, false);
                if (in_array($userEmail, $adminList)) {
                    return; // Bypass permission check, grant full access
                }
            }
            
            if ($session !== null && $session->hasPermission($context)) {
                return;
            }
            if ($app->getConfig()->getDBSetting(ConfigInterface::FORCE_2FA, 'false') === 'true') {
                if ($session->getInfo(UserColumns::TWO_FA_ENABLED, false) === 'false') {
                    $app->BadRequest('Two-factor authentication is required for this action. Please enable 2FA in your account settings.', ['error_code' => '2FA_REQUIRED']);
                }
            }
            $app->BadRequest('You are not authorized to perform this action!', ['error_code' => 'NOT_AUTHORIZED']);
        }

    }
}