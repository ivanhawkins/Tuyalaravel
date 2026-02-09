# 🚀 Script de Configuración Automática - Tuya Lock Manager
# Este script configura todo el proyecto automáticamente

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Tuya Lock Manager - Setup Automático" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Paso 1: Verificar Docker
Write-Host "1. Verificando Docker..." -ForegroundColor Yellow
try {
    $dockerVersion = docker --version
    Write-Host "   ✅ Docker instalado: $dockerVersion" -ForegroundColor Green
} catch {
    Write-Host "   ❌ ERROR: Docker no encontrado" -ForegroundColor Red
    Write-Host "   Por favor:" -ForegroundColor Yellow
    Write-Host "   - Abre Docker Desktop desde el menú de Windows" -ForegroundColor Yellow
    Write-Host "   - Espera a que arranque completamente" -ForegroundColor Yellow
    Write-Host "   - Reinicia PowerShell" -ForegroundColor Yellow
    Write-Host "   -  Ejecuta de nuevo: .\setup.ps1" -ForegroundColor Yellow
    exit 1
}

# Paso 2: Copiar .env
Write-Host ""
Write-Host "2. Configurando variables de entorno..." -ForegroundColor Yellow
if (-Not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "   ✅ Archivo .env creado" -ForegroundColor Green
} else {
    Write-Host "   ℹ️  .env ya existe, omitiendo" -ForegroundColor Blue
}

# Paso 3: Iniciar contenedores
Write-Host ""
Write-Host "3. Iniciando contenedores Docker..." -ForegroundColor Yellow
Write-Host "   ⏳ Esto puede tardar 5-10 minutos la primera vez..." -ForegroundColor Cyan
docker-compose up -d
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Contenedores iniciados correctamente" -ForegroundColor Green
} else {
    Write-Host "   ❌ ERROR al iniciar contenedores" -ForegroundColor Red
    exit 1
}

# Esperar a que PostgreSQL esté listo
Write-Host ""
Write-Host "4. Esperando a que PostgreSQL esté listo..." -ForegroundColor Yellow
Start-Sleep -Seconds 10
Write-Host "   ✅ PostgreSQL listo" -ForegroundColor Green

# Paso 5: Instalar dependencias PHP
Write-Host ""
Write-Host "5. Instalando dependencias PHP (Composer)..." -ForegroundColor Yellow
Write-Host "   ⏳ Esto puede tardar 2-3 minutos..." -ForegroundColor Cyan
docker-compose exec -T app composer install --no-interaction
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Dependencias PHP instaladas" -ForegroundColor Green
} else {
    Write-Host "   ❌ ERROR instalando dependencias PHP" -ForegroundColor Red
    exit 1
}

# Paso 6: Instalar dependencias Node.js
Write-Host ""
Write-Host "6. Instalando dependencias Node.js..." -ForegroundColor Yellow
Write-Host "   ⏳ Esto puede tardar 1-2 minutos..." -ForegroundColor Cyan
docker-compose exec -T app npm install
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Dependencias Node.js instaladas" -ForegroundColor Green
} else {
    Write-Host "   ❌ ERROR instalando dependencias Node.js" -ForegroundColor Red
    exit 1
}

# Paso 7: Generar clave de aplicación
Write-Host ""
Write-Host "7. Generando clave de aplicación Laravel..." -ForegroundColor Yellow
docker-compose exec -T app php artisan key:generate --no-interaction
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Clave generada" -ForegroundColor Green
} else {
    Write-Host "   ❌ ERROR generando clave" -ForegroundColor Red
    exit 1
}

# Paso 8: Ejecutar migraciones
Write-Host ""
Write-Host "8. Creando base de datos..." -ForegroundColor Yellow
docker-compose exec -T app php artisan migrate --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Base de datos creada" -ForegroundColor Green
} else {
    Write-Host "   ❌ ERROR creando base de datos" -ForegroundColor Red
    exit 1
}

# Paso 9: Crear usuario admin
Write-Host ""
Write-Host "9. Creando usuario administrador..." -ForegroundColor Yellow
docker-compose exec -T app php artisan db:seed --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Usuario admin creado" -ForegroundColor Green
} else {
    Write-Host "   ❌ ERROR creando usuario admin" -ForegroundColor Red
    exit 1
}

# Paso 10: Compilar assets
Write-Host ""
Write-Host "10. Compilando assets (CSS/JS)..." -ForegroundColor Yellow
Write-Host "   ⏳ Esto puede tardar 1-2 minutos..." -ForegroundColor Cyan
docker-compose exec -T app npm run build
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Assets compilados" -ForegroundColor Green
} else {
    Write-Host "   ❌ ERROR compilando assets" -ForegroundColor Red
    exit 1
}

# ¡Completado!
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  ✅ ¡INSTALACIÓN COMPLETADA!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "📝 Credenciales de acceso:" -ForegroundColor Cyan
Write-Host "   URL:      http://localhost:8000" -ForegroundColor White
Write-Host "   Email:    admin@tuya.local" -ForegroundColor White
Write-Host "   Password: admin123" -ForegroundColor White
Write-Host ""
Write-Host "🚀 Abriendo el sistema en tu navegador..." -ForegroundColor Yellow
Start-Sleep -Seconds 2
Start-Process "http://localhost:8000"

Write-Host ""
Write-Host "💡 Comandos útiles:" -ForegroundColor Cyan
Write-Host "   Ver logs:        docker-compose logs -f app" -ForegroundColor White
Write-Host "   Detener:         docker-compose down" -ForegroundColor White
Write-Host "   Reiniciar:       docker-compose restart" -ForegroundColor White
Write-Host ""
