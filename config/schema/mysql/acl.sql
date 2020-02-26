
CREATE TABLE `acl_aro`(
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64),
    `created` DATETIME DEFAULT NULL,
    `modified` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE (`name`)
);;

CREATE TABLE `acl_aro_link`(
    `acl_aro_id` BIGINT(20) NOT NULL,
    `acl_sub_aro_id` BIGINT(20) NOT NULL,
    `item` SMALLINT NOT NULL DEFAULT 1,
    `created` DATETIME DEFAULT NULL,
    PRIMARY KEY (`acl_aro_id`,`acl_sub_aro_id`),
    FOREIGN KEY (`acl_aro_id`) REFERENCES `acl_aro`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`acl_sub_aro_id`) REFERENCES `acl_aro`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
);;

CREATE TABLE `acl_aco`(
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64),
    `created` DATETIME DEFAULT NULL,
    `modified` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE (`name`)
);;

CREATE TABLE `acl_aco_link`(
    `acl_aco_id` BIGINT(20) NOT NULL,
    `acl_sub_aco_id` BIGINT(20) NOT NULL,
    `item` SMALLINT NOT NULL DEFAULT 1,
    `created` DATETIME DEFAULT NULL,
    PRIMARY KEY (`acl_aco_id`,`acl_sub_aco_id`),
    FOREIGN KEY (`acl_aco_id`) REFERENCES `acl_aco`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`acl_sub_aco_id`) REFERENCES `acl_aco`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
);;

CREATE TABLE `acl_alo`(
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64),
    `created` DATETIME DEFAULT NULL,
    `modified` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE (`name`)
);;

CREATE TABLE `acl_alo_link`(
    `acl_alo_id` BIGINT(20) NOT NULL,
    `acl_sub_alo_id` BIGINT(20) NOT NULL,
    `item` SMALLINT NOT NULL DEFAULT 1,
    `created` DATETIME DEFAULT NULL,
    PRIMARY KEY (`acl_alo_id`,`acl_sub_alo_id`),
    FOREIGN KEY (`acl_alo_id`) REFERENCES `acl_alo`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`acl_sub_alo_id`) REFERENCES `acl_alo`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
);;

CREATE TABLE `acl_aro_aco`(
    `acl_aro_id` BIGINT(20) NOT NULL,
    `acl_aco_id` BIGINT(20) NOT NULL,
    `created` DATETIME DEFAULT NULL,
    PRIMARY KEY (`acl_aro_id`,`acl_aco_id`),
    FOREIGN KEY (`acl_aro_id`) REFERENCES `acl_aro`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`acl_aco_id`) REFERENCES `acl_aco`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
);;

CREATE TABLE `acl_item`(
    `acl_aro_id` BIGINT(20) NOT NULL,
    `acl_aco_id` BIGINT(20) NOT NULL,
    `acl_alo_id` BIGINT(20) NOT NULL,
    `mask` INT NOT NULL DEFAULT 0,
    `created` DATETIME DEFAULT NULL,
    PRIMARY KEY (`acl_aro_id`,`acl_aco_id`,`acl_alo_id`),
    FOREIGN KEY (`acl_aro_id`) REFERENCES `acl_aro`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`acl_aco_id`) REFERENCES `acl_aco`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`acl_alo_id`) REFERENCES `acl_alo`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
);;

CREATE TABLE `acl_access`(
    `acl_aro_id` BIGINT(20) NOT NULL,
    `acl_aco_id` BIGINT(20) NOT NULL,
    `acl_alo_id` BIGINT(20) NOT NULL,
    `mask` INT NOT NULL DEFAULT 0,
    `created` DATETIME,
    PRIMARY KEY (`acl_aro_id`,`acl_aco_id`,`acl_alo_id`),
    FOREIGN KEY (`acl_aro_id`) REFERENCES `acl_aro`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`acl_aco_id`) REFERENCES `acl_aco`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`acl_alo_id`) REFERENCES `acl_alo`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
);;

