<?php

use yii\db\Migration;

/**
 * Class m200313_203950_create_user_history
 */
class m200313_203950_create_user_history extends Migration
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

        $this->createTable('userHistoryPassword', [
            'idUserHistoryPassword' => $this->primaryKey()->comment('número único de la tabla'),
            'hashUserHistoryPassword' => $this->string(255)->comment('Contraseña definida por el usuario'),
            'idUser' => $this->integer()->comment('ID del usuario'),
            'creacionUserHistoryPassword' => $this->dateTime()->comment('Fecha de creación')
        ], $tableOptions);

        $this->addForeignKey('fk_userHistoryPassword_user', 'userHistoryPassword', 'idUser', '{{%user}}', 'id',  $onUpdate, $onDelete);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('userHistoryPassword');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200313_203950_create_user_history cannot be reverted.\n";

        return false;
    }
    */
}
