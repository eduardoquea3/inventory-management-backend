# Checklist de cumplimiento — Prueba técnica backend

Fuente: [`Backend-Guia-Prueba-Tecnica.md`](Backend-Guia-Prueba-Tecnica.md). Los ítems marcados se consideran implementados según inspección del código/documentación disponible; no implican que todas las verificaciones de ejecución hayan sido repetidas.

## Estado inicial y migración

- [x] Revisar stack y documentar versión final: Laravel 12 / PHP 8.2 (`composer.json`, `README.md`).
- [ ] Documentar diagnóstico inicial del legacy, versiones/problemas de partida y mediciones baseline.
- [ ] Crear y documentar rama de trabajo de la migración.
- [ ] Documentar estrategia y decisiones de migración/compatibilidad.

## Repositorios y Docker

- [ ] Mantener backend y frontend como repositorios independientes, ambos con README y `.env.example`.
- [ ] Crear/documentar el repositorio frontend independiente.
- [ ] Configurar Compose principal en backend para levantar backend, servidor web, MySQL y frontend con contexto `../frontend`.
- [ ] Añadir Dockerfile frontend en el repositorio frontend.
- [x] Definir servicios Docker para aplicación PHP y MySQL (`docker-compose.yml`).
- [ ] Configurar Nginx/servidor web y URL API conforme a entrega (hoy se usa el servidor de desarrollo de Laravel).
- [x] Configurar persistencia de datos MySQL y conexión por nombre de servicio (`mysql`).
- [ ] Revisar que todas las variables y credenciales de `.env.example` coincidan con Compose y estén completas.
- [ ] Garantizar arranque desde cero incluyendo instalación de dependencias dentro de Docker; comprobar el flujo completo documentado.
- [ ] Documentar clonación como carpetas hermanas `backend/` y `frontend/` en ambos README.
- [x] Documentar comandos Docker de inicio, migraciones, seed y pruebas para el backend (`README.md`).
- [ ] Levantar backend, frontend y MySQL desde cero siguiendo README.

## Arquitectura y API

- [x] Migrar a Laravel 11+ y PHP 8.2+ compatibles (Laravel 12 / PHP 8.2).
- [ ] Separar responsabilidades usando Controllers, Form Requests, Services y Resources (Controllers existen; Requests/Services/Resources aún son placeholders).
- [ ] Estandarizar respuestas JSON de éxito, errores y paginación, y documentar el contrato para frontend.
- [x] Implementar autenticación API con token y middleware (`AuthController`, `LegacyTokenAuth`; validar si satisface el mecanismo acordado con frontend).
- [x] Implementar endpoints de login, logout y usuario autenticado.
- [x] Implementar CRUD de categorías y productos.
- [x] Implementar listado y registro de movimientos de stock.
- [x] Implementar endpoint de salud de aplicación/base de datos.
- [x] Implementar endpoint de dashboard con KPIs/últimos movimientos; documentar contrato y decidir cache.
- [x] Incluir documentación OpenAPI/Swagger (`l5-swagger`).
- [ ] Verificar/documentar URL real de Swagger.
- [ ] Habilitar Laravel Telescope solo en local/desarrollo y documentar URL (no se observa instalado/configurado).
- [ ] Configurar CORS para el origen real del frontend.

## Datos, validaciones y reglas de negocio

- [x] Definir migraciones para usuarios, categorías, productos y movimientos de stock.
- [ ] Verificar relaciones y restricciones referenciales completas entre categorías, productos y movimientos.
- [ ] Completar validaciones mediante Form Requests para CRUD, precios, cantidades y referencias.
- [ ] Implementar categorías activas/inactivas y validar el estado.
- [ ] Garantizar rechazo de salidas superiores al stock disponible.
- [ ] Ejecutar movimiento de stock en transacción con bloqueo del producto para evitar carreras.
- [ ] Evitar cambios parciales ante fallos de movimiento.
- [ ] Auditar create/update/delete y movimientos de stock con actor, acción, entidad, fecha y cambios.
- [ ] Definir/documentar política de borrado de categorías con productos y productos con historial.
- [ ] Definir/documentar umbral de bajo stock.

## Optimización y volumen

- [x] Paginar listados principales de categorías y productos.
- [ ] Completar filtros de productos por nombre, categoría, estado, rango de precio y stock.
- [ ] Completar ordenamiento por fecha, precio y stock.
- [ ] Revisar N+1 y usar eager loading (el listado actual hace consultas por producto para categoría/conteo).
- [ ] Añadir índices justificados por consultas reales.
- [ ] Implementar cache obligatoria para catálogos/consultas frecuentes e invalidación coherente.
- [ ] Crear seeders de volumen: mínimo 100 categorías, 10 000 productos y 30 000 movimientos.
- [ ] Asegurar coherencia entre stock final y movimientos generados.
- [ ] Medir antes/después con mismo volumen y condiciones.
- [ ] Documentar consultas lentas, índices, N+1, paginación, cache y evidencia de mejora.

## Pruebas y verificación

- [x] Disponer de pruebas automatizadas backend y configuración PHPUnit.
- [ ] Verificar login/logout/me y rechazo de solicitudes no autenticadas.
- [ ] Verificar CRUD y validaciones fallidas de categorías/productos.
- [ ] Verificar paginación, filtros y ordenamiento.
- [ ] Verificar entradas/salidas, saldo insuficiente y ausencia de cambios parciales.
- [ ] Verificar auditoría.
- [ ] Verificar endpoint health y contrato uniforme de respuestas.
- [ ] Ejecutar pruebas dentro de Docker con base de pruebas aislada.
- [ ] Verificar Swagger, Telescope y conexión real con frontend.
- [ ] Registrar resultados actuales de pruebas (no se vuelven a ejecutar como parte de este checklist).

## README y entrega

- [x] Documentar stack actual, servicios/puertos y comandos backend (`README.md`).
- [ ] Completar `.env.example` y documentar variables/credenciales de prueba.
- [ ] Documentar logs, reinicio y troubleshooting.
- [ ] Documentar versión inicial/final, incompatibilidades y soluciones.
- [ ] Documentar optimizaciones y mediciones antes/después.
- [ ] Documentar alcance de pruebas y ejecución.
- [ ] Documentar URLs reales de Swagger y Telescope.
- [ ] Documentar arquitectura, contrato JSON y trade-offs.
- [ ] Completar entrega conjunta: repos backend/frontend, Compose integrado, ambos `.env.example` y ambos README.

## Criterio de cierre

No marcar un requisito como completo solo porque exista un archivo o ruta: verificar comportamiento y ejecución en Docker. La guía considera Docker obligatorio y la optimización representa una parte central de la evaluación.
