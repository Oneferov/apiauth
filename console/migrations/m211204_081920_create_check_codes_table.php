<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%check_codes}}`.
 */
class m211204_081920_create_check_codes_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%check_codes}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull(),
            'code' => $this->integer()->notNull(),
            'option' => $this->string()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%check_codes}}');
    }
}
