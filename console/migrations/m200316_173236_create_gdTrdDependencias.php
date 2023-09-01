<?php

use yii\db\Migration;

/**
 * Class m200316_173236_create_gdTrDependencias
 */
class m200316_173236_create_gdTrdDependencias extends Migration
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

        $this->createTable('cgRegionales', [
            'idCgRegional' => $this->primaryKey(),
            'nombreCgRegional' => $this->string(45)->notNull()->unique(),
            'estadoCgRegional' => $this->integer()->notNull()->defaultValue(10),
            'creacionCgRegional' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),           
            'idNivelGeografico3' => $this->integer()
        ], $tableOptions);

        $this->addForeignKey('fk_cgRegionales_nivelGeografico3', 'cgRegionales', 'idNivelGeografico3', 'nivelGeografico3', 'nivelGeografico3', $onUpdate, $onDelete);

        $this->createTable('gdTrdDependencias', [
            'idGdTrdDependencia' => $this->primaryKey()->comment('idGdTrdDependencia'),
            'nombreGdTrdDependencia' => $this->string(45)->notNull()->comment('Nombre de la dependencia o area funcional según organigrama del cliente'),
            'codigoGdTrdDependencia' => $this->string(5)->notNull()->comment('Código de la dependencia o centro de costos acorde al organigrama del cliente'),
            'direccionGdTrdDependencia' => $this->string(45)->comment('Dirección física de la dependencia o área funcional que se  esta creando'),
            'codigoGdTrdDepePadre' => $this->string(5)->comment('Código de la dependencia o área funcional, dependencia principal'),
            'estadoGdTrdDependencia' => $this->integer()->notNull()->defaultValue(10)->comment('Estado de la dependencia o área funcional'),
            'creacionGdTrdDependencia' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Fecha de creación de la dependencia'),            
            'idCgRegional' => $this->integer()->notNull()->comment('Identificador de la tabla regional, relación entre la tabla')
        ], $tableOptions);

        $this->addForeignKey('fk_gdTrdDependencias_cgRegionales', 'gdTrdDependencias', 'idCgRegional', 'cgRegionales', 'idCgRegional', $onUpdate, $onDelete);

        $this->addColumn('{{%user}}', 'idGdTrdDependencia', $this->integer()->notNull());

        $this->insert('cgRegionales', [
            'nombreCgRegional' =>  'default',
            'idNivelGeografico3' => 525
        ]);

        $this->insert('gdTrdDependencias', [
            'nombreGdTrdDependencia' => 'Default',
            'codigodGdTrdDependencia' => '001',
            'idCgRegional' => 1
        ]);        

        $this->update('{{%user}}', ['idGdTrdDependencia' => 1]);        

        $this->addForeignKey('fk_user_gdTrdDependencias', '{{%user}}', 'idGdTrdDependencia', 'gdTrdDependencias', 'idGdTrdDependencia', $onUpdate, $onDelete);
        
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->droptable('gdTrdDependencias');
        $this->dropTable('cgRegionales');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200316_173236_create_gdTrDependencias cannot be reverted.\n";

        return false;
    }
    */
}
