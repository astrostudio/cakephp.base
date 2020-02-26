
CREATE TABLE `task`(
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `alias` VARCHAR(64),
    `shell` VARCHAR(128) not null,
    `action` VARCHAR(128) not null,
    `params` TEXT,
    `code` INT(11),
    `result` TEXT,
    `error` TEXT,
    `progress` INT(11),
    `message` TEXT,
    `timeout` INT(11),
    `step` INT(11),
    `pid` INT(11),
    `created` DATETIME,
    `started` DATETIME,
    `stopped` DATETIME,
    PRIMARY KEY (`id`)
);
