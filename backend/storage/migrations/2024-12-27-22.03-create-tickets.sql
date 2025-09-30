CREATE TABLE IF NOT EXISTS
    `mythicaldash_tickets` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `user` varchar(36) NOT NULL,
        `department` int(16) NOT NULL,
		`title` TEXT NOT NULL,
		`description` TEXT NOT NULL,
        `enabled` ENUM ('false', 'true') NOT NULL DEFAULT 'false',
        `deleted` ENUM ('false', 'true') NOT NULL DEFAULT 'false',
        `locked` ENUM ('false', 'true') NOT NULL DEFAULT 'false',
        `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`user`) REFERENCES `mythicaldash_users` (`uuid`),
        FOREIGN KEY (`department`) REFERENCES `mythicaldash_departments` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;