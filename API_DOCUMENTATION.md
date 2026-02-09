# 📋 Documentación de la API - Tuya Lock Manager

## Autenticación

**Actualmente sin autenticación implementada.**  
Recomendación: Implementar API tokens antes de producción.

---

## Endpoints de PINs

### Crear PIN Temporal

Crea un nuevo código de acceso temporal para un huésped.

**Endpoint:** `POST /api/pins`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "lock_id": 1,
  "name": "John Doe - Reserva 12345",
  "effective_time": "2024-02-10 15:00:00",
  "invalid_time": "2024-02-15 11:00:00",
  "external_reference": "RES12345"
}
```

**Parámetros:**
| Campo | Tipo | Required | Descripción |
|-------|------|----------|-------------|
| lock_id | integer | Sí | ID de la cerradura |
| name | string | Sí | Nombre del huésped / identificador |
| effective_time | datetime | Sí | Fecha/hora de inicio (formato: Y-m-d H:i:s) |
| invalid_time | datetime | Sí | Fecha/hora de expiración |
| external_reference | string | No | ID de reserva del CRM |

**Respuesta Exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 42,
    "pin": "1234567",
    "tuya_password_id": "675485614",
    "effective_time": "2024-02-10T15:00:00.000000Z",
    "invalid_time": "2024-02-15T11:00:00.000000Z",
    "status": "created_cloud"
  }
}
```

**⚠️ IMPORTANTE:** El PIN `1234567` solo se devuelve UNA VEZ. El CRM debe guardarlo para compartirlo con el huésped.

**Errores:**
```json
// 422 - Validación fallida
{
  "error": {
    "lock_id": ["El campo lock_id es requerido"],
    "invalid_time": ["invalid_time debe ser posterior a effective_time"]
  }
}

// 500 - Error de Tuya API
{
  "error": "Failed to create PIN: password length incorrect"
}
```

---

### Actualizar Duración PIN

Modifica la fecha de expiración de un PIN (útil para late checkout / early checkin).

**Endpoint:** `PATCH /api/pins/{id}`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "invalid_time": "2024-02-15 14:00:00"
}
```

**Respuesta (200):**
```json
{
  "success": true,
  "message": "PIN duration updated",
  "data": {
    "id": 42,
    "invalid_time": "2024-02-15T14:00:00.000000Z"
  }
}
```

**Nota:** Actualmente solo actualiza la BD local. La API de Tuya para modificar PINs está pendiente.

---

### Consultar PIN

Obtiene detalles de un PIN específico.

**Endpoint:** `GET /api/pins/{id}`

**Respuesta (200):**
```json
{
  "success": true,
  "data": {
    "id": 42,
    "lock_id": 1,
    "name": "John Doe - Reserva 12345",
    "effective_time": "2024-02-10T15:00:00.000000Z",
    "invalid_time": "2024-02-15T11:00:00.000000Z",
    "status": "active",
    "is_active": true,
    "external_reference": "RES12345"
  }
}
```

**Estados posibles:**
- `created_cloud`: Creado en Tuya cloud, pendiente de sincronización
- `syncing`: En proceso de sincronización con la cerradura
- `active`: Activo y funcional
- `deleted`: Revocado manualmente
- `expired`: Caducado automáticamente

---

### Revocar PIN

Elimina un PIN activo antes de su expiración.

**Endpoint:** `DELETE /api/pins/{id}`

**Respuesta (200):**
```json
{
  "success": true,
  "message": "PIN revoked successfully"
}
```

**Casos especiales:**
- Si el PIN ya expiró, se marca como `expired` sin error
- No se puede eliminar un PIN ya eliminado

---

## Endpoints de Cerraduras

### Estado de Cerradura

Obtiene información actual de una cerradura.

**Endpoint:** `GET /api/locks/{id}/status`

**Respuesta (200):**
```json
{
  "success": true,
  "data": {
    "lock_id": 1,
    "device_id": "bf6520bfd42e18f4d8n9to",
    "name": "Puerta Principal",
    "apartment": "101",
    "building": "Edificio A",
    "active": true,
    "online": true,
    "battery_level": 85,
    "last_sync": "2024-02-07T20:15:00.000000Z",
    "status_data": {
      "battery_level": 85,
      "online": true
    }
  }
}
```

---

### Logs de Apertura

Obtiene histórico de aperturas de una cerradura.

**Endpoint:** `GET /api/locks/{id}/logs`

**Query Parameters:**
| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| start_date | date | Hace 7 días | Fecha de inicio (Y-m-d) |
| end_date | date | Hoy | Fecha de fin (Y-m-d) |

**Respuesta (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "unlock_method": "unlock_temporary",
      "unlocked_at": "2024-02-10T16:30:45.000000Z",
      "guest_name": "John Doe - Reserva 12345",
      "temp_password_id": 42,
      "external_reference": "RES12345"
    },
    {
      "id": 124,
      "unlock_method": "unlock_app",
      "unlocked_at": "2024-02-11T10:15:22.000000Z",
      "guest_name": null,
      "temp_password_id": null,
      "external_reference": null
    }
  ],
  "meta": {
    "start_date": "2024-02-01",
    "end_date": "2024-02-07",
    "total": 2
  }
}
```

**Métodos de apertura:**
- `unlock_temporary`: PIN temporal
- `unlock_fingerprint`: Huella digital
- `unlock_app`: App móvil Tuya
- `unlock_key`: Llave física
- `unlock_card`: Tarjeta NFC

---

## Endpoints de Alertas

### Todas las Alertas

Obtiene alertas filtradas.

**Endpoint:** `GET /api/alerts`

**Query Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| lock_id | integer | Filtrar por cerradura |
| code | string | Filtrar por tipo (doorbell, alarm_lock, hijack) |

**Respuesta (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "lock_id": 1,
      "device_id": "bf6520bfd42e18f4d8n9to",
      "apartment": "101",
      "building": "Edificio A",
      "alert_code": "doorbell",
      "alert_time": "2024-02-07T18:30:00.000000Z",
      "notified": true
    }
  ],
  "meta": {
    "total": 1
  }
}
```

---

### Alertas Pendientes

Obtiene alertas no notificadas al CRM y las marca como notificadas.

**Endpoint:** `GET /api/alerts/pending`

**Respuesta (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "lock_id": 2,
      "device_id": "abc123xyz",
      "apartment": "205",
      "building": "Edificio B",
      "alert_code": "alarm_lock",
      "alert_time": "2024-02-07T22:15:00.000000Z"
    }
  ],
  "meta": {
    "total": 1,
    "marked_as_notified": true
  }
}
```

**⚠️ IMPORTANTE:** Este endpoint automáticamente marca las alertas como notificadas. Solo llamar cuando el CRM esté listo para procesarlas.

**Tipos de alertas:**
- `doorbell`: Timbre pulsado
- `alarm_lock`: Alarma general
- `hijack`: Código de coacción usado

---

## Códigos de Error Comunes

| Código | Significado | Solución |
|--------|-------------|----------|
| 400 | Bad Request | Verificar formato de datos |
| 404 | Not Found | ID no existe |
| 422 | Validation Error | Revisar campos requeridos |
| 500 | Server Error | Verificar logs, puede ser error de Tuya API |

---

## Ejemplos de Integración

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'http://localhost:8000']);

// Crear PIN
$response = $client->post('/api/pins', [
    'json' => [
        'lock_id' => 1,
        'name' => 'Guest Name',
        'effective_time' => '2024-02-10 15:00:00',
        'invalid_time' => '2024-02-15 11:00:00',
        'external_reference' => 'RES123',
    ]
]);

$data = json_decode($response->getBody(), true);
$pin = $data['data']['pin']; // Guardar el PIN
```

### JavaScript (Fetch)

```javascript
// Crear PIN
const response = await fetch('http://localhost:8000/api/pins', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        lock_id: 1,
        name: 'Guest Name',
        effective_time: '2024-02-10 15:00:00',
        invalid_time: '2024-02-15 11:00:00',
        external_reference: 'RES123',
    }),
});

const data = await response.json();
const pin = data.data.pin; // Guardar el PIN
```

---

## Límites y Consideraciones

1. **PIN de 7 dígitos**: El Smart Lock X7 requiere exactamente 7 dígitos
2. **Sincronización**: Las cerraduras pueden tardar en sincronizar si están en modo ahorro
3. **Tiempos**: Todas las fechas se manejan en formato ISO 8601 con timezone UTC
4. **Rate limiting**: Sin límite actualmente (implementar antes de producción)

---

## Webhooks (Futuro)

Pendiente de implementación. Permitirá notificaciones push al CRM en lugar de polling.
