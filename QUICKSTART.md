# 🚀 Guía de Inicio Rápido

## Prerrequisitos

1. Instalar **Docker Desktop** para Windows
   - Descargar desde: https://www.docker.com/products/docker-desktop/
   - Instalar y reiniciar el PC si es necesario
   - Asegurarse de que Docker esté ejecutándose

## Pasos de Instalación

### 1. Preparar el Proyecto

```powershell
# Navegar al directorio del proyecto
cd d:\proyectos\programasivan\Tuyalaravel

# Copiar archivo de configuración
copy .env.example .env
```

### 2. Editar Configuración

Abrir el archivo `.env` y configurar:

```env
# Credenciales del Administrador (CAMBIAR DESPUÉS)
ADMIN_EMAIL=admin@tuya.local
ADMIN_PASSWORD=admin123
```

### 3. Iniciar Docker

```powershell
# Construir e iniciar los contenedores
docker-compose up -d

# Esperar unos segundos y verificar que estén corriendo
docker-compose ps
```

### 4. Instalar Dependencias

```powershell
# Instalar dependencias de PHP (Composer)
docker-compose exec app composer install

# Instalar dependencias de Node.js
docker-compose exec app npm install
```

### 5. Configurar Base de Datos

```powershell
# Generar clave de aplicación
docker-compose exec app php artisan key:generate

# Ejecutar migraciones (crear tablas)
docker-compose exec app php artisan migrate

# Crear usuario administrador
docker-compose exec app php artisan db:seed
```

### 6. Compilar Assets

```powershell
# Compilar CSS y JavaScript
docker-compose exec app npm run build
```

## ✅ Verificar Instalación

1. Abrir navegador en: **http://localhost:8000**
2. Login con:
   - Email: `admin@tuya.local`
   - Contraseña: `admin123`

## 📝 Configuración Inicial

### Agregar Edificio

1. Ir a **Edificios** → **Nuevo Edificio**
2. Ingresar credenciales de **Tuya IoT Platform**:
   - Client ID
   - Client Secret
   (Obtener de: https://iot.tuya.com/ → Cloud → Development)

### Agregar Apartamentos y Cerraduras

1. Crear apartamentos para el edificio
2. Agregar cerraduras con el `device_id` de Tuya

## 🔄 Activar Sincronización Automática

```powershell
# Iniciar el scheduler (en una nueva terminal)
docker-compose exec app php artisan schedule:work
```

Alternativamente, configurar cron en producción.

## 🛑 Detener el Sistema

```powershell
# Detener contenedores
docker-compose down

# Detener y eliminar volúmenes (CUIDADO: borra la BD)
docker-compose down -v
```

## 📞 Endpoints API para el CRM

- **Base URL**: `http://localhost:8000/api`
- Ver documentación completa en `README.md`

### Ejemplo: Crear PIN

```http
POST http://localhost:8000/api/pins
Content-Type: application/json

{
  "lock_id": 1,
  "name": "John Doe - RES123",
  "effective_time": "2024-02-10 15:00:00",
  "invalid_time": "2024-02-15 11:00:00",
  "external_reference": "RES123"
}
```

## ⚠️ Problemas Comunes

### Docker no arranca
- Verificar que Docker Desktop esté ejecutándose
- Reiniciar Docker Desktop

### Puerto 8000 ocupado
Editar `docker-compose.yml` y cambiar:
```yaml
ports:
  - "8080:8000"  # Cambiar 8000 por otro puerto
```

### Errores de permisos
```powershell
docker-compose exec app chmod -R 777 storage bootstrap/cache
```

## 🎯 Próximos Pasos

1. Cambiar contraseña del administrador
2. Configurar edificios con credenciales Tuya
3. Agregar cerraduras
4. Probar creación de PIN desde API
5. Implementar webhooks si es necesario

---

**¿Necesitas ayuda?** Consulta el README.md completo para más detalles.
