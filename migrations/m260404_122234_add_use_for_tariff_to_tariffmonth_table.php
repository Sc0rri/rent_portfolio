<?php

use yii\db\Migration;

class m260404_122234_add_use_for_tariff_to_tariffmonth_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('TariffMonth', 'useForCalculate', 'tinyint(1) unsigned DEFAULT 0 AFTER `price`');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('TariffMonth', 'useForCalculate');
    }
}
