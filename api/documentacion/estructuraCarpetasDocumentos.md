[<- Volver](README.md)

# Skinatech v1.0

# Estructura de directorios para carga de archivos

Especifica la estructura y nomeclatura de nombres de directorios para la carga de archivos en el sistema.

##Estructura de directorio para archivos temporales
```
├── api/web/tmp_masiva/
│   ├── balance/
│   │   ├── tmp_balance_cliente_(idCliente).xlsx
│   ├── homologacion/
│   │   ├── tmp_homologacion_cliente_(idCliente).xlsx
```

##Estructura de directorio para documentos de renta
```
├── api/web/documentos/
│   ├── documentosCliente(idCliente)/
│   │   ├── renta_anio_anterior_cliente_(idCliente).pdf
│   │   ├── renta_(idCliente)/
│   │   │   ├── respuestaPregunta_(idPregunta)_(dateTime).(extenciónArchivo)
```