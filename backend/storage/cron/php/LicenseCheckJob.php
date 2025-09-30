<?php

namespace MythicalDash\Cron;

use MythicalDash\App;
use MythicalDash\Chat\TimedTask;
use MythicalDash\Config\ConfigInterface;
use MythicalDash\Cron\Cron;
use MythicalDash\Cron\TimeTask;
use MythicalDash\Hooks\MythicalSystems\Utils\BungeeChatApi;

class LicenseCheckJob implements TimeTask
{
    private const LICENSE_KEY_URL = 'https://raw.githubusercontent.com/lyminhthai2009/MythicalDashKey/refs/heads/main/key.txt';
    private const MAINTENANCE_FILE = APP_STORAGE_DIR . 'caches/maintenance.php';

    public function run()
    {
        $cron = new Cron('license-check', '2M'); // Changed from 5M to 2M
        try {
            $cron->runIfDue(function () {
                $app = App::getInstance(false, true);
                $config = $app->getConfig();
                $chat = new BungeeChatApi();

                $chat->sendOutputWithNewLine('&8[&bLicense Check&8] &7Running license validation...');
                
                $storedKey = $config->getDBSetting(ConfigInterface::LICENSE_KEY, '');

                if (empty($storedKey)) {
                    $chat->sendOutputWithNewLine('&8[&bLicense Check&8] &cLicense key is not set. Forcing maintenance mode.');
                    $this->enableMaintenanceMode('Invalid License Key');
                    TimedTask::markRun("license-check", false, "License key is not set.");
                    return;
                }
                
                try {
                    $client = new \GuzzleHttp\Client(['timeout' => 5.0]);
                    $response = $client->get(self::LICENSE_KEY_URL);
                    
                    if ($response->getStatusCode() === 200) {
                        $validKeysContent = $response->getBody()->getContents();
                    } else {
                        $chat->sendOutputWithNewLine('&8[&bLicense Check&8] &cCould not fetch license key list. Status code: ' . $response->getStatusCode());
                        TimedTask::markRun("license-check", false, "Could not fetch license key list. Status code: " . $response->getStatusCode());
                        return;
                    }
                } catch (\Exception $e) {
                    $chat->sendOutputWithNewLine('&8[&bLicense Check&8] &cException while fetching license key list: ' . $e->getMessage());
                    TimedTask::markRun("license-check", false, "Exception while fetching license key list: " . $e->getMessage());
                    return;
                }

                $validKeys = array_filter(array_map('trim', explode("\n", $validKeysContent)));

                if (in_array($storedKey, $validKeys)) {
                    $chat->sendOutputWithNewLine('&8[&bLicense Check&8] &aLicense key is valid.');
                    $this->disableMaintenanceMode();
                    TimedTask::markRun("license-check", true, "License key is valid.");
                } else {
                    $chat->sendOutputWithNewLine('&8[&bLicense Check&8] &cLicense key is invalid! Enabling maintenance mode.');
                    $this->enableMaintenanceMode('Invalid License Key');
                    TimedTask::markRun("license-check", false, "License key is invalid!");
                }
            });
        } catch (\Throwable $e) {
            TimedTask::markRun('license-check', false, $e->getMessage());
        }
    }
    
    private function enableMaintenanceMode(string $message): void
    {
        $fileTemplate = "<?php header('Content-Type: application/json'); http_response_code(503); echo json_encode(['code'=>503,'message'=>'".$message."','error'=>'Service Unavailable','success'=>false,],JSON_PRETTY_PRINT);die();";
        file_put_contents(self::MAINTENANCE_FILE, $fileTemplate);
    }
    
    private function disableMaintenanceMode(): void
    {
        if (file_exists(self::MAINTENANCE_FILE)) {
            $content = file_get_contents(self::MAINTENANCE_FILE);
            if (strpos($content, 'Invalid License Key') !== false) {
                unlink(self::MAINTENANCE_FILE);
            }
        }
    }
}