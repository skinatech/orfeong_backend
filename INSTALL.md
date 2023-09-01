![OrfeoNG Logo](https://orfeolibre.org/inicio/wp-content/uploads/2022/07/orfeo_ng_skinatech.png)

# OrfeoNG Backend

## La infraestructura lógica de OrfeoNG

## Requerimientos:

* Versión de php: >= php 8.1, Recomendada: phpi 8.1

## Pasos para instalación:

* Clonar el proyecto desde el repositorio en gitlab:
```
$ git clone https://aruba.skinatech.com/Orfeo-NG/ng_backend.git
```
Si tienes problemas con certificados en la descarga del proyecto visita esta sección: [Ignorar certificado ssl gitlab](api/documentacion/ignore_ssl_gitlab.md)

* Crear el alchivo de configuración /common/config/main-local.php como una copia a partir del archivo de ejemplo: /common/config/main-local.php.dist en la ruta /common/config/
```
/common/config/main-local.php
```
En este se deben configurar las credenciales de la conexión a base de datos y el envío de correos

* Instalar los paquetes de terceros mediante composer:
```
composer install
``` 

* Ejecutar el comando de inicio de YII:
```
php init
```

Seleccionar 0 ó 1 de acuerdo al entorno que se desee utilizar:
```
Yii Application Initialization Tool v1.0

Which environment do you want the application to be initialized in?

  [0] Development
  [1] Production

  Your choice [0-1, or "q" to quit] 
```

Escribir yes y pulsar la tecla Enter para confirmar el paso:
```
Initialize the application under 'Development' environment? [yes|no] yes
```

* Confirmar que las variables de entorno se encuentren correctamente configuradas de acuerdo al entorno con el se esté trabajando [desarrollo | producción]

Archivo: api/web/index.php
```
defined('YII_DEBUG') or define('YII_DEBUG', true); // [true | false]
defined('YII_ENV') or define('YII_ENV', 'dev'); // [dev | prod]
```

* Crear archivos de configuración a partir de los archivos de ejemplo ".dist"

Yii necesita de archivos de configuración y parametros para poder conectarce con bases de datos, correos, entre otros
los cuales no se encuentran agregados en el repositorio por temas de seguridad de contraseñas. \
Para esto se anexan archivos con la extension .dist de los cuales se debe crear una copia con el mismo nombre exceptuando la extensión .dist
y configurar las contraseñas de acceso segun el ambiente con el que se desea trabajar


Archivos:
```
api/config/params-local.php.dist
common/config/main-local.php.dist
common/config/params-local.php.dist
```

* Configurar variables de entorno:

Se deben verificar los siguientes archivos para evitar la escritura de logs que genera aplicaión de acuerdo al ambiente con el que se trabaja (desarrollo o producción):

```
api/tests/_bootstrap.php
modified:   api/web/index-test.php
modified:   api/web/index.php
```

/////EN CONSTRUCCIÓN

## Documentación de microservicios
https://programar.cloud/post/como-documentar-un-microservicio-con-spring-rest-docs/


[Códigos de respuesta HTTP utilizados](api/documentacion/codigos_estado_respuesta_utilizados.md) \
[Códigos de respuesta HTTP estandar](api/documentacion/codigos_estado_respuesta.md) \
[Estructura de directorios para carga de archivos](api/documentacion/estructuraCarpetasDocumentos.md) \
[Documentación de crontabs](api/documentacion/crontabs.md)
