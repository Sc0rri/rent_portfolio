<?php

use yii\db\Migration;

/**
 * Class m250421_141644_add_vehilce_type_to_season
 */
class m260402_141644_add_vehilce_type_to_season extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('Season', 'vehicletype_idVehicleType', 'int(10) unsigned NOT NULL AFTER `name`');
        $this->execute('UPDATE `Season` SET vehicletype_idVehicleType=1');

        $this->addForeignKey(
            'fk-season_vehicletype_idVehicleType',
            'Season',
            'vehicletype_idVehicleType',
            'VehicleType',
            'idVehicleType',
            'RESTRICT',
            'RESTRICT'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-season_vehicletype_idVehicleType', 'Season');
        $this->dropColumn('Season', 'vehicletype_idVehicleType');
    }
}
