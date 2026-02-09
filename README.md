# Tuya Smart Lock Management System

Sistema completo para gestionar cerraduras inteligentes Tuya Smart Lock X7 en edificios de apartamentos turísticos.

## 🚀 Características

- **Gestión de PINs Temporales**: Crear, modificar y revocar códigos de acceso
- **Multi-edificio**: Soporte para múltiples edificios con credenciales Tuya independientes
- **API para CRM**: Endpoints REST para integración con sistemas externos
- **Auditoría Completa**: Registro de todas las aperturas y accesos
- **Alertas en Tiempo Real**: Timbre, alarmas, coacciones
- **Sincronización Automática**: Jobs programados para mantener datos actualizados
- **Panel de Administración**: Interfaz web moderna y responsive

## 📋 Requisitos

- Docker Desktop
- Git

## 🔧 Instalación

### 1. Clonar o iniciar proyecto

El proyecto ya está creado en: `d:\proyectos\programasivan\Tuyalaravel`

### 2. Configurar entorno

```bash
# Copiar archivo de configuración
cp .env.example .env

# Editar .env y configurar:
# - Credenciales de admin (ADMIN_EMAIL, ADMIN_PASSWORD)
# - Región Tuya (ya configurado para EU)
```

### 3. Iniciar con Docker

```bash
# Construir e inicial contenedores
docker-compose up -d

# Instalar dependencias PHP
docker-compose exec app composer install

# Instalar dependencias Node.js
docker-compose exec app npm install

# Generar key de aplicación
docker-compose exec app php artisan key:generate

# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Crear usuario admin
docker-compose exec app php artisan db:seed

# Compilar assets
docker-compose exec app npm run build
```

### 4. Acceder al sistema

- **Panel Admin**: http://localhost:8000
- **Login**: admin@tuya.local / admin123 (cambiar después)
- **API**: http://localhost:8000/api

## 📁 Estructura del Proyecto

```
app/
├── Http/Controllers/
│   ├── Api/              # Controladores para CRM
│   │   ├── PinController.php
│   │   ├── LockController.php
│   │   └── AlertController.php
│   ├── DashboardController.php
│   ├── BuildingController.php
│   ├── ApartmentController.php
│   └── LockController.php
├── Models/
│   ├── Building.php
│   ├── Apartment.php
│   ├── Lock.php
│   ├── TempPassword.php
│   ├── UnlockLog.php
│   └── AlertLog.php
├── Services/
│   └── TuyaApiService.php    # Servicio Tuya API
└── Jobs/
    ├── SyncUnlockLogs.php
    └── SyncAlerts.php
```

## 🔌 API para CRM

### Autenticación
Actualmente sin autenticación (agregar según necesidades).

### Endpoints

#### Crear PIN
```http
POST /api/pins
Content-Type: application/json

{
  "lock_id": 1,
  "name": "John Doe - RES123",
  "effective_time": "2024-02-10 15:00:00",
  "invalid_time": "2024-02-15 11:00:00",
  "external_reference": "RES123"
}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "pin": "1234567",
    "tuya_password_id": "675485614",
    "effective_time": "2024-02-10T15:00:00.000000Z",
    "invalid_time": "2024-02-15T11:00:00.000000Z",
    "status": "created_cloud"
  }
}
```

#### Modificar duración PIN (Late/Early Checkout)
```http
PATCH /api/pins/{id}
Content-Type: application/json

{
  "invalid_time": "2024-02-15 14:00:00"
}
```

#### Revocar PIN
```http
DELETE /api/pins/{id}
```

#### Estado de cerradura
```http
GET /api/locks/{id}/status
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "lock_id": 1,
    "device_id": "bf6520bfd42e18f4d8n9to",
    "apartment": "101",
    "building": "Edificio A",
    "online": true,
    "battery_level": 85,
    "last_sync": "2024-02-07T20:00:00.000000Z"
  }
}
```

#### Logs de apertura
```http
GET /api/locks/{id}/logs?start_date=2024-02-01&end_date=2024-02-07
```

#### Alertas pendientes
```http
GET /api/alerts/pending
```

Automáticamente marca las alertas como notificadas.

## ⚙️ Configuración de Edificios

1. Acceder al panel admin
2. Ir a **Edificios** → **Nuevo Edificio**
3. Completar:
   - Nombre del edificio
   - Dirección
   - **Tuya Client ID** (de Tuya IoT Platform)
   - **Tuya Client Secret** (de Tuya IoT Platform)
4. Crear apartamentos para el edificio
5. Agregar cerraduras vinculando `device_id` de Tuya

## 🔄 Sincronización Automática

El sistema ejecuta automáticamente:

- **Cada 15 minutos**: Sincroniza logs de apertura
- **Cada 10 minutos**: Sincroniza alertas

Para ejecutar manualmente:
```bash
docker-compose exec app php artisan schedule:work
```

## 🗄️ Base de Datos

**PostgreSQL** en Docker:
- Host: `localhost`
- Puerto: `5432`
- Base de datos: `tuya_locks`
- Usuario: `tuya_user`
- Contraseña: `tuya_password`

## 📊 Modelo de Datos

```
Building (Edificio)
  ├── Client ID/Secret Tuya
  └── N Apartments
       └── 1 Lock (Cerradura)
            ├── Device ID Tuya
            ├── N TempPasswords (PINs)
            ├── N UnlockLogs
            └── N AlertLogs
```

## 🛠️ Comandos Útiles

```bash
# Ver logs de Laravel
docker-compose exec app tail -f storage/logs/laravel.log

# Ejecutar queue worker
docker-compose exec app php artisan queue:work

# Limpiar cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# Ejecutar migraciones frescas (CUIDADO: borra datos)
docker-compose exec app php artisan migrate:fresh --seed
```

## 🔐 Seguridad

- Cambiar credenciales admin después de primera instalación
- Los PINs se almacenan cifrados en la BD
- Las credenciales Tuya están ocultas en respuestas API
- Implementar autenticación API antes de producción

## 🚧 Pendiente de Implementar

- [ ] Modificar PIN via API Tuya (actualmente solo local)
- [ ] Estado de dispositivo en tiempo real (batería, online)
- [ ] Webhooks de Tuya para eventos en tiempo real
- [ ] Autenticación API (Bearer tokens)
- [ ] Tests unitarios

## 📞 Soporte

Para consultas sobre la API de Tuya:
- Documentación: https://developer.tuya.com
- Región: Europa (EU)
- Base URL: https://openapi.tuyaeu.com

## 📝 Notas Importantes

1. **PINs deben ser de 7 dígitos** (requisito del Smart Lock X7 validado)
2. **Tiempos en API**:
   - Creación de PIN: epoch en **segundos**
   - Logs de apertura: epoch en **milisegundos**
3. **Sincronización**: Las cerraduras pueden tardar en sincronizar si están en modo ahorro de energía
4. **Eliminación de PINs**: No se puede eliminar un PIN ya expirado (error 2304)

---

**Desarrollado para gestión profesional de accesos en apartamentos turísticos**
