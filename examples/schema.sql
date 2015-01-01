DROP TABLE IF EXISTS `tbl_structure`;
CREATE TABLE `tbl_structure` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT "Unique structure identifier",
    `root` INT(11) UNSIGNED DEFAULT NULL COMMENT "Root identifier",
    `lft` INT(11) UNSIGNED NOT NULL COMMENT "Nested set left property",
    `rgt` INT(11) UNSIGNED NOT NULL COMMENT "Nested set right property",
    `lvl` SMALLINT(5) UNSIGNED NOT NULL COMMENT "Nested set level / depth",
    `name` VARCHAR(30) NOT NULL COMMENT "The structure node name",
    `icon` VARCHAR(30) COMMENT "The icon to use for the node",
    `icon_type` TINYINT(1) NOT NULL DEFAULT 1 COMMENT "Icon Type, 1 = CSS Class, 2 = Image",
    PRIMARY KEY (`id`),
    KEY `tbl_structure_NK1` (`root`),
    KEY `tbl_structure_NK2` (`lft`),
    KEY `tbl_structure_NK3` (`rgt`),
    KEY `tbl_structure_NK4` (`lvl`)
);