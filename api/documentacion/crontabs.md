[<- Volver](README.md)

# Documentación para crontabs

En este apartado se explica utilizar o crear nuevos crontabs en linux, para la ejecución de tareas programadas necesarias del sistema como reportes vía correo, entre otros.

## Referencias
https://www.redeszone.net/2017/01/09/utilizar-cron-crontab-linux-programar-tareas/ \
https://geekytheory.com/programar-tareas-en-linux-usando-crontab

## Requerimientos:

* Poseer permisos de super usuario
* Tener instalado curl en el sistema
* Tener cuidado en la ejecución de los comandos ya que se está trabajando con el sistema operativo

## Pasos para instalación:

* Crear un archivo llamado "**ng_correos_radicados.sh**" e ingresar la siguiente informacion:
```
curl --request GET "http://192.168.8.223/~amosquera/orfeoNg/ng_api/api/web/version1/site/next-to-expire"
curl --request GET "http://192.168.8.223/~amosquera/orfeoNg/ng_api/api/web/version1/site/pqrs-next-expire"
curl --request GET "http://192.168.8.223/~amosquera/orfeoNg/ng_api/api/web/version1/site/expired-pqrs"
curl --request GET "http://192.168.8.223/~amosquera/orfeoNg/ng_api/api/web/version1/site/expired"
```

* Crear un archivo llamado "**ng_reinicio_secuencias.sh**" e ingresar la siguiente informacion:
```
curl --request GET "http://192.168.8.223/~amosquera/orfeoNg/ng_api/api/web/version1/site/bodega-create"
curl --request GET "http://192.168.8.223/~amosquera/orfeoNg/ng_api/api/web/version1/site/reboot-sequences"
```

* Proporcionar permisos de ejecución al archivo creado:
```
sudo chmod +x ng_correos_radicados.sh
sudo chmod +x ng_reinicio_secuencias.sh
```

* Ejecutar el script en consola para abrir el crontab de linux:
```
sudo crontab -e
```
* Ingresar el siguiente código al final del archivo teniendo presente la ruta donde se guardó el archivo bash:
```
#Ejecución de lunes a viernes a las 8:00 am:
00 8 * * 1-5 /ruta_del_archivo/ng_correos_radicados.sh
```
* Ingresar el siguiente código al final del archivo teniendo presente la ruta donde se guardó el archivo bash:
```
#Ejecución anual --> 1 de enero a las 1:01 am:
01 1 1 1 * /ruta_del_archivo/ng_reinicio_secuencias.sh
```

* Verificar las tareas existentes en el crontab de linux:
```
sudo crontab -l
```