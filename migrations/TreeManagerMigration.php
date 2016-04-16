<?php

namespace kartik\tree\migrations;

use yii\db\cubrid\Schema;
use yii\db\Migration;

class TreeManagerMigration extends Migration
{
    public function tableName() {
        return '{{%tree}}';
    }
    
    public function up()
    {
        $this->createTable($this->tableName(), [
            'id' => Schema::TYPE_PK,
            'root' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
            'lft' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
            'rgt' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
            'lvl' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
            'name' => Schema::TYPE_STRING . ' DEFAULT NULL',
            'icon' => Schema::TYPE_STRING . ' DEFAULT NULL',
            'icon_type' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 1',
            'selected' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT FALSE',
            'active' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT TRUE',
            'disabled' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT FALSE',
            'readonly' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT FALSE',
            'collapsed' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT FALSE',
            'visible' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT TRUE',
            'movable_u' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT TRUE',
            'movable_d' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT TRUE',
            'movable_l' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT TRUE',
            'movable_r' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT TRUE',
            'removable' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT TRUE',
            'removable_all' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT FALSE'
        ], '
            ENGINE = InnoDB
            DEFAULT CHARSET = utf8
            AUTO_INCREMENT = 1
        ');

        $this->createIndex('TREE_NK1', $this->tableName(), 'root');
        $this->createIndex('TREE_NK2', $this->tableName(), 'lft');
        $this->createIndex('TREE_NK3', $this->tableName(), 'rgt');
        $this->createIndex('TREE_NK4', $this->tableName(), 'lvl');
        $this->createIndex('TREE_NK5', $this->tableName(), 'active');
    }

    public function down()
    {
        $this->dropTable($this->tableName());
    }
}