CREATE VIEW `acl_access_extension`(`acl_aro_id`,`acl_aco_id`,`acl_alo_id`,`mask`) AS
    SELECT `R`.`acl_sub_aro_id`, `C`.`acl_sub_aco_id`, `L`.`acl_sub_alo_id`, BIT_OR(`I`.`mask`)
        FROM `acl_item` `I`, `acl_aro_link` `R`, `acl_aco_link` `C`, `acl_alo_link` `L`
        WHERE `I`.`acl_aro_id`=`R`.`acl_aro_id`
            AND `I`.`acl_aco_id`=`C`.`acl_aco_id`
            AND `I`.`acl_alo_id`=`L`.`acl_alo_id`
        GROUP BY `R`.`acl_sub_aro_id`, `C`.`acl_sub_aco_id`, `L`.`acl_sub_alo_id`;;

CREATE VIEW `acl_access_insertion`(`acl_aro_id`,`acl_aco_id`,`acl_alo_id`,`mask`) AS
    SELECT E.acl_aro_id, E.acl_aco_id, E.acl_alo_id, E.mask
        FROM acl_access_extension E
            LEFT JOIN acl_access A
                ON (E.acl_aro_id=A.acl_aro_id)
                    AND (E.acl_aco_id=A.acl_aco_id)
                    AND (E.acl_alo_id=A.acl_alo_id)
        WHERE A.acl_aro_id IS NULL;;

CREATE VIEW `acl_access_deletion`(`acl_aro_id`,`acl_aco_id`,`acl_alo_id`) AS
    SELECT `A`.`acl_aro_id`, `A`.`acl_aco_id`, `A`.`acl_alo_id`
        FROM `acl_access` `A`
            LEFT JOIN `acl_access_extension` `E`
                  ON (A.acl_aro_id=E.acl_aro_id)
                      AND (A.acl_aco_id=E.acl_aco_id)
                      AND (A.acl_alo_id=E.acl_alo_id)
        WHERE `E`.`acl_aro_id` IS NULL;;

CREATE PROCEDURE `acl_access_update`()
    BEGIN
        DELETE A
            FROM acl_access A
                LEFT JOIN acl_access_extension E
                    ON (A.acl_aro_id=E.acl_aro_id)
                        AND (A.acl_aco_id=E.acl_aco_id)
                        AND (A.acl_alo_id=E.acl_alo_id)
            WHERE E.acl_aro_id IS NULL;

        INSERT INTO acl_access(acl_aro_id,acl_aco_id,acl_alo_id,mask,created)
            SELECT E.acl_aro_id, E.acl_aco_id, E.acl_alo_id, E.mask, CURDATE()
                FROM acl_access_extension E
                    LEFT JOIN acl_access A
                        ON (E.acl_aro_id=A.acl_aro_id)
                            AND (E.acl_aco_id=A.acl_aco_id)
                            AND (E.acl_alo_id=A.acl_alo_id)
                WHERE A.acl_aro_id IS NULL;
    END;;

CREATE TABLE `acl_aro_access`(
    `acl_aro_id` BIGINT(20) NOT NULL,
    `acl_aco_aro_id` BIGINT(20) NOT NULL,
    `acl_alo_id` BIGINT(20) NOT NULL,
    `mask` INT NOT NULL DEFAULT 0,
    `created` DATETIME,
    PRIMARY KEY (`acl_aro_id`,`acl_aco_aro_id`,`acl_alo_id`),
    FOREIGN KEY (`acl_aro_id`) REFERENCES `acl_aro`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`acl_aco_aro_id`) REFERENCES `acl_aro`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`acl_alo_id`) REFERENCES `acl_alo`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
);;

CREATE VIEW `acl_aro_access_extension`(`acl_aro_id`,`acl_aco_aro_id`,`acl_alo_id`,`mask`) AS
    SELECT `A`.`acl_aro_id`, `R`.`acl_aro_id`, `A`.`acl_alo_id`, BIT_OR(`A`.`mask`)
        FROM `acl_access` `A`, `acl_aro_aco` `R`
        WHERE `A`.`acl_aco_id`=`R`.`acl_aco_id`
        GROUP BY `A`.`acl_aro_id`, `R`.`acl_aro_id`, `A`.`acl_alo_id`;;

