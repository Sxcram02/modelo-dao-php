# modelo-dao-php
    Este respositorio empezo siendo un proyecto de un blog profesional y pienso en el desarrollo de una herramienta reutilizable de código para la funcionalidad de una aplicación web con php respecto a la gestión de rutas, insercción, actualización y muestra de los datos en MySQL por defecto siguiendo una orientación POO y una arquitectura Modelo Vista Controlador.

# Requerimientos
    mod_headers Apache 2.4^ [enable]
    rewrite Apache 2.4^ [enable]
    php 8.0^

# Estrcuturación del proyecto
    La idea es trabajar con una orientación POO por lo tanto dependeremos del objeto Route para gestionar las rutas junto con los métodos, permisos necesarios en las solicitudes HTTP.

# Indice
1. Dependencias necesarias
    - [Gestor de rutas](#trait-gestor-rutas)
    - [Controladores](#interfaz-controller)
    - [Singelton](#interfaz-singelton)
2. [Objeto Route](#objeto-route)
3. [Objeto Request](#objeto-request)
4. [Objeto Session](#objeto-sessión)
5. [Objeto Openssl](#objeto-openssl)
6. [Objeto Colección](#objeto-colección)
7. [Objeto Modelo](#objeto-modelo)
8. [Objeto Database](#objeto-database)
9. [Objeto Consulta](#objeto-consulta)
10. [Objeto Logs](#objeto-logs)
11. [Objeto Filtro](#objeto-filtro)
12. [Objeto Files](#objeto-files)
13. [Objeto Fecha](#objeto-fecha)

# Dependencias
## Trait Gestor Rutas
    Este trait agrupará los métodos necesarios para gestionar las rutas, separandolas y comprobando que sus partes son iguales, además de identificar los parámetros $_GET mandados al servidor.

## Interfaz Controller
    Esta interfaz obligará a todos los objetos que la implementen a contener un método index, create, update y delete que asegura la implementación de un CRUD básico

# Interfaz Singelton
    Esta otra interfaz es 
# Objeto Route
# Objeto Request
# Objeto Session
# Objeto Openssl
# Objeto Colección
# Objeto Modelo
# Objeto Database
# Objeto Consulta
# Objeto Logs
# Objeto Filtro
# Objeto Files
# Objeto Fecha