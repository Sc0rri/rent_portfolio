<?php

use yii\db\Migration;

class m260403_142321_add_month_tariff_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
// Tariffs table
        $this->createTable('TariffMonth', [
            'idTariffMonth' => $this->primaryKey(),
            'vehicle_idVehicle' => $this->integer()->unsigned()->notNull(),
            'season_idSeason' => $this->integer()->notNull(),
            'price' => $this->decimal(10, 2)->notNull(),
        ]);
        $this->addForeignKey('fk-tariffmonth-season_id', 'TariffMonth', 'season_idSeason', 'Season', 'idSeason', 'CASCADE');
        $this->addForeignKey('fk-tariffmonth-vehicle_id', 'TariffMonth', 'vehicle_idVehicle', 'Vehicle', 'idVehicle', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('TariffMonth');
    }
}