CREATE VIEW `acl_aro_access_insertion`(`acl_aro_id`,`acl_aco_aro_id`,`acl_alo_id`,`mask`) AS
    SELECT E.acl_aro_id, E.acl_aco_aro_id, E.acl_alo_id, E.mask
        FROM acl_aro_access_extension E
            LEFT JOIN acl_aro_access A
                ON (E.acl_aro_id=A.acl_aro_id)
                    AND (E.acl_aco_aro_id=A.acl_aco_aro_id)
                    AND (E.acl_alo_id=A.acl_alo_id)
        WHERE A.acl_aro_id IS NULL;;

CREATE VIEW `acl_aro_access_deletion`(`acl_aro_id`,`acl_aco_aro_id`,`acl_alo_id`) AS
    SELECT `A`.`acl_aro_id`, `A`.`acl_aco_aro_id`, `A`.`acl_alo_id`
        FROM `acl_aro_access` `A`
            LEFT JOIN `acl_aro_access_extension` `E`
                  ON (A.acl_aro_id=E.acl_aro_id)
                      AND (A.acl_aco_aro_id=E.acl_aco_aro_id)
                      AND (A.acl_alo_id=E.acl_alo_id)
        WHERE `E`.`acl_aro_id` IS NULL;;

CREATE PROCEDURE `acl_aro_access_update`()
    BEGIN
        DELETE A
            FROM acl_aro_access A
                LEFT JOIN acl_aro_access_extension E
                    ON (A.acl_aro_id=E.acl_aro_id)
                        AND (A.acl_aco_aro_id=E.acl_aco_aro_id)
                        AND (A.acl_alo_id=E.acl_alo_id)
            WHERE E.acl_aro_id IS NULL;

        INSERT INTO acl_aro_access(acl_aro_id,acl_aco_aro_id,acl_alo_id,mask,created)
            SELECT E.acl_aro_id, E.acl_aco_aro_id, E.acl_alo_id, E.mask, CURDATE()
                FROM acl_aro_access_extension E
                    LEFT JOIN acl_aro_access A
                        ON (E.acl_aro_id=A.acl_aro_id)
                            AND (E.acl_aco_aro_id=A.acl_aco_aro_id)
                            AND (E.acl_alo_id=A.acl_alo_id)
                WHERE A.acl_aro_id IS NULL;
    END;;

CREATE PROCEDURE `acl_update`()
    BEGIN
        CALL acl_access_update();
        CALL acl_aro_access_update();
    END;;

CREATE TRIGGER `acl_item_after_insert` AFTER INSERT ON `acl_item` FOR EACH ROW
    CALL acl_update();;

CREATE TRIGGER `acl_item_after_delete` AFTER DELETE ON `acl_item` FOR EACH ROW
    CALL acl_update();;

CREATE TRIGGER `acl_aro_link_after_insert` AFTER INSERT ON `acl_aro_link` FOR EACH ROW
    CALL acl_update();;

CREATE TRIGGER `acl_aro_link_after_delete` AFTER DELETE ON `acl_aro_link` FOR EACH ROW
    CALL acl_update();;

CREATE TRIGGER `acl_aco_link_after_insert` AFTER INSERT ON `acl_aco_link` FOR EACH ROW
    CALL acl_update();;

CREATE TRIGGER `acl_aco_link_after_delete` AFTER DELETE ON `acl_aco_link` FOR EACH ROW
    CALL acl_update();;

CREATE TRIGGER `acl_alo_link_after_insert` AFTER INSERT ON `acl_alo_link` FOR EACH ROW
    CALL acl_update();;

CREATE TRIGGER `acl_alo_link_after_delete` AFTER DELETE ON `acl_alo_link` FOR EACH ROW
    CALL acl_update();;

CREATE TRIGGER `acl_aro_aco_after_insert` AFTER INSERT ON `acl_aro_aco` FOR EACH ROW
    CALL acl_update();;

CREATE TRIGGER `acl_aro_aco_after_delete` AFTER DELETE ON `acl_aro_aco` FOR EACH ROW
    CALL acl_update();;

