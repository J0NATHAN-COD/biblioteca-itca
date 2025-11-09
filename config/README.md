# Sistema de Gestión de Préstamos de Computadoras - ITCA FEPADE

Este es un sistema web para gestionar préstamos de computadoras en la biblioteca del ITCA FEPADE.

## Características

- Gestión de préstamos de laptops
- Control de inventario de laptops
- Reportes y estadísticas
- Interfaz responsive con colores institucionales (rojo y blanco)

## Instalación

1. Clona el repositorio:

2. Importa la base de datos:
   - Crea una base de datos llamada `biblioteca_itca`
   - Ejecuta el script SQL proporcionado en `database.sql`

3. Configura la conexión a la base de datos en `config/database.php`

4. Sube los archivos a tu servidor web

## Uso

1. Accede al sistema mediante `index.php`
2. Inicia sesión con las credenciales por defecto:
   - Usuario: `admin` / Contraseña: `password`
   - Usuario: `bibliotecario` / Contraseña: `password`

## Estructura del Proyecto

- `index.php` - Página de inicio de sesión
- `dashboard.php` - Panel principal
- `gestion_prestamos.php` - Gestión de préstamos
- `gestion_laptops.php` - Gestión de laptops
- `reportes.php` - Reportes y estadísticas
- `config/database.php` - Configuración de base de datos
- `logout.php` - Cierre de sesión

## Tecnologías Utilizadas

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- Chart.js (para gráficos)

## Colores

- Rojo principal: #d32f2f
- Rojo oscuro: #b71c1c
- Blanco: #ffffff
- Gris de fondo: #f5f5f5