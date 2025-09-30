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

namespace MythicalDash\Chat\Redeem;

use MythicalDash\Chat\Database;

class RedeemRedeems extends Database
{
    public const TABLE_NAME = 'mythicaldash_redeem_codes_redeems';

    /**
     * Get the table name for redeem code redeems.
     *
     * @return string The table name
     */
    public static function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * Get list of all non-deleted redeem code redeems.
     *
     * @return array List of redeem code redeems
     */
    public static function getList(): array
    {
        $dbConn = Database::getPdoConnection();
        $stmt = $dbConn->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE deleted = "false"');
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Create a new redeem code redeem record.
     *
     * @param string $user The user UUID
     * @param int $code The redeem code ID
     *
     * @return int|false The ID of newly created redeem record, or false on failure
     */
    public static function create(string $user, int $code): int|false
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('INSERT INTO ' . self::getTableName() . ' (user, code) VALUES (:user, :code)');
            $stmt->bindParam(':user', $user);
            $stmt->bindParam(':code', $code);

            $stmt->execute();

            return (int) $dbConn->lastInsertId();
        } catch (\Exception $e) {
            self::db_Error('Failed to create redeem record: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Soft delete a redeem record.
     *
     * @param int $id ID of record to delete
     *
     * @return bool True if deletion successful, false otherwise
     */
    public static function delete(int $id): bool
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('UPDATE ' . self::getTableName() . ' SET deleted = "true" WHERE id = :id');
            $stmt->bindParam(':id', $id);

            return $stmt->execute();
        } catch (\Exception $e) {
            self::db_Error('Failed to delete redeem record: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Check if a redeem record exists by ID.
     *
     * @param int $id ID to check
     *
     * @return bool True if record exists, false otherwise
     */
    public static function exists(int $id): bool
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('SELECT COUNT(*) FROM ' . self::getTableName() . ' WHERE id = :id AND deleted = "false"');
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            self::db_Error('Failed to check if redeem record exists: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get a redeem record by ID.
     *
     * @param int $id ID of record to get
     *
     * @return array|null Array containing redeem data, or null if not found
     */
    public static function get(int $id): ?array
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE id = :id AND deleted = "false"');
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            self::db_Error('Failed to get redeem record: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Check if a code has been redeemed by a user.
     *
     * @param int $code The redeem code ID
     * @param string $uuid The user UUID
     *
     * @return bool True if code has been redeemed, false otherwise
     */
    public static function isCodeRedeemed(int $code, string $uuid): bool
    {
        try {
            $dbConn = Database::getPdoConnection();
            $stmt = $dbConn->prepare('SELECT COUNT(*) FROM ' . self::getTableName() . ' WHERE code = :code AND user = :uuid AND deleted = "false"');
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':uuid', $uuid);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            self::db_Error('Failed to check if code is redeemed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Redeem a code.
     *
     * @param int $code The redeem code ID
     * @param string $uuid The user UUID
     *
     * @return bool|int True if redemption successful, false otherwise
     */
    public static function redeemCode(int $code, string $uuid): bool|int
    {
        return self::create($uuid, $code);
    }
}
