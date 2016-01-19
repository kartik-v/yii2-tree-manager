/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package yii2-tree-manager
 * @version 1.0.6
 */
 
DROP TABLE IF EXISTS tbl_tree;

CREATE TABLE tbl_tree (
    id            INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY
    COMMENT 'Unique tree node identifier',
    root          INT(11)               DEFAULT NULL
    COMMENT 'Tree root identifier',
    lft           INT(11)      NOT NULL
    COMMENT 'Nested set left property',
    rgt           INT(11)      NOT NULL
    COMMENT 'Nested set right property',
    lvl           SMALLINT(5)  NOT NULL
    COMMENT 'Nested set level / depth',
    name          VARCHAR(60)  NOT NULL
    COMMENT 'The tree node name / label',
    icon          VARCHAR(255)          DEFAULT NULL
    COMMENT 'The icon to use for the node',
    icon_type     TINYINT(1)   NOT NULL DEFAULT '1'
    COMMENT 'Icon Type: 1 = CSS Class, 2 = Raw Markup',
    active        TINYINT(1)   NOT NULL DEFAULT TRUE
    COMMENT 'Whether the node is active (will be set to false on deletion)',
    selected      TINYINT(1)   NOT NULL DEFAULT FALSE
    COMMENT 'Whether the node is selected/checked by default',
    disabled      TINYINT(1)   NOT NULL DEFAULT FALSE
    COMMENT 'Whether the node is enabled',
    readonly      TINYINT(1)   NOT NULL DEFAULT FALSE
    COMMENT 'Whether the node is read only (unlike disabled - will allow toolbar actions)',
    visible       TINYINT(1)   NOT NULL DEFAULT TRUE
    COMMENT 'Whether the node is visible',
    collapsed     TINYINT(1)   NOT NULL DEFAULT FALSE
    COMMENT 'Whether the node is collapsed by default',
    movable_u     TINYINT(1)   NOT NULL DEFAULT TRUE
    COMMENT 'Whether the node is movable one position up',
    movable_d     TINYINT(1)   NOT NULL DEFAULT TRUE
    COMMENT 'Whether the node is movable one position down',
    movable_l     TINYINT(1)   NOT NULL DEFAULT TRUE
    COMMENT 'Whether the node is movable to the left (from sibling to parent)',
    movable_r     TINYINT(1)   NOT NULL DEFAULT TRUE
    COMMENT 'Whether the node is movable to the right (from sibling to child)',
    removable     TINYINT(1)   NOT NULL DEFAULT TRUE
    COMMENT 'Whether the node is removable (any children below will be moved as siblings before deletion)',
    removable_all TINYINT(1)   NOT NULL DEFAULT FALSE
    COMMENT 'Whether the node is removable along with descendants',
    KEY tbl_tree_NK1 (root),
    KEY tbl_tree_NK2 (lft),
    KEY tbl_tree_NK3 (rgt),
    KEY tbl_tree_NK4 (lvl),
    KEY tbl_tree_NK5 (active)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8
    AUTO_INCREMENT = 1;