<?php

use yii\db\Migration;

/**
 * Class m200326_200731_create_gdTrd_tmp
 */
class m200326_200731_create_gdTrd_tmp extends Migration
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

        $this->createTable('gdTrdTiposDocumentalesTmp', [
            'idGdTrdTipoDocumentalTmp' => $this->primaryKey()->comment('Numero único de tipo documental'),
            'nombreTipoDocumentalTmp' => $this->string(45)->comment('nombreTipoDocumental'),
            'diasTramiteTipoDocumentalTmp' => $this->integer()->comment('Nombre único del tipo documental'),
            'estadoTipoDocumentalTmp' => $this->integer()->defaultValue(10)->comment('Estado 0 Inactivo 10 Activo'),
            'creacionTipoDocumentalTmp' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del tipo documental')
        ],
        $tableOptions);

        $this->createTable('gdTrdSeriesTmp', [
          'idGdTrdSerieTmp' => $this->primaryKey()->comment('Número único para identificar la serie'),
          'nombreGdTrdSerieTmp' => $this->string(45),
          'codigoGdTrdSerieTmp' => $this->string(45)->unique(),
          'estadoGdTrdSerieTmp' => $this->integer()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
          'creacionGdTrdSerieTmp' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del registro')
        ], 
        $tableOptions);

        $this->createTable('gdTrdSubseriesTmp', [
            'idGdTrdSubserieTmp' => $this->primaryKey()->comment('Número único para identificar la subserie'),
            'nombreGdTrdSubserieTmp' => $this->string(45),
            'codigoGdTrdSubserieTmp' => $this->string(45)->unique(),
            'tiempoGestionGdTrdSubserieTmp' => $this->integer(),
            'tiempoCentralGdTrdSubserieTmp' => $this->integer(),
            'disposicionFinalGdTrdSubserieTmp' => $this->string(45),
            'soporteGdTrdSubserieTmp' => $this->string(45),
            'procedimientoGdTrdSubserieTmp' => $this->string(45),
            'estadoGdTrdSubserieTmp' => $this->integer()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionGdTrdSubserieTmp' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del registro')
          ], 
        $tableOptions);
        
        $this->createTable('gdTrdTmp', [
            'idGdTrdTmp' => $this->primaryKey()->comment('Número único para identificar la tabla'),
            'idGdTrdDependenciaTmp' => $this->integer(),
            'idGdTrdSerieTmp' => $this->integer(),
            'idGdTrdSubserieTmp' => $this->integer(),
            'idGdTrdTipoDocumentalTmp' => $this->integer(),
            'estadoGdTrdTmp' => $this->integer()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionGdTrdSubserieTmp' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del registro')
        ],
        $tableOptions);

        $this->createTable('gdTrdDependenciasTmp', [
            'idGdTrdDependenciaTmp' => $this->primaryKey()->comment('idGdTrdDependencia'),
            'nombreGdTrdDependenciaTmp' => $this->string(45)->notNull()->comment('Nombre de la dependencia o area funcional según organigrama del cliente'),
            'codigoGdTrdDependenciaTmp' => $this->string(5)->notNull()->comment('Código de la dependencia o centro de costos acorde al organigrama del cliente'),
            'direccionGdTrdDependenciaTmp' => $this->string(45)->comment('Dirección física de la dependencia o área funcional que se  esta creando'),
            'codigoGdTrdDepePadreTmp' => $this->string(5)->comment('Código de la dependencia o área funcional, dependencia principal'),
            'estadoGdTrdDependenciaTmp' => $this->integer()->notNull()->defaultValue(10)->comment('Estado de la dependencia o área funcional'),
            'creacionGdTrdDependenciaTmp' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Fecha de creación de la dependencia'),            
            'idCgRegionalTmp' => $this->integer()->notNull()->comment('Identificador de la tabla regional, relación entre la tabla')
        ], $tableOptions);


        $this->addForeignKey('fk_gdTrdTmp_gdTrdDependenciasTmp', 'gdTrdTmp', 'idGdTrdDependenciaTmp', 'gdTrdDependenciasTmp', 'idGdTrdDependenciaTmp', $onDelete, $onUpdate);
        $this->addForeignKey('fk_gdTrdTmp_gdTrdSeriesTmp', 'gdTrdTmp', 'idGdTrdSerieTmp', 'gdTrdSeriesTmp', 'idGdTrdSerieTmp', $onDelete, $onUpdate);
        $this->addForeignKey('fk_gdTrdTmp_gdTrdSubseriesTmp', 'gdTrdTmp', 'idGdTrdSubserieTmp', 'gdTrdSubseriesTmp', 'idGdTrdSubserieTmp', $onDelete, $onUpdate);
        $this->addForeignKey('fk_gdTrdTmp_gdTrdTiposDocumentalesTmp', 'gdTrdTmp', 'idGdTrdTipoDocumentalTmp', 'gdTrdTiposDocumentalesTmp', 'idGdTrdTipoDocumentalTmp', $onDelete, $onUpdate);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('gdTrdTmp');
        $this->dropTable('gdTrdSubseriesTmp');
        $this->dropTable('gdTrdSeriesTmp');
        $this->dropTable('gdTrdTiposDocumentalesTmp');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200326_200731_create_gdTrd_tmp cannot be reverted.\n";

        return false;
    }
    */
}
