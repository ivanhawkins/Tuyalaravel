# 🐳 Instalación de Docker Desktop - Guía Paso a Paso

## Opción 1: Instalación Automática con WinGet (Recomendado)

Si tienes Windows Package Manager (winget), puedes instalar Docker automáticamente:

```powershell
# Instalar Docker Desktop
winget install Docker.DockerDesktop

# Reiniciar el PC después de la instalación
```

## Opción 2: Descarga Manual

1. **Ir a la página oficial:**
   https://www.docker.com/products/docker-desktop/

2. **Click en "Download Docker Desktop for Windows"**

3. **Ejecutar el instalador descargado**
   - Aceptar los términos
   - Seguir el asistente de instalación
   - **IMPORTANTE:** Dejar marcada la opción "Use WSL 2 instead of Hyper-V"

4. **Reiniciar el PC** cuando te lo pida

---

## Después de Instalar Docker

### 1️⃣ Verificar Instalación

Abre PowerShell y ejecuta:

```powershell
docker --version
docker-compose --version
```

Deberías ver algo como:
```
Docker version 24.0.x
Docker Compose version v2.x.x
```

### 2️⃣ Iniciar Docker Desktop

- Buscar "Docker Desktop" en el menú de Windows
- Abrirlo y esperar a que arranque (ícono de ballena en la bandeja del sistema)
- Esperar hasta que diga "Docker Desktop is running"

---

## ✅ Una vez Docker esté funcionando

Ejecuta estos comandos en el directorio del proyecto:

```powershell
# Navegar al proyecto
cd d:\proyectos\programasivan\Tuyalaravel

# Copiar configuración
copy .env.example .env

# Construir e iniciar contenedores (PRIMERA VEZ: tarda 5-10 minutos)
docker-compose up -d

# Esperar a que terminen de arrancar los contenedores
# Puedes ver el progreso con:
docker-compose ps

# Instalar dependencias PHP (Composer)
docker-compose exec app composer install

# Instalar dependencias Node.js
docker-compose exec app npm install

# Generar clave de aplicación
docker-compose exec app php artisan key:generate

# Crear base de datos
docker-compose exec app php artisan migrate

# Crear usuario admin
docker-compose exec app php artisan db:seed

# Compilar assets frontend
docker-compose exec app npm run build

# ¡LISTO! Acceder a:
# http://localhost:8000
```

---

## 🔍 Verificar que Todo Funciona

```powershell
# Ver contenedores corriendo
docker-compose ps

# Deberías ver 3 contenedores:
# - tuyalaravel-app (Laravel)
# - tuyalaravel-postgres (Base de datos)
# - tuyalaravel-redis (Cache)
```

---

## 🚨 Problemas Comunes

### "WSL 2 installation is incomplete"

Ejecutar en PowerShell como Administrator:
```powershell
wsl --install
```
Reiniciar PC.

### "Docker Desktop requires a newer WSL kernel version"

Ejecutar:
```powershell
wsl --update
```

### Puerto 8000 ya ocupado

Editar `docker-compose.yml`, cambiar la línea:
```yaml
ports:
  - "8080:8000"  # Cambiar 8000 por 8080
```

Luego acceder a http://localhost:8080

---

## 📞 ¿Necesitas ayuda?

Si tienes algún error, comparte el mensaje de error y te ayudo a resolverlo.

**¡Una vez completado, tendrás el sistema completo funcionando! 🎉**
