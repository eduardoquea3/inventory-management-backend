# Backend — Guía de inicio y requisitos

Basado en `Pruaba-Tecnica.md`: evaluación Full Stack Laravel + Vue.js.

## 1. Qué debes entregar

Migrar o reestructurar el backend legacy hacia **Laravel 11 o superior, con PHP 8.2 o superior**, usando MySQL, arquitectura ordenada, autenticación, auditoría, pruebas y optimización medible.

**Docker es obligatorio:** deben funcionar backend, frontend y MySQL desde cero siguiendo el README. Las migraciones, seeders y pruebas deben ejecutarse dentro de contenedores. No cumplir esta condición puede resultar en NO APTA aunque el CRUD funcione localmente.

La empresa entrega un proyecto legacy con errores intencionales. Debes diagnosticarlo y migrarlo; no limitarte a crear un CRUD nuevo. Las estructuras y decisiones propuestas aquí son recomendaciones, no requisitos adicionales de la prueba.

## Organización: dos repositorios independientes

Trabajarás con **backend y frontend por separado**, cada uno con su propio repositorio Git, README y `.env.example`.

Propuesta de arranque conjunto, sin monorepo: clonar ambos como carpetas hermanas (`backend/` y `frontend/`) y mantener el `docker-compose.yml` principal en el repositorio backend. Ese Compose levanta Laravel, servidor web, MySQL y un servicio frontend cuyo contexto de construcción es `../frontend`; el Dockerfile frontend pertenece al repositorio frontend.

Ejemplo de clonación (reemplaza las URLs por las reales):

```bash
git clone <URL_BACKEND> backend
git clone <URL_FRONTEND> frontend
```

La carpeta padre solo agrupa los clones; no constituye un repositorio Git. Ejecuta los comandos principales de Compose desde `backend/`. Documenta los nombres y la ubicación de ambos clones en los dos README; el evaluador debe poder levantar todo desde cero con esa disposición.

## 2. Cómo iniciar el proyecto

### Paso 1: diagnosticar el backend recibido

1. Clona el repositorio y crea una rama de trabajo.
2. Revisa `composer.json`, versión de PHP/Laravel, rutas, controladores, modelos, migraciones y tests.
3. Identifica validaciones débiles, respuestas inconsistentes, consultas lentas, N+1 y dependencias incompatibles.
4. Registra versiones y problemas iniciales. Obtén mediciones de consultas/listados para comparar después.
5. Prepara el plan de migración y revisa los cambios de compatibilidad de las versiones elegidas.

### Paso 2: preparar el entorno Docker

| Servicio o elemento | Exigencia |
| --- | --- |
| `backend` PHP/Laravel | Composer, artisan, migraciones, seeders y pruebas |
| Nginx o servidor web | API en una URL documentada, por ejemplo `http://localhost:8080` |
| `mysql` | Base de datos, usuario y contraseña mediante variables |
| `frontend` Node/Vue | Vite, por ejemplo `http://localhost:5173` |
| Volúmenes | Persistir MySQL y permitir desarrollo local |
| Red interna | Comunicación por nombres de servicio, sin IP fija |
| Redis | Opcional; suma puntos para cache/colas y no sustituye MySQL |

Configura extensiones PHP requeridas, permisos de Laravel y espera de disponibilidad de MySQL. Documenta cualquier inicialización necesaria; no dependas de herramientas instaladas en el host salvo Docker y Docker Compose.

### Paso 3: configurar variables de entorno

Ejemplo orientativo de `backend/.env.example`, suponiendo que el servicio se llama `mysql`:

```dotenv
APP_NAME=PruebaTecnica
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=prueba_tecnica
DB_USERNAME=prueba
DB_PASSWORD=prueba_local
```

Estas credenciales son ejemplos locales. Completa además las variables usadas por cache, sesiones, autenticación y Telescope según tu implementación. Los valores de MySQL en Compose deben coincidir; documenta también las variables del Compose. Permite mediante CORS el origen del frontend y configura la autenticación elegida.

### Paso 4: migrar y levantar

