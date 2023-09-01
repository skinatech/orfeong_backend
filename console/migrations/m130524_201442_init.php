<?php

use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }        
        
        $onUpdate = 'RESTRICT';
        $onDelete = 'RESTRICT';
 
        $this->createTable('roles', [
            'idRol' => $this->primaryKey()->comment('Número único que identifica la cantida de roles en el sistema'),
            'nombreRol' => $this->string(40)->notNull()->unique()->comment('nombreRol'),
            'estadoRol' => $this->integer()->notNull()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionRol' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Fecha de creación')
        ], $tableOptions);
  
        $this->createTable('rolesModulosOperaciones', [            
            'idRolModuloOperacion' => $this->primaryKey()->comment('Número único de la tabla'),
            'nombreRolModuloOperacion' => $this->string(45)->notNull()->comment('Nombre del modulo o menu a visualizar'),
            'classRolModuloOperacion' => $this->string(40)->notNull()->comment('Icono del modulo en el menú'),
            'rutaRolModuloOperacion' => $this->string(40)->notNull()->comment('Ruta donde se dirije el modulo en frontend'),
            'ordenModuloOperacion' => $this->integer()->defaultValue(999)->comment('Orden del menú del sistema'),
            'estadoRolModuloOperacion' => $this->integer()->notNull()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionModuloOperaciones' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('creacionModuloOperaciones')            
        ], $tableOptions);

        $this->createTable('rolesOperaciones', [
            'idRolOperacion' => $this->primaryKey()->comment('Número único de la tabla'),
            'nombreRolOperacion' => $this->string(80)->notNull()->unique()->comment('Ruta de la acción que se ejecuta en backend'),
            'aliasRolOperacion' => $this->string(80)->notNull()->comment('Nombre de la acción o proceso a realizar'),
            'moduloRolOperacion' => $this->string(80)->notNull()->comment('Grupo o modulo donde se ejecuta la acción'),
            'estadoRolOperacion' => $this->integer()->notNull()->defaultValue(10)->comment('0 Inactiva - 10 Activa'),
            'creacionRolOperacion' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Fecha creación de la operación'),
            'idRolModuloOperacion' => $this->integer()->notNull()->comment('Número que indica el modulo de la operación')
        ], $tableOptions);

        $this->createTable('rolesTiposOperaciones', [
            'idRolTipoOperacion' => $this->primaryKey()->comment('Número único de la tabla'),
            'idRol' => $this->integer()->notNull()->comment('Número que indica el rol'),
            'idRolOperacion' => $this->integer()->notNull()->comment('Número que indica la operación')
        ], $tableOptions);

        $this->addForeignKey('fk_rolesOperaciones_roles', 'rolesOperaciones', 'idRolModuloOperacion', 'rolesModulosOperaciones', 'idRolModuloOperacion',  $onUpdate, $onDelete );
       
        $this->addForeignKey('fk_rolesTiposOperaciones_roles', 'rolesTiposOperaciones', 'idRol', 'roles', 'idRol',  $onUpdate, $onDelete );
        $this->addForeignKey('fk_rolesTiposOperaciones_rolesOperaciones', 'rolesTiposOperaciones', 'idRolOperacion', 'rolesOperaciones', 'idRolOperacion',  $onUpdate, $onDelete );

        $this->createTable('userTipo', [
            'idUserTipo' => $this->primaryKey()->comment('Número único que indica la cantidad de tipos de usuario registrados en el sistema'),
            'nombreUserTipo' => $this->string(80)->notNull()->comment('Nombre del tipo de usuario'),
            'estadoUserTipo' => $this->integer()->notNull()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionUserTipo' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Fecha de creación')
        ], $tableOptions);

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey()->notNull()->comment('Número único que inca la cantidad de usuarios registrados en el sistema'),
            'username' => $this->string(255)->notNull()->unique()->comment('Usuario o correo con que el usuario se registra'),
            'auth_key' => $this->string(32)->notNull()->comment('auth_key'),
            'password_hash' => $this->string(255)->notNull()->comment('Contraseña definida por el usuario'),
            'password_reset_token' => $this->string(255)->unique()->comment('almacena codigo de restauración de contraseña'),
            'email' => $this->string(255)->notNull()->unique()->comment('Correo que ingresan al sistema'),
            'status' => $this->smallInteger()->notNull()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'created_at' => $this->integer()->notNull()->comment('Fecha de creación convertida en número'),
            'updated_at' => $this->integer()->notNull()->comment('Fecha de modificación convertida en número'),
            'fechaVenceToken' => $this->dateTime()->notNull()->comment('Fecha en la que se vence el token para el usuario'),
            'idRol' => $this->integer()->notNull()->comment('Número que indica el rol del usuario'),
            'idUserTipo' => $this->integer()->notNull()->comment('Número que indica el tipo de usuario'),
            'accessToken' => $this->string(255)->notNull()->comment('accessToken'),
            'intentos' => $this->integer()->notNull()->defaultValue(0)->comment('Intentos'),
            'ldap' => $this->integer()->notNull()->defaultValue(0)->comment('0 false y 10 true')
        ], $tableOptions);

        $this->addForeignKey('fk_user_roles', '{{%user}}', 'idRol', 'roles', 'idRol',  $onUpdate, $onDelete );
        $this->addForeignKey('fk_user_userTipo', '{{%user}}', 'idUserTipo', 'userTipo', 'idUserTipo',  $onUpdate, $onDelete );

        $this->createTable('log', [
            'idLog' => $this->primaryKey(),
            'idUser' => $this->integer()->notNull(),
            'userNameLog' => $this->string(80)->notNull(),
            'fechaLog' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'ipLog' => $this->string(40)->notNull(),
            'moduloLog' => $this->string(80)->notNull(),
            'eventoLog' => $this->text()->notNull(),
            'antesLog' => $this->text(),
            'despuesLog' => $this->text()
        ],  $tableOptions);

        $this->addForeignKey('fk_log_user', 'log', 'idUser', '{{%user}}', 'id',  $onUpdate, $onDelete );        
        
        $this->createTable('tiposIdentificacion', [
            'idTipoIdentificacion' => $this->primaryKey()->comment('Número único para identificar los tipos de identificación'),
            'nombreTipoIdentificacion' => $this->string(50)->notNull()->unique()->comment('Nombre del tipo de identificación'),
            'estadoTipoIdentificacion' => $this->integer()->notNull()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionTipoIdentificacion' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del registro')
        ],  $tableOptions);

        $this->createTable('userDetalles', [
            'idUserDetalles' => $this->primaryKey()->comment('Número único que identifica la cantidad de usuarios registrados en el sistema'),
            'idUser' => $this->integer()->notNull()->unique()->comment('Número que indica el usuario'),
            'nombreUserDetalles' => $this->string(80)->notNull()->comment('Nombres del usuario'),
            'apellidoUserDetalles' => $this->string(80)->notNull()->comment('Apellidos del usuario'),
            'cargoUserDetalles' => $this->string(80)->notNull()->comment('Cargo del usuario'),
            'creacionUserDetalles' => $this->integer()->notNull()->comment('Fecha en que se creo el usuario'),
            'idTipoIdentificacion' => $this->integer()->notNull()->comment('Número que indica el tipo de identificación'),
            'documento' => $this->string(20)->notNull()->unique()->comment('Número de identificación o documeto'),
            'estadoUserDetalles' => $this->integer()->notNull()->defaultValue(10)->comment('0 Inactivo - 10 Activo')
        ], $tableOptions);
        
        $this->addForeignKey('fk_userDetalles_user', 'userDetalles', 'idUser', '{{%user}}', 'id',  $onUpdate, $onDelete );
        $this->addForeignKey('fk_userDetalles_tiposIdentificacion', 'userDetalles', 'idTipoIdentificacion', 'tiposIdentificacion', 'idTipoIdentificacion',  $onUpdate, $onDelete );
    
        $this->createTable('tiposArchivos', [
            'idTipoArchivo' => $this->primaryKey()->comment('Campo primario de la tabla'),
            'tipoArchivo' => $this->string(80)->comment('Nombre del tipo de archivo'),
            'estadoTipoArchivo' => $this->integer()->comment('status del registro'),
            'creacionTipoArchivo' => $this->dateTime()->comment('creacionTipoArchivo')
        ], $tableOptions);

        $this->createTable('tiposPersona', [
            'idTipoPersona' => $this->primaryKey()->comment('ID primario de la tabla tiposPersonas'),
            'tipoPersona' => $this->string(50)->notNull()->comment('Tipo de persona'),
            'estadoPersona' => $this->integer()->notNull()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionPersona' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del registro')
        ], $tableOptions);        

        $this->createTable('nivelGeografico1', [
            'nivelGeografico1' => $this->primaryKey()->comment('Identificador único del país'),
            'nomNivelGeografico1' => $this->string(50)->notNull()->comment('Nombre del país'),
            'cdi' => $this->string(20)->notNull()->comment('CDI'),
            'estadoNivelGeografico1' => $this->integer()->notNull()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionNivelGeografico1' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del registro')
        ], $tableOptions);

        $this->createTable('nivelGeografico2', [
            'nivelGeografico2' => $this->primaryKey()->comment('Identificador único del nivel 2'),
            'idNivelGeografico1' => $this->integer()->notNull()->comment('Número que indica el país al que pertenece'),
            'nomNivelGeografico2' => $this->string(50)->notNull()->comment('Nombre del nivel geografico 2 ( Departamento )'),
            'estadoNivelGeografico2' => $this->integer()->notNull()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionNivelGeografico2' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del registro')
        ], $tableOptions);

        $this->addForeignKey('fk_nivelGeografico2_nivelGeografico1', 'nivelGeografico2', 'idNivelGeografico1', 'nivelGeografico1', 'nivelGeografico1',  $onUpdate, $onDelete);
    
        $this->createTable('nivelGeografico3', [
            'nivelGeografico3' => $this->primaryKey()->comment('Número único de la tabla'),
            'idNivelGeografico2' => $this->integer()->notNull()->comment('Número del departmaneto al que pertenece'),
            'nomNivelGeografico3' => $this->string(50)->notNull()->comment('Nombre del nivel geografico 3 ( Ciudad )'),
            'estadoNivelGeografico3' => $this->integer()->notNull()->defaultValue(10)->comment('0 Inactivo - 10 Activo'),
            'creacionNivelGeografico3' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Creación del registro')

        ], $tableOptions);

        $this->addForeignKey('fk_nivelGeografico3_nivelGeografico2', 'nivelGeografico3', 'idNivelGeografico2', 'nivelGeografico2', 'nivelGeografico2',  $onUpdate, $onDelete);
               
    }
    
    public function down()
    {
        $this->dropTable('userDetalles');
        $this->dropTable('tiposIdentificacion');
        $this->dropTable('log');
        $this->dropTable('{{%user}}');
        $this->dropTable('userTipo');
        $this->dropTable('rolesTiposOperaciones');
        $this->dropTable('rolesOperaciones');
        $this->dropTable('rolesModulosOperaciones');
        $this->dropTable('roles');
        $this->dropTable('nivelGeografico3');
        $this->dropTable('nivelGeografico2');
        $this->dropTable('nivelGeografico1');      
        $this->dropTable('tiposPersona');
        $this->dropTable('tiposArchivos');            
    }
}