CREATE TABLE `acl_item_schedule`(
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `acl_aro_id` BIGINT(20) NOT NULL,
    `acl_aco_id` BIGINT(20) NOT NULL,
    `acl_alo_id` BIGINT(20) NOT NULL,
    `mask` INT NOT NULL DEFAULT 0,
    `start` DATETIME,
    `stop` DATETIME,
    `created` DATETIME DEFAULT NULL,
    `modified` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`acl_aro_id`) REFERENCES `acl_aro`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`acl_aco_id`) REFERENCES `acl_aco`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`acl_alo_id`) REFERENCES `acl_alo`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
);;

CREATE VIEW `acl_item_insertion`(`acl_aro_id`,`acl_aco_id`,`acl_alo_id`,`mask`) AS
    SELECT `S`.`acl_aro_id`, `S`.`acl_aco_id`, `S`.`acl_alo_id`, BIT_OR(`S`.`mask`)
        FROM `acl_item_schedule` `S`
            LEFT JOIN `acl_item` `I`
                ON (`S`.`acl_aro_id`=`I`.`acl_aro_id`)
                    AND (`S`.`acl_aco_id`=`I`.`acl_aco_id`)
                    AND (`S`.`acl_alo_id`=`I`.`acl_alo_id`)
        WHERE (`I`.`acl_aro_id` IS NULL)
             AND ((`S`.`start` IS NULL) OR ((`S`.`start`<=CURDATE())))
             AND ((`S`.`stop` IS NULL) OR ((`S`.`stop`>=CURDATE())))
        GROUP BY `S`.`acl_aro_id`, `S`.`acl_aco_id`, `S`.`acl_alo_id`;;

CREATE VIEW `acl_item_deletion`(`acl_aro_id`,`acl_aco_id`,`acl_alo_id`) AS
    SELECT `I`.`acl_aro_id`, `I`.`acl_aco_id`, `I`.`acl_alo_id`
        FROM `acl_item` `I`
            LEFT JOIN `acl_item_schedule` `S`
                ON (`I`.`acl_aro_id`=`S`.`acl_aro_id`)
                    AND (`I`.`acl_aco_id`=`S`.`acl_aco_id`)
                    AND (`I`.`acl_alo_id`=`S`.`acl_alo_id`)
                    AND ((`S`.`start` IS NULL) OR ((`S`.`start`<=CURDATE())))
                    AND ((`S`.`stop` IS NULL) OR ((`S`.`stop`>=CURDATE())))
        WHERE (`S`.`id` IS NULL);;

CREATE PROCEDURE `acl_item_update`()
    BEGIN
        DELETE `I`
            FROM `acl_item` `I`
                LEFT JOIN `acl_item_schedule` `S`
                ON (`I`.`acl_aro_id`=`S`.`acl_aro_id`)
                    AND (`I`.`acl_aco_id`=`S`.`acl_aco_id`)
                    AND (`I`.`acl_alo_id`=`S`.`acl_alo_id`)
                    AND ((`S`.`start` IS NULL) OR ((`S`.`start`<=CURDATE())))
                    AND ((`S`.`stop` IS NULL) OR ((`S`.`stop`>=CURDATE())))
            WHERE `S`.`id` IS NULL;

        INSERT INTO `acl_item`(`acl_aro_id`,`acl_aco_id`,`acl_alo_id`,`mask`,`created`)
            SELECT `S`.`acl_aro_id`, `S`.`acl_aco_id`, `S`.`acl_alo_id`, BIT_OR(`S`.`mask`), CURDATE()
                FROM `acl_item_schedule` `S`
                    LEFT JOIN `acl_item` `I`
                        ON (`S`.`acl_aro_id`=`I`.`acl_aro_id`)
                            AND (`S`.`acl_aco_id`=`I`.`acl_aco_id`)
                            AND (`S`.`acl_alo_id`=`I`.`acl_alo_id`)
                WHERE (`I`.`acl_aro_id` IS NULL)
                     AND ((`S`.`start` IS NULL) OR ((`S`.`start`<=CURDATE())))
                     AND ((`S`.`stop` IS NULL) OR ((`S`.`stop`>=CURDATE())))
                GROUP BY `S`.`acl_aro_id`, `S`.`acl_aco_id`, `S`.`acl_alo_id`;
    END;;

CREATE EVENT `acl_item_event`
    ON SCHEDULE EVERY 10 MINUTE
    DO
        CALL acl_item_update();;

