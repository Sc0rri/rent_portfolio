<?php

use yii\db\Migration;

/**
 * Adds rate-limiting (allowance) columns to the User table.
 */
class m260405_120000_add_rate_limit_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%User}}', 'allowance', 'int(10) unsigned DEFAULT NULL AFTER `updateTime`');
        $this->addColumn('{{%User}}', 'allowance_updated_at', 'int(10) unsigned DEFAULT 0 AFTER `allowance`');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%User}}', 'allowance');
        $this->dropColumn('{{%User}}', 'allowance_updated_at');
    }
}