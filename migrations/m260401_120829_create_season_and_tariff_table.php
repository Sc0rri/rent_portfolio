<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%season_and_tariff}}`.
 */
class m260401_120829_create_season_and_tariff_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropTable('Tariff');

        // Seasons table
        $this->createTable('Season', [
            'idSeason' => $this->primaryKey(),
            'company_idCompany' => $this->integer(10)->unsigned(),
            'name' => $this->string()->notNull(),
        ]);

        // Date ranges table for seasons (without year)
        $this->createTable('SeasonRange', [
            'idSeasonRange' => $this->primaryKey(),
            'season_idSeason' => $this->integer()->notNull(),
            'startDate' => $this->string(5)->notNull(), // Format MM-DD
            'endDate' => $this->string(5)->notNull(), // Format MM-DD
        ]);
        $this->addForeignKey('fk-season_range-season_id', 'SeasonRange', 'season_idSeason', 'Season', 'idSeason', 'CASCADE');

        // Tariffs table
        $this->createTable('Tariff', [
            'idTariff' => $this->primaryKey(),
            'vehicle_idVehicle' => $this->integer()->unsigned()->notNull(),
            'season_idSeason' => $this->integer()->notNull(),
            'minDays' => $this->integer()->notNull(),
            'maxDays' => $this->integer()->notNull(),
            'price' => $this->decimal(10, 2)->notNull(),
        ]);
        $this->addForeignKey('fk-tariff-season_id', 'Tariff', 'season_idSeason', 'Season', 'idSeason', 'CASCADE');
        $this->addForeignKey('fk-tariff-vehicle_id', 'Tariff', 'vehicle_idVehicle', 'Vehicle', 'idVehicle', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-tariff-vehicle_id', 'Tariff');
        $this->dropForeignKey('fk-tariff-season_id', 'Tariff');
        $this->dropForeignKey('fk-season_range-season_id', 'SeasonRange');

        $this->dropTable('SeasonRange');
        $this->dropTable('Season');
    }
}
