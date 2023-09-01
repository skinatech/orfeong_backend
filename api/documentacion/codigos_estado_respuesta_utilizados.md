[<- Volver](README.md) \
[Ver códigos de estado de respuesta estandar](codigos_estado_respuesta.md)

# Skinatech v1.0

# Códigos de estado de respuesta HTTP utilizados en el sistema:

Descripción de los códigos de estado de respuesta HTTP utilizados o controlados por el sistema.

## 200 OK
La solicitud ha tenido éxito

## 400 Bad Request / Solicitud incorrecta
Esta respuesta significa que el servidor no pudo interpretar la solicitud dada una sintaxis inválida. \
Por lo general sucede cuando algún parametro requerido se encuentra ausente, ejemplo: La variable encriptada "request" en las peticiones GET 

que necesitan parámetros para la consulta.

# 401 unauthorized / no autorizado (usuario no logueado)
El usuario no logueado en el sistema. (respuesta generada por Yii).
Se debe redirigir al login para obtener un token de sesión

## 404 Not Found
El servidor no pudo encontrar el contenido solicitado.
Ejemplo: Cuando la consulta del modelo principal según los parámetros recibidos no arroja resultados.

## 405 Method Not Allowed
El método solicitado es conocido por el servidor (GET, POST, PUT...) pero ha sido deshabilitado y no puede ser utilizado.

## 422 Unprocessable Entity
La petición estaba bien formada pero no se pudo seguir debido a errores de semántica. (Errores de validación)

## 490 Ususario sin permisos para ejecutar una acción
El usuario logueado no posee permisos de acceso o ejecución sobre la petición realizada.

## 590 Error en desencriptación (Agregado)
La cadena de recibida en la petición HTTP no pudo ser desencriptada