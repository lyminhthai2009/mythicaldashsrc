CREATE TABLE IF NOT EXISTS
    `mythicaldash_tickets_attachments` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `ticket` int(16) NOT NULL,
        `file` TEXT NOT NULL,
        `enabled` ENUM ('false', 'true') NOT NULL DEFAULT 'false',
        `deleted` ENUM ('false', 'true') NOT NULL DEFAULT 'false',
        `locked` ENUM ('false', 'true') NOT NULL DEFAULT 'false',
        `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`ticket`) REFERENCES `mythicaldash_tickets` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;