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

namespace MythicalDash\Chat\Referral;

use MythicalDash\Chat\Database;

class ReferralUses extends Database
{
    public const TABLE_NAME = 'mythicaldash_referral_uses';

    /**
     * Get the table name for referral uses.
     *
     * @return string The table name
     */
    public static function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * Get list of all non-deleted referral uses.
     *
     * @return array List of referral uses
     */
    public static function getList(): array
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE deleted = "false"');
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            self::db_Error('Failed to get list of referral uses: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Get list of all non-deleted referral codes by user.
     *
     * @return array List of referral codes
     */
    public static function getListByReferralCode(int $referralCodeId): array
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE referral_code_id = :referral_code_id AND deleted = "false"');
            $stmt->bindParam(':referral_code_id', $referralCodeId);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            self::db_Error('Failed to get list of referral codes by user: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Create a new referral use.
     *
     * @param int $referralCodeId The ID of the referral code
     * @param string $referredUserId The UUID of the referred user
     *
     * @return int|false The ID of newly created use, or false on failure
     */
    public static function create(int $referralCodeId, string $referredUserId): int|false
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('INSERT INTO ' . self::getTableName() . ' (referral_code_id, referred_user_id) VALUES (:referral_code_id, :referred_user_id)');
            $stmt->bindParam(':referral_code_id', $referralCodeId, \PDO::PARAM_INT);
            $stmt->bindParam(':referred_user_id', $referredUserId);
            $stmt->execute();

            return (int) $dbConn->lastInsertId();
        } catch (\Exception $e) {
            self::db_Error('Failed to create referral use: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Delete a referral use (soft delete).
     *
     * @param int $id The ID of the referral use
     *
     * @return bool True if deletion successful, false otherwise
     */
    public static function delete(int $id): bool
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('UPDATE ' . self::getTableName() . ' SET deleted = "true" WHERE id = :id AND deleted = "false"');
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);

            return $stmt->execute();
        } catch (\Exception $e) {
            self::db_Error('Failed to delete referral use: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get a referral use by ID.
     *
     * @param int $id The ID of the referral use
     *
     * @return array|false The referral use data or false if not found
     */
    public static function getById(int $id): array|false
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE id = :id AND deleted = "false"');
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            self::db_Error('Failed to get referral use by ID: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get referral uses by referral code ID.
     *
     * @param int $referralCodeId The ID of the referral code
     *
     * @return array List of referral uses
     */
    public static function getByReferralCodeId(int $referralCodeId): array
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE referral_code_id = :referral_code_id AND deleted = "false"');
            $stmt->bindParam(':referral_code_id', $referralCodeId, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            self::db_Error('Failed to get referral uses by referral code ID: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Get referral uses by referred user ID.
     *
     * @param string $referredUserId The UUID of the referred user
     *
     * @return array List of referral uses
     */
    public static function getByReferredUserId(string $referredUserId): array
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE referred_user_id = :referred_user_id AND deleted = "false"');
            $stmt->bindParam(':referred_user_id', $referredUserId);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            self::db_Error('Failed to get referral uses by referred user ID: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Check if a user has already used a referral code.
     *
     * @param string $referredUserId The UUID of the referred user
     * @param int $referralCodeId The ID of the referral code
     *
     * @return bool True if the user has used the code, false otherwise
     */
    public static function hasUserUsedCode(string $referredUserId, int $referralCodeId): bool
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('SELECT COUNT(*) FROM ' . self::getTableName() . ' WHERE referred_user_id = :referred_user_id AND referral_code_id = :referral_code_id AND deleted = "false"');
            $stmt->bindParam(':referred_user_id', $referredUserId);
            $stmt->bindParam(':referral_code_id', $referralCodeId, \PDO::PARAM_INT);
            $stmt->execute();

            return (bool) $stmt->fetchColumn();
        } catch (\Exception $e) {
            self::db_Error('Failed to check if user has used referral code: ' . $e->getMessage());

            return false;
        }
    }
}