Actualiza `composer.json` y el código gradualmente, corrige incompatibilidades y conserva el lockfile. Si eliges una versión superior a Laravel 11, utiliza la versión PHP compatible con ella; PHP 8.2 es el mínimo de la prueba, no una garantía de compatibilidad con cualquier versión.

Desde la carpeta del repositorio backend, una vez creados Dockerfile y Compose, y copiados los `.env.example` a los `.env` correspondientes, la ruta mínima esperada es:

```bash
docker compose up -d --build
docker compose exec backend composer install
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate --seed
docker compose exec backend php artisan test
docker compose logs -f backend
docker compose down
```

Estos comandos suponen un servicio `backend` con el directorio Laravel como directorio de trabajo. Puedes ajustarlos si tu estructura lo requiere, dejando una ruta completa y reproducible. El arranque debe soportar un proyecto aún sin dependencias o documentar su instalación previa dentro de Docker.

Si necesitas una base Laravel nueva para trasladar el legacy, créala con Composer dentro de un contenedor, en un directorio separado y con una versión explícita compatible. Traslada y verifica modelos, reglas, rutas y datos; documenta por qué elegiste esa estrategia.

### Paso 5: separar responsabilidades

| Capa requerida | Responsabilidad |
| --- | --- |
| Controllers | Recibir solicitudes y coordinar respuestas |
| Form Requests | Validación y autorización de entradas |
| Services | Reglas de negocio y operaciones transaccionales |
| Resources | Serialización consistente de la API |

Ubicaciones sugeridas: `app/Http/Controllers/Api`, `app/Http/Requests`, `app/Http/Resources`, `app/Services`, `app/Models`, `database/migrations`, `database/seeders` y `tests/Feature`.

## 3. Requisitos técnicos obligatorios

- Laravel 11+ y PHP 8.2+ compatibles entre sí.
- Controllers, Form Requests, Services y Resources.
- Respuestas JSON estandarizadas, incluidos errores y paginación.
- Autenticación con Laravel Sanctum o mecanismo equivalente.
- Swagger/OpenAPI para documentar los endpoints.
- Laravel Telescope habilitado en local/desarrollo.
- Auditoría de acciones create/update/delete y movimientos de stock.
- Pruebas automatizadas mínimas ejecutables.

El documento exige un formato estándar, pero no proporciona un esquema JSON concreto. Define uno, aplícalo de forma consistente y documenta su contrato para el frontend. El frontend requiere manejo de token; si eliges otra modalidad de Sanctum, acuerda y documenta su integración.

## 4. Endpoints mínimos

| Módulo | Método y ruta | Comportamiento requerido |
| --- | --- | --- |
| Autenticación | `POST /api/login` | Iniciar sesión |
| Autenticación | `POST /api/logout` | Cerrar sesión |
| Autenticación | `GET /api/me` | Obtener usuario autenticado |
| Categorías | `GET /api/categories` | Listar |
| Categorías | `POST /api/categories` | Crear con validaciones y estado activo/inactivo |
| Categorías | `GET /api/categories/{id}` | Obtener detalle |
| Categorías | `PUT /api/categories/{id}` | Actualizar |
| Categorías | `DELETE /api/categories/{id}` | Eliminar |
| Productos | `GET /api/products` | Listar con filtros, paginación y relación con categoría |
| Productos | `POST /api/products` | Crear con validaciones |
| Productos | `GET /api/products/{id}` | Obtener detalle |
| Productos | `PUT /api/products/{id}` | Actualizar |
| Productos | `DELETE /api/products/{id}` | Eliminar |
| Stock | `GET /api/products/{id}/stock-movements` | Historial del producto |
| Stock | `POST /api/products/{id}/stock-movements` | Registrar entrada o salida |
| Salud | `GET /api/health` | Verificar aplicación y conexión a base de datos |

El frontend necesita KPIs y últimos movimientos para el dashboard. La prueba no define un endpoint específico: acuerda cómo exponer esos datos y documenta la decisión.

## 5. Datos y reglas de negocio

Entidades principales: usuarios, categorías, productos, movimientos de stock y registros de auditoría. Deben existir migraciones y relaciones coherentes entre producto/categoría y producto/movimientos.

Reglas exigidas:

