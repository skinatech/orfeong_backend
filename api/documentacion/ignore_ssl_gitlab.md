[<- Volver](README.md)

# Skinatech v1.0

## Ignorar certificado ssl gitlab

* <b>Opción 1:</b> Exportar en linux la variable "GIT_SSL_NO_VERIFY" como true para ignorar la verificación de certificados permanentemente
```
export GIT_SSL_NO_VERIFY=true
```

* <b>Opción 2:</b> Realizar cada instrucción con la opción "-c http.sslVerify=false" después del comando git
```
git -c http.sslVerify=false clone https://example.com/path/to/git
```

* <b>Otra solución:</b> 
>Referencia: https://codeday.me/es/qa/20190301/239369.html

Configure git para usar el certificado raíz correcto. Obtenga el certificado de CA raíz del servidor y agréguelo a la configuración de git. Ejecuta esto en el símbolo del sistema (no olvides cd en tu repositorio git)

```
git config http.sslCAinfo ~/certs/cacert.pem
```

Puede elegir ignorar el certificado del servidor (¡bajo su propio riesgo!).
```
git config http.sslVerify false
```

<b>Advertencia de seguridad:</b> Esto es susceptible a los ataques de Man in the Middle. Asegúrese de que este problema de seguridad no sea un problema para usted antes de deshabilitar la verificación de la certificación SSL.