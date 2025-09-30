CREATE TABLE IF NOT EXISTS
	`mythicaldash_invoices` (
		`id` INT NOT NULL AUTO_INCREMENT,
        `user` varchar(36) NOT NULL,
		`service` INT (16) NOT NULL,
		`status` ENUM ('cancelled', 'pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
		`paid_at` DATETIME NULL DEFAULT NULL,
		`due_date` DATETIME NULL DEFAULT NULL,
		`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`payment_gateway` TEXT NULL DEFAULT 'MythicalPay',
		`cancelled_at` DATETIME NULL DEFAULT NULL,
		`refunded_at` DATETIME NULL DEFAULT NULL,
		`deleted` ENUM ('false', 'true') NOT NULL DEFAULT 'false',
		`locked` ENUM ('false', 'true') NOT NULL DEFAULT 'false',
		PRIMARY KEY (`id`),
        FOREIGN KEY (`user`) REFERENCES `mythicaldash_users` (`uuid`),
		FOREIGN KEY (`service`) REFERENCES `mythicaldash_services` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;