<?php

use yii\db\Migration;

/**
 * Class m200313_131955_init2
 */
class m200313_131955_insert_datos_iniciales extends Migration
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

        //Insert into rolesModulosOperaciones
        $this->insert('rolesModulosOperaciones', [
            'nombreRolModuloOperacion' => 'Gestión de usuarios',
            'classRolModuloOperacion' => 'person_outline',
            'rutaRolModuloOperacion' => 'users',
            'ordenModuloOperacion' => 2,
            'estadoRolModuloOperacion' => 10
        ]);

        $this->insert('rolesModulosOperaciones', [
            'nombreRolModuloOperacion' => 'GGestión de mi cuenta',
            'classRolModuloOperacion' => 'person',
            'rutaRolModuloOperacion' => 'customers',
            'ordenModuloOperacion' => 1,
            'estadoRolModuloOperacion' => 10
        ]);

        $this->insert('rolesModulosOperaciones', [
            'nombreRolModuloOperacion' => 'Configuración',
            'classRolModuloOperacion' => 'build',
            'rutaRolModuloOperacion' => 'setting',
            'ordenModuloOperacion' => 2,
            'estadoRolModuloOperacion' => 10
        ]);

        //Insert into rolesOperaciones      
        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%user%index',
            'aliasRolOperacion' => 'Consultar usuarios',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%user%create',
            'aliasRolOperacion' => 'Crear usuarios',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%user%update',
            'aliasRolOperacion' => 'Actualizar usuarios',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%user%view',
            'aliasRolOperacion' => 'Ver detalles usuarios',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%user%status',
            'aliasRolOperacion' => 'Cambiar estados usuarios.',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%roles%index',
            'aliasRolOperacion' => 'Perfiles',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%roles%update',
            'aliasRolOperacion' => 'Actualizar perfiles',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%roles%change-status',
            'aliasRolOperacion' => 'Cambiar estados perfiles',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%roles%view',
            'aliasRolOperacion' => 'Ver detalles perfiles',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%roles%index-one',
            'aliasRolOperacion' => 'Crear perfiles',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%roles%view-operaciones',
            'aliasRolOperacion' => 'Consultar operaciones a los que tiene permiso un rol',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%roles%create',
            'aliasRolOperacion' => 'Crear perfiles',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%index',
            'aliasRolOperacion' => 'Consultar usuarios',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%users%user-tipo%index',
            'aliasRolOperacion' => 'Consultar tipos de usuario',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%roles%index-list',
            'aliasRolOperacion' => 'Lista de perfiles',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);     

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%roles-operaciones%index',
            'aliasRolOperacion' => 'Administrar operacion',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%roles-operaciones%create',
            'aliasRolOperacion' => 'Crear operaciones',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        $this->insert('rolesOperaciones', [
            'nombreRolOperacion' => 'version1%roles%roles-operaciones%update',
            'aliasRolOperacion' => 'Actualizar operacione',
            'moduloRolOperacion' => 'Gestión de usuarios',
            'estadoRolOperacion' => 10,
            'idRolModuloOperacion' => 1
        ]);

        //insert into roles
        $this->insert('roles', [
            'nombreRol' => 'Administardor',   
            'estadoRol' => 10,
        ]);

        $this->insert('roles', [
            'nombreRol' => 'Cliente',   
            'estadoRol' => 10,
        ]);
        
        $this->insert('roles', [
            'nombreRol' => 'Perfil',   
            'estadoRol' => 10,
        ]);
    
        //insert into tiposArchivos
        $this->insert('tiposArchivos', [
            'tipoArchivo' => 'xlsx',
            'estadoTipoArchivo' => 10         
        ]);

        $this->insert('tiposArchivos', [
            'tipoArchivo' => 'pdf',
            'estadoTipoArchivo' => 10         
        ]);

        $this->insert('tiposArchivos', [
            'tipoArchivo' => 'xls',
            'estadoTipoArchivo' => 10         
        ]);

        //insert into tiposIdentificacion
        $this->insert('tiposIdentificacion', [
            'nombreTipoIdentificacion' => 'Cédula de Ciudadanía',
            'estadoTipoIdentificacion' => 10         
        ]);

        $this->insert('tiposIdentificacion', [
            'nombreTipoIdentificacion' => 'Cédula de Extrangería',
            'estadoTipoIdentificacion' => 10         
        ]);

        $this->insert('tiposIdentificacion', [
            'nombreTipoIdentificacion' => 'Nit',
            'estadoTipoIdentificacion' => 10         
        ]);

        //isnsert into tiposPersona
        $this->insert('tiposPersona', [
            'tipoPersona' => 'Persona Jurídica',
            'estadoPersona' => 10         
        ]);

        $this->insert('tiposPersona', [
            'tipoPersona' => 'Gran Contribuyente',
            'estadoPersona' => 10         
        ]);

        //insert into userTipo
        $this->insert('userTipo', [
            'nombreUserTipo' => 'Administrador',
            'estadoUserTipo' => 10         
        ]);

        $this->insert('userTipo', [
            'nombreUserTipo' => 'Cliente',
            'estadoUserTipo' => 10         
        ]);

        //insert into rolesTiposOperaciones
        $this->insert('rolesTiposOperaciones',[
            'idRol' => 2,
            'idRolOperacion' => 1
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 2
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 3
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 4
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 5
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 6
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 7
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 8
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 9
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 10
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 11
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 12
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 13
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 14
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 15
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 16
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 3,
            'idRolOperacion' => 17
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 1
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 2
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 3
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 4
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 5
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 6
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 7
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 8
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 9
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 10
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 11
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 12
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 13
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 14
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 15
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 16
        ]);

        $this->insert('rolesTiposOperaciones',[
            'idRol' => 1,
            'idRolOperacion' => 17
        ]);


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200313_131955_init2 cannot be reverted.\n";

        return false;
    }
    */
}