- Validaciones del CRUD, categorías con estado activo/inactivo y productos relacionados con una categoría.
- Registrar entradas y salidas de stock.
- Rechazar una salida mayor al stock disponible.
- Usar transacciones para movimientos de stock.
- Auditar create/update/delete y movimientos de stock.

Recomendaciones para implementar esas reglas:

- Validar cantidades positivas, precios y referencias existentes según el esquema acordado.
- Leer y bloquear el producto dentro de la transacción, validar saldo, actualizar stock y registrar movimiento. Esto evita salidas simultáneas que dejen stock negativo.
- Registrar actor, acción, entidad, fecha y cambios relevantes en auditoría.
- Definir qué ocurre al eliminar una categoría con productos o un producto con historial.

La prueba no determina todos los campos, la política de eliminación ni el umbral de bajo stock. Son decisiones que debes justificar.

## 6. Optimización obligatoria

- Paginación en los listados principales.
- Filtros de productos por nombre, categoría, estado, rango de precio y stock.
- Ordenamiento por fecha, precio y stock.
- Índices en columnas usadas para filtros y relaciones, elegidos según consultas reales.
- Eager loading para evitar N+1.
- Cache para catálogos o consultas frecuentes, con invalidación coherente al modificar datos.
- Transacciones en movimientos de stock.
- Seeders de volumen con **mínimo 100 categorías, 10 000 productos y 30 000 movimientos de stock**.

Genera datos coherentes: movimientos y stock final deben respetar las reglas. Mide consultas y tiempos antes/después con el mismo volumen y condiciones. Documenta consultas lentas, índices, N+1 corregido, paginación y cache, con evidencia de mejora. Redis es opcional; la cache es obligatoria.

## 7. Pruebas y verificación

La prueba exige tests backend mínimos, pero no fija una cantidad. Cobertura sugerida:

1. Login, logout, usuario autenticado y rechazo de acceso sin sesión.
2. CRUD de categorías y productos, incluyendo validaciones fallidas.
3. Paginación, filtros y ordenamiento.
4. Entrada/salida de stock y rechazo por saldo insuficiente sin cambios parciales.
5. Registro de auditoría.
6. Endpoint de salud y respuestas JSON consistentes.

Ejecuta los tests dentro de Docker con base de datos de pruebas aislada. Verifica también Swagger, Telescope y conexión real con el frontend. Telescope debe quedar limitado a local/desarrollo.

## 8. README y entrega

Incluye:

- Requisitos Docker/Compose y pasos desde clonación hasta ejecución.
- `.env.example` completo, variables base y credenciales de prueba si aplica.
- Servicios, puertos, logs, reinicio y troubleshooting.
- Comandos de migraciones, seeders y pruebas dentro de Docker.
- Versión inicial y final, dependencias, incompatibilidades y soluciones de migración.
- Optimizaciones y mediciones antes/después.
- Qué cubren las pruebas y cómo ejecutarlas.
- URLs reales de Swagger y Telescope.
- Arquitectura, contrato JSON y decisiones técnicas con sus trade-offs.

Checklist final:

- [ ] Repositorio backend independiente actualizado y conectado a MySQL real.
- [ ] Compose principal referencia el Dockerfile del repositorio frontend mediante `../frontend`.
- [ ] Todos los endpoints mínimos implementados.
- [ ] Autenticación, validaciones y respuestas estandarizadas.
- [ ] Auditoría y stock transaccional con validación de saldo.
- [ ] Paginación, filtros, ordenamiento, índices, eager loading y cache.
- [ ] Seeders con los tres mínimos de volumen exigidos.
- [ ] Swagger y Telescope disponibles en las URLs documentadas.
- [ ] Pruebas mínimas pasan desde Docker.
- [ ] Frontend, backend y MySQL levantan desde cero siguiendo el README.
- [ ] Migración, optimizaciones y decisiones documentadas.

Entrega conjunta: dos repositorios independientes backend/frontend compartidos, `docker-compose.yml`, `.env.example` de ambas partes y README. Correo indicado por la prueba: `pierog@overskull.pe`. Duración sugerida total: 5 a 6 horas. Optimización y rendimiento tienen el mayor peso individual de la rúbrica (20%); Docker sigue siendo condición obligatoria.
