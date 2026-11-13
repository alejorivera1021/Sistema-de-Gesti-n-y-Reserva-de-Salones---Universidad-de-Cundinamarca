# Changelog - Sistema de Gestión y Reserva de Salones

## [V1.7] - 2026-10-23
### Corregido
- Estilos de botones de acción en tablas (alineación con flexbox)
- Botón "Generar Reporte" y "Generar PDF" con diseño consistente
- Hash de contraseñas compatible con PHP 8.0

### Añadido
- Documentación final para sustentación
- Diagramas UML actualizados

## [V1.6] - 2026-10-22
### Añadido
- Módulo de generación de PDF con Dompdf 2.0.4
- Visualización 3D con A-Frame (WebVR)
- Botón "Generar PDF" en módulo de reportes

## [V1.5] - 2026-10-21
### Añadido
- Módulo de reportes con consultas multitabla (JOIN 3 tablas)
- Filtros por fecha, tipo de usuario y bloque
- Estadísticas de reservas, salidas anticipadas y uso actual

## [V1.4] - 2026-10-20
### Añadido
- Sistema de check-in/check-out en reservas
- Campos check_in y check_out en tabla reservas
- Detección automática de "Salió antes" vs "Salió a tiempo"
- Controlador check_in_out.php

## [V1.3] - 2026-10-19
### Añadido
- Registro de nuevos usuarios (estudiante, profesor, aseo)
- Validación estricta de dominio @ucundinamarca.edu.co
- Asignación automática de departamento para rol aseo

## [V1.2] - 2026-10-18
### Corregido
- Bug crítico: doble reserva en mismo horario (profesor + aseo)
- Validación de overlap con lógica SQL estricta

### Añadido
- Mensajes de error constructivos con contexto

## [V1.1] - 2026-10-17
### Añadido
- Dashboards completos para 4 roles
- Tabla de disponibilidad con filas expandibles
- Reducción a 60 salones con botones por bloque (A, B, C)

## [V1.0] - 2026-10-15
### Añadido
- Base de datos MySQL con 4 tablas principales
- Login funcional con encriptación bcrypt
- Sesiones PHP con redirección por rol
- Conexión PDO segura