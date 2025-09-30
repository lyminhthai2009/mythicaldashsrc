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

use MythicalDash\App;
use MythicalDash\Chat\User\User;
use MythicalDash\Chat\User\Roles;
use MythicalDash\Chat\User\Session;
use MythicalDash\Chat\Tickets\Tickets;
use MythicalDash\Chat\Tickets\Messages;
use MythicalDash\Chat\columns\UserColumns;
use MythicalDash\Chat\Tickets\Attachments;
use MythicalDash\Chat\Tickets\Departments;
use MythicalDash\Chat\User\UserActivities;
use MythicalDash\CloudFlare\CloudFlareRealIP;
use MythicalDash\Plugins\Events\Events\TicketEvent;
use MythicalDash\Chat\interface\UserActivitiesTypes;

$router->get('/api/user/ticket/(.*)/messages', function ($ticketId) {
    App::init();
    $appInstance = App::getInstance(true);
    $appInstance->allowOnlyGET();
    $s = new Session($appInstance);
    $ticketId = (int) $ticketId;
    try {
        if (Tickets::exists($ticketId)) {
            $messages = Messages::getMessagesByTicketId($ticketId);
            $ticketInfo = Tickets::getTicket($ticketId);
            $ticketInfo['department_id'] = $ticketInfo['department'];
            $ticketInfo['department'] = Departments::get((int) $ticketInfo['department']);
            $uuid = $s->getInfo(UserColumns::UUID, false);
            global $eventManager;
            if ($ticketInfo['user'] !== $s->getInfo(UserColumns::UUID, false) && $s->getInfo(UserColumns::ROLE_ID, false) < 3) {
                $appInstance->Forbidden('You do not have permission to view this ticket', ['error_code' => 'ERROR_PERMISSION_DENIED']);

                return;
            }

            if (empty($ticketInfo['department'])) {
                $ticketInfo['department'] = [
                    'id' => 0,
                    'name' => 'Deleted Department',
                    'description' => 'This department has been deleted.',
                    'time_open' => '08:30',
                    'time_close' => '17:30',
                    'enabled' => 'true',
                    'deleted' => 'false',
                    'locked' => 'false',
                    'date' => '2024-12-25 22:25:09',
                ];
            }

            // Get user info for messages and ticket
            $messages = array_map(function ($msg) {
                $userInfo = User::getInfoArray(User::getTokenFromUUID($msg['user']), [UserColumns::USERNAME, UserColumns::AVATAR, UserColumns::ROLE_ID], []);
                $msg['user'] = [
                    'username' => $userInfo['username'],
                    'avatar' => $userInfo['avatar'],
                    'role' => Roles::getRoleNameById((int) $userInfo['role']),
                    'uuid' => $msg['user'],
                ];

                return $msg;
            }, $messages);

            $ticketUserInfo = User::getInfoArray(User::getTokenFromUUID($ticketInfo['user']), [UserColumns::USERNAME, UserColumns::AVATAR, UserColumns::ROLE_ID], []);
            $ticketInfo['user'] = [
                'username' => $ticketUserInfo['username'],
                'avatar' => $ticketUserInfo['avatar'],
                'role' => Roles::getRoleNameById((int) $ticketUserInfo['role']),
                'uuid' => $ticketInfo['user'],
            ];

            $attachments = Attachments::getAttachmentsByTicketId($ticketId);

            $eventManager->emit(TicketEvent::onTicketView(), [
                'ticket_id' => $ticketId,
                'user_id' => $uuid,
                'messages' => $messages,
                'attachments' => $attachments,
                'ticket' => $ticketInfo,
            ]);

            $appInstance->OK(200, [
                'messages' => $messages,
                'ticket' => $ticketInfo,
                'attachments' => $attachments,
            ]);
        } else {
            $appInstance->BadRequest('Ticket not found', ['error_code' => 'ERROR_TICKET_NOT_FOUND']);
        }
    } catch (Exception $e) {
        $appInstance->BadRequest('Failed to get messages', ['error_code' => 'ERROR_GETTING_MESSAGES']);
    }

});

$router->post('/api/user/ticket/(.*)/reply', function ($ticketId) {
    App::init();
    $appInstance = App::getInstance(true);
    $appInstance->allowOnlyPOST();
    $s = new Session($appInstance);
    $ticketId = (int) $ticketId;
    global $eventManager;
    $uuid = $s->getInfo(UserColumns::UUID, false);
    if (!isset($_POST['message'])) {
        $appInstance->BadRequest('Message is required', ['error_code' => 'ERROR_MESSAGE_REQUIRED']);

        return;
    }
    $message = $_POST['message'];

    if (Tickets::exists($ticketId)) {
        $ticketInfo = Tickets::getTicket($ticketId);
        if ($ticketInfo['user'] !== $s->getInfo(UserColumns::UUID, false) && $s->getInfo(UserColumns::ROLE_ID, false) < 3) {
            $appInstance->Forbidden('You do not have permission to reply to this ticket', ['error_code' => 'ERROR_PERMISSION_DENIED']);

            return;
        }
        Messages::createMessage($ticketId, $message, $uuid);
        $eventManager->emit(TicketEvent::onTicketReply(), [
            'ticket_id' => $ticketId,
            'user_id' => $uuid,
            'message' => $message,
        ]);
        UserActivities::add(
            $uuid,
            UserActivitiesTypes::$ticket_reply,
            CloudFlareRealIP::getRealIP(),
            "Replied to ticket $ticketId"
        );
        $appInstance->OK(200, ['message' => 'Message sent']);
    } else {
        $appInstance->BadRequest('Ticket not found', ['error_code' => 'ERROR_TICKET_NOT_FOUND']);
    }
});

