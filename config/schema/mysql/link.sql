
/*
{{table}}
{{table_link}}
{{id}}
{{sup_id}}
{{sup_id}}
{{item}}
*/

CREATE VIEW table_link_extension(sup_id,sub_id) AS
    SELECT P.sup_id, S.sub_id
        FROM table_link P, table_link S
        WHERE (P.sub_id=S.sup_id)
        GROUP BY P.sup_id, S.sub_id;

CREATE PROCEDURE `table_link_extend_up`(IN `a_sup_id` BIGINT, IN `a_sub_id` BIGINT)
    INSERT INTO table_link(sup_id,sub_id,item)
        SELECT P.sup_id, a_sub_id, 0
            FROM table_link P
                LEFT JOIN table_link L
                    ON (P.sup_id=L.sup_id) AND (L.sub_id=a_sub_id)
            WHERE (P.sub_id=a_sup_id) AND (L.sup_id IS NULL);

CREATE PROCEDURE `table_link_extend_down`(IN `a_sup_id` BIGINT, IN `a_sub_id` BIGINT)
    INSERT INTO table_link(sup_id,sub_id,item)
        SELECT a_sup_id, S.sub_id, 0
            FROM table_link S
                LEFT JOIN table_link L
                    ON (S.sub_id=L.sub_id) AND (L.sup_id=a_sup_id)
            WHERE (S.sup_id=a_sub_id) AND (L.sub_id IS NULL);

DELIMITER ;;
CREATE PROCEDURE `table_link_extend`(IN `a_sup_id` BIGINT, IN `a_sub_id` BIGINT)
    BEGIN
        CALL table_link_extend_down(a_sup_id,a_sub_id);
        CALL table_link_extend_up(a_sup_id,a_sub_id);
    END;;
DELIMITER ;

CREATE PROCEDURE `table_link_extend_all`()
    INSERT INTO table_link(sup_id,sub_id,item)
        SELECT P.sup_id, S.sub_id,0
            FROM table_link P
                JOIN table_link S
                    ON (P.sub_id=S.sup_id)
                LEFT JOIN table_link L
                    ON (P.sup_id=L.sup_id) AND (S.sub_id=L.sub_id)
            WHERE (L.sup_id IS NULL);

CREATE PROCEDURE `table_link_shrink_up`(IN `a_sup_id` BIGINT, IN `a_sub_id` BIGINT)
    DELETE L
        FROM table_link L
            JOIN table_link P
                ON (L.sup_id=P.sup_id) AND (P.sub_id=a_sup_id)
            WHERE (L.sup_id=P.sup_id) AND (L.sub_id=a_sub_id) AND (L.item=0);

CREATE PROCEDURE `table_link_shrink_down`(IN `a_sup_id` BIGINT, IN `a_sub_id` BIGINT)
    DELETE L
        FROM table_link L
            JOIN table_link S
                ON (L.sub_id=S.sub_id) AND (S.sup_id=a_sup_id)
            WHERE (L.sup_id=a_sup_id) AND (L.sub_id=S.sub_id) AND (L.item=0);

DELIMITER ;;
CREATE PROCEDURE `table_link_shrink`(IN `a_sup_id` BIGINT, IN `a_sub_id` BIGINT)
    BEGIN
        CALL table_link_shrink_down(a_sup_id,a_sub_id);
        CALL table_link_shrink_up(a_sup_id,a_sub_id);
    END;;
DELIMITER ;

CREATE PROCEDURE `table_link_shrink_all`()
    DELETE L
        FROM table_link L
        WHERE L.item=0;

DELIMITER ;;
CREATE PROCEDURE `table_link_insert`(
    IN `a_sup_id` BIGINT,
    IN `a_sub_id` BIGINT,
    IN `a_extend_down` BOOLEAN,
    IN `a_extend_up` BOOLEAN
)
    BEGIN
        INSERT INTO table_link (sup_id,sub_id,item) VALUES (a_sup_id,a_sub_d,1);

        IF a_extend_up THEN
            CALL table_link_extend_up(a_sup_id,a_sub_id);
        END IF;

        IF a_extend_down THEN
            CALL table_link_extend_down(a_sup_id,a_sub_id);
        END IF;
    END;;
DELIMITER ;

DELIMITER ;;
CREATE PROCEDURE `table_link_delete`(
    IN `a_sup_id` BIGINT,
    IN `a_sub_id` BIGINT,
    IN `a_shrink_down` BOOLEAN,
    IN `a_shrink_up` BOOLEAN
)
    BEGIN
        IF a_shrink_up THEN
            CALL table_link_shrink_up(a_sup_id,a_sub_id);
        END IF;

        IF a_shrink_down THEN
            CALL table_link_shrink_down(a_sup_id,a_sub_id);
        END IF;

        DELETE L
            FROM table_link L
            WHERE (L.sup_id=a_sup_id) AND (L.sub_id=a_sub_id);
    END;;
DELIMITER ;

/* TAXONOMY */

CREATE TRIGGER `table_taxonomy_after_insert` AFTER INSERT ON `table` FOR EACH ROW
    INSERT INTO `table_link`(sup_id,sub_id,item) VALUES (NEW.id,NEW.id,1);

DELIMITER ;;
CREATE TRIGGET `table_link_taxonomy_after_insert` AFTER INSERT ON `table_link` FOR EACH ROW
    IF NEW.item=1 THEN
        CALL table_link_extend(NEW.sup_id,NEW.sub_id);
    END IF;;

CREATE TRIGGET `table_link_taxonomy_after_delete` AFTER DELETE ON `table_link` FOR EACH ROW
    IF OLD.item=1 THEN
        CALL table_link_shrink(OLD.sup_id,OLD.sub_id);
    END IF;;
DELIMITER ;
