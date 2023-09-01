<?php

use yii\db\Migration;

/**
 * Class m200325_161002_create_gdTrd
 */
class m200325_161002_create_gdTrd extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }        
        
        $onUpdate = 'RESTRICT';
        $onDelete = 'RESTRICT';

        $this->createTable('gdTrdTiposDocumentales', [
            'idGdTrdTipoDocumental' => $this->primaryKey()->comment('Numero único de tipo documental'),
            'nombreTipoDocumental' => $this->string(45)->comment('nombreTipoDocumental'),
            'diasTramiteTipoDocumental' => $this->integer()->comment('Nombre único del tipo documental'),
            'estadoTipoDocumental' => $this->integer()->defaultValue(10)->comment('Estado 0 Inactivo 10 Activo'),
            'creacionTipoDocumental' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del tipo documental')
        ],
        $tableOptions);

        $this->createTable('gdTrdSeries', [
          'idGdTrdSerie' => $this->primaryKey()->comment('Número único para identificar la serie'),
          'nombreGdTrdSerie' => $this->string(45),
          'codigoGdTrdSerie' => $this->string(45)->unique(),
          'estadoGdTrdSerie' => $this->integer()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
          'creacionGdTrdSerie' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del registro')
        ], 
        $tableOptions);

        $this->createTable('gdTrdSubseries', [
            'idGdTrdSubserie' => $this->primaryKey()->comment('Número único para identificar la subserie'),
            'nombreGdTrdSubserie' => $this->string(45),
            'codigoGdTrdSubserie' => $this->string(45)->unique(),
            'tiempoGestionGdTrdSubserie' => $this->integer(),
            'tiempoCentralGdTrdSubserie' => $this->integer(),
            'disposicionFinalGdTrdSubserie' => $this->string(45),            
            'soporteGdTrdSubserie' => $this->string(45),
            'procedimientoGdTrdSubserie' => $this->string(45),
            'estadoGdTrdSubserie' => $this->integer()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionGdTrdSubserie' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del registro')
          ], 
        $tableOptions);
        
        $this->createTable('gdTrd', [
            'idGdTrd' => $this->primaryKey()->comment('Número único para identificar la tabla'),
            'idGdTrdDependencia' => $this->integer(),
            'idGdTrdSerie' => $this->integer(),
            'idGdTrdSubserie' => $this->integer(),
            'idGdTrdTipoDocumental' => $this->integer(),
            'estadoGdTrd' => $this->integer()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionGdTrdSubserie' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del registro')
        ],
        $tableOptions);

        $this->addForeignKey('fk_gdTrd_gdTrdDependencias', 'gdTrd', 'idGdTrdDependencia', 'gdTrdDependencias', 'idGdTrdDependencia', $onDelete, $onUpdate);
        $this->addForeignKey('fk_gdTrd_gdTrdSeries', 'gdTrd', 'idGdTrdSerie', 'gdTrdSeries', 'idGdTrdSerie', $onDelete, $onUpdate);
        $this->addForeignKey('fk_gdTrd_gdTrdSubseries', 'gdTrd', 'idGdTrdSubserie', 'gdTrdSubseries', 'idGdTrdSubserie', $onDelete, $onUpdate);
        $this->addForeignKey('fk_gdTrd_gdTrdTiposDocumentales', 'gdTrd', 'idGdTrdTipoDocumental', 'gdTrdTiposDocumentales', 'idGdTrdTipoDocumental', $onDelete, $onUpdate);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
       $this->dropTable('gdTrd');
       $this->dropTable('gdTrdSubseries');
       $this->dropTable('gdTrdSeries');
       $this->dropTable('gdTrdTiposDocumentales');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200325_161002_create_gdTrd cannot be reverted.\n";

        return false;
    }
    */
}