$router->post('/api/user/ticket/(.*)/status', function ($ticketId) {
    App::init();
    $appInstance = App::getInstance(true);
    $appInstance->allowOnlyPOST();
    $s = new Session($appInstance);
    $ticketId = (int) $ticketId;
    global $eventManager;
    $uuid = $s->getInfo(UserColumns::UUID, false);

    if (!isset($_POST['status'])) {
        $appInstance->BadRequest('Status is required', ['error_code' => 'ERROR_STATUS_REQUIRED']);

        return;
    }
    $status = $_POST['status'];

    $status_list = ['open', 'closed', 'waiting', 'replied', 'inprogress'];
    if (!in_array($status, $status_list)) {
        $appInstance->BadRequest('Invalid status', ['error_code' => 'ERROR_INVALID_STATUS']);

        return;
    }

    if (Tickets::exists($ticketId)) {
        $ticketInfo = Tickets::getTicket($ticketId);
        if ($ticketInfo['user'] !== $s->getInfo(UserColumns::UUID, false) && $s->getInfo(UserColumns::ROLE_ID, false) < 3) {
            $appInstance->Forbidden('You do not have permission to update this ticket', ['error_code' => 'ERROR_PERMISSION_DENIED']);

            return;
        }
        Tickets::updateTicketStatus($ticketId, $status);
        $eventManager->emit(TicketEvent::onTicketUpdate(), [
            'ticket_id' => $ticketId,
            'user_id' => $uuid,
            'status' => $status,
        ]);
        UserActivities::add(
            $uuid,
            UserActivitiesTypes::$ticket_update,
            CloudFlareRealIP::getRealIP(),
            "Updated ticket $ticketId"
        );
        $appInstance->OK(200, ['message' => 'Ticket status updated']);
    } else {
        $appInstance->BadRequest('Ticket not found', ['error_code' => 'ERROR_TICKET_NOT_FOUND']);
    }
});

$router->post('/api/user/ticket/(.*)/attachments', function ($ticketId) {
    App::init();
    $appInstance = App::getInstance(true);
    $appInstance->allowOnlyPOST();
    $s = new Session($appInstance);
    $ticketId = (int) $ticketId;
    global $eventManager;
    $uuid = $s->getInfo(UserColumns::UUID, false);

    if (!isset($_FILES['attachments'])) {
        $appInstance->BadRequest('Attachments are required', ['error_code' => 'ERROR_ATTACHMENTS_REQUIRED']);

        return;
    }

    $attachments = $_FILES['attachments'];

    // Always treat as single file since frontend sends one at a time
    $attachments = [
        'name' => [$attachments['name']],
        'type' => [$attachments['type']],
        'tmp_name' => [$attachments['tmp_name']],
        'error' => [$attachments['error']],
        'size' => [$attachments['size']],
    ];

    if (Tickets::exists($ticketId)) {
        $ticketInfo = Tickets::getTicket($ticketId);
        if ($ticketInfo['user'] !== $s->getInfo(UserColumns::UUID, false) && $s->getInfo(UserColumns::ROLE_ID, false) < 3) {
            $appInstance->Forbidden('You do not have permission to upload attachments to this ticket', ['error_code' => 'ERROR_PERMISSION_DENIED']);

            return;
        }

        // Validate file types and sizes
        $allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $maxFiles = 5;

        // Now we can safely check count
        if (count($attachments['name']) > $maxFiles) {
            $appInstance->BadRequest("Maximum {$maxFiles} files allowed", ['error_code' => 'ERROR_TOO_MANY_FILES']);

            return;
        }

        // Validate files first
        for ($i = 0; $i < count($attachments['name']); ++$i) {
            if (!in_array($attachments['type'][$i], $allowedTypes)) {
                $appInstance->BadRequest('Invalid file type. Only PNG, JPG and GIF allowed', ['error_code' => 'ERROR_INVALID_FILE_TYPE']);

                return;
            }

            if ($attachments['size'][$i] > $maxSize) {
                $appInstance->BadRequest('File too large. Maximum size is 2MB', ['error_code' => 'ERROR_FILE_TOO_LARGE']);

                return;
            }

            if ($attachments['error'][$i] !== UPLOAD_ERR_OK) {
                $appInstance->BadRequest('File upload failed', ['error_code' => 'ERROR_UPLOAD_FAILED']);

                return;
            }
        }

        // Upload files
        try {
            // Create date-based folder structure
            $currentDate = date('Y-m-d');
            $uploadPath = APP_PUBLIC . '/attachments/tickets/' . $ticketId . '/' . $currentDate;

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $uploadedFiles = [];
            for ($i = 0; $i < count($attachments['name']); ++$i) {
                $extension = pathinfo($attachments['name'][$i], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                $destination = $uploadPath . '/' . $filename;

                if (!move_uploaded_file($attachments['tmp_name'][$i], $destination)) {
                    throw new Exception('Failed to move uploaded file');
                }

                // Store attachment info in database
                $relativePath = 'tickets/' . $ticketId . '/' . $currentDate . '/' . $filename;
                Attachments::addAttachment($ticketId, $relativePath);
                $uploadedFiles[] = $relativePath;
            }
            $eventManager->emit(TicketEvent::onTicketAttachmentUpload(), [
                'ticket_id' => $ticketId,
                'user_id' => $uuid,
                'attachments' => $uploadedFiles,
            ]);
            $appInstance->OK(200, [
                'message' => 'Attachments uploaded successfully',
                'files' => $uploadedFiles,
            ]);

        } catch (Exception $e) {
            $appInstance->InternalServerError('Failed to process attachments', ['error_code' => 'ERROR_PROCESSING_ATTACHMENTS']);

            return;
        }
    } else {
        $appInstance->BadRequest('Ticket not found', ['error_code' => 'ERROR_TICKET_NOT_FOUND']);

        return;
    }
});
