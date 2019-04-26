<?php

use yii\db\Migration;

/**
 * Class m230417_200117_alter_tree
 */
class m230417_200117_alter_tree extends Migration
{
    const TABLE_NAME = '{{%tree}}';
    
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema(self::TABLE_NAME, true);
        if (!$table) {
            return;
        }
        if (!isset($table->columns['child_allowed'])) {
            $this->addColumn(self::TABLE_NAME, 'child_allowed', $this->boolean()->notNull()->defaultValue(true));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn(self::TABLE_NAME, 'child_allowed');
    }
}
