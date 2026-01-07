# Sistema de Filtrado Global

## Descripción General

Este sistema permite filtrar datos de forma flexible y reutilizable en cualquier endpoint de la API. El frontend puede enviar filtros en formato JSON con diferentes operadores, y el backend los aplicará automáticamente a las consultas Eloquent.

## Componentes Principales

### 1. ValueObjects

#### Filter (`App\ValueObjects\Filter`)
Representa un filtro individual con tres propiedades:
- `field`: Campo a filtrar
- `operator`: Operador a aplicar
- `value`: Valor del filtro

#### FilterCollection (`App\ValueObjects\FilterCollection`)
Colección de objetos `Filter` que implementa `Countable`, `IteratorAggregate` y `JsonSerializable`.

### 2. Request (`App\Http\Requests\FilterableRequest`)
Clase base que valida y procesa los filtros recibidos del frontend.

### 3. Service (`App\Services\QueryFilterService`)
Servicio que aplica los filtros a un Query Builder de Eloquent.

## Operadores Soportados

| Operador | SQL | Descripción | Ejemplo de Valor |
|----------|-----|-------------|------------------|
| `EQUAL` | `=` | Igual a | `"Juan"` |
| `NOT_EQUAL` | `!=` | Diferente de | `"Pedro"` |
| `LIKE` | `LIKE` | Contiene (usar con %) | `"%gmail%"` |
| `NOT_LIKE` | `NOT LIKE` | No contiene | `"%spam%"` |
| `IN` | `IN` | Está en la lista | `["active", "pending"]` |
| `NOT_IN` | `NOT IN` | No está en la lista | `["deleted", "archived"]` |
| `GREATER_THAN` | `>` | Mayor que | `100` |
| `GREATER_THAN_OR_EQUAL` | `>=` | Mayor o igual que | `18` |
| `LESS_THAN` | `<` | Menor que | `50` |
| `LESS_THAN_OR_EQUAL` | `<=` | Menor o igual que | `30` |
| `IS_NULL` | `IS NULL` | Es nulo | `null` (no requiere value) |
| `IS_NOT_NULL` | `IS NOT NULL` | No es nulo | `null` (no requiere value) |
| `BETWEEN` | `BETWEEN` | Entre dos valores | `[10, 20]` |

## Cómo Usar el Sistema

### Paso 1: Crear un Request Específico

Extiende `FilterableRequest` y define los campos permitidos para filtrar:

```php
<?php

namespace App\Http\Requests\YourModule;

use App\Http\Requests\FilterableRequest;

class GetYourModelRequest extends FilterableRequest
{
    /**
     * Fields that are allowed to be filtered.
     */
    protected array $filterableFields = [
        'id',
        'name',
        'email',
        'status',
        'created_at',
    ];
}
```

> **Nota:** Si `$filterableFields` está vacío, se permite filtrar por cualquier campo (no recomendado en producción).

### Paso 2: Actualizar el Controlador

Inyecta el Request personalizado y el `QueryFilterService`:

```php
<?php

namespace App\Http\Controllers\Api\V1\YourModule;

use App\Http\Controllers\Controller;
use App\Http\Requests\YourModule\GetYourModelRequest;
use App\Models\YourModel;
use App\Services\QueryFilterService;
use Illuminate\Http\JsonResponse;

class GetYourModelController extends Controller
{
    public function __invoke(
        GetYourModelRequest $request, 
        QueryFilterService $filterService
    ): JsonResponse {
        $query = YourModel::query();
        
        // Apply filters
        $filters = $request->getFilters();
        $filterService->apply($query, $filters);
        
        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }
}
```

### Paso 3: Realizar Peticiones desde el Frontend

#### Petición POST/GET con JSON Body

```javascript
// Filtro simple
fetch('/api/v1/channels', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    filters: [
      {field: "name", operator: "EQUAL", value: "Channel 1"}
    ]
  })
})

// Múltiples filtros
fetch('/api/v1/channels', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    filters: [
      {field: "status", operator: "EQUAL", value: "active"},
      {field: "name", operator: "LIKE", value: "%test%"}
    ]
  })
})
```

#### Petición GET con Query Params

```javascript
const filters = JSON.stringify([
  {field: "status", operator: "IN", value: ["active", "pending"]}
]);

fetch(`/api/v1/channels?filters=${encodeURIComponent(filters)}`);
```

## Ejemplos de Uso

### Ejemplo 1: Filtro Simple (EQUAL)

**Request:**
```json
{
  "filters": [
    {"field": "id", "operator": "EQUAL", "value": "1"}
  ]
}
```

**SQL Generado:**
```sql
SELECT * FROM channels WHERE id = '1'
```

---

### Ejemplo 2: Búsqueda con LIKE

**Request:**
```json
{
  "filters": [
    {"field": "name", "operator": "LIKE", "value": "%soporte%"}
  ]
}
```

**SQL Generado:**
```sql
SELECT * FROM channels WHERE name LIKE '%soporte%'
```

---

### Ejemplo 3: Filtro con IN

**Request:**
```json
{
  "filters": [
    {"field": "status", "operator": "IN", "value": ["active", "pending", "processing"]}
  ]
}
```

**SQL Generado:**
```sql
SELECT * FROM channels WHERE status IN ('active', 'pending', 'processing')
```

---

### Ejemplo 4: Múltiples Filtros

**Request:**
```json
{
  "filters": [
    {"field": "status", "operator": "EQUAL", "value": "active"},
    {"field": "coordination_id", "operator": "EQUAL", "value": "5"},
    {"field": "created_at", "operator": "GREATER_THAN", "value": "2024-01-01"}
  ]
}
```

**SQL Generado:**
```sql
SELECT * FROM channels 
WHERE status = 'active' 
AND coordination_id = '5' 
AND created_at > '2024-01-01'
```

---

### Ejemplo 5: BETWEEN

**Request:**
```json
{
  "filters": [
    {"field": "created_at", "operator": "BETWEEN", "value": ["2024-01-01", "2024-12-31"]}
  ]
}
```

**SQL Generado:**
```sql
SELECT * FROM channels WHERE created_at BETWEEN '2024-01-01' AND '2024-12-31'
```

---

### Ejemplo 6: IS_NULL

**Request:**
```json
{
  "filters": [
    {"field": "deleted_at", "operator": "IS_NULL"}
  ]
}
```

**SQL Generado:**
```sql
SELECT * FROM channels WHERE deleted_at IS NULL
```

## Validación y Manejo de Errores

### Errores Comunes

#### 1. Campo No Permitido
```json
{
  "message": "Field 'password' is not allowed for filtering. Allowed fields: id, name, email, status, created_at"
}
```

#### 2. Operador Inválido
```json
{
  "message": "El operador del filtro no es válido. Operadores permitidos: EQUAL, NOT_EQUAL, LIKE, ..."
}
```

#### 3. Formato JSON Inválido
```json
{
  "message": "Invalid JSON format for filters parameter."
}
```

#### 4. Valor Inválido para Operador
```json
{
  "message": "IN operator requires an array value for field: status"
}
```

```json
{
  "message": "BETWEEN operator requires an array with exactly 2 values for field: created_at"
}
```

## Mejores Prácticas

### 1. Siempre Define `$filterableFields`
```php
protected array $filterableFields = [
    'id',
    'name',
    'status',
    // No incluyas campos sensibles como 'password', 'token', etc.
];
```

### 2. Combina con Paginación
```php
public function __invoke(
    GetYourModelRequest $request, 
    QueryFilterService $filterService
): JsonResponse {
    $query = YourModel::query();
    
    $filters = $request->getFilters();
    $filterService->apply($query, $filters);
    
    // Aplicar paginación
    $perPage = $request->input('per_page', 15);
    
    return response()->json([
        'success' => true,
        'data' => $query->paginate($perPage)
    ]);
}
```

### 3. Agrega Relaciones Eager Loading
```php
$query = YourModel::with(['relation1', 'relation2']);

$filters = $request->getFilters();
$filterService->apply($query, $filters);
```

### 4. Validaciones Adicionales
```php
class GetYourModelRequest extends FilterableRequest
{
    protected array $filterableFields = ['id', 'name', 'status'];
    
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'include_deleted' => 'boolean',
            'sort_by' => 'string|in:name,created_at',
        ]);
    }
}
```

## Consideraciones de Seguridad

1. **Whitelist de Campos:** Siempre define `$filterableFields` para prevenir inyección SQL o exposición de datos sensibles.

2. **Sanitización:** Laravel Eloquent ya protege contra inyección SQL, pero siempre valida los datos entrantes.

3. **Rate Limiting:** Considera aplicar rate limiting a endpoints con filtrado complejo.

4. **Índices de Base de Datos:** Asegúrate de tener índices en los campos que serán filtrados frecuentemente.

## Testing

### Test de Ejemplo

```php
public function test_filters_channels_by_status()
{
    Channel::factory()->create(['status' => 'active']);
    Channel::factory()->create(['status' => 'inactive']);
    
    $response = $this->json('GET', '/api/v1/channels', [
        'filters' => [
            ['field' => 'status', 'operator' => 'EQUAL', 'value' => 'active']
        ]
    ]);
    
    $response->assertStatus(200)
             ->assertJsonCount(1, 'data');
}
```

## Extensibilidad

### Agregar Nuevos Operadores

Si necesitas agregar un operador personalizado:

1. Agrega la constante en `Filter.php`:
```php
public const OPERATOR_CUSTOM = 'CUSTOM';
```

2. Agrégalo a `getSupportedOperators()`:
```php
public static function getSupportedOperators(): array
{
    return [
        // ... operadores existentes
        self::OPERATOR_CUSTOM,
    ];
}
```

3. Implementa la lógica en `QueryFilterService.php`:
```php
protected function applyFilter(Builder $query, Filter $filter): void
{
    // ...
    match ($operator) {
        // ... casos existentes
        Filter::OPERATOR_CUSTOM => $this->applyCustomFilter($query, $field, $value),
        // ...
    };
}

protected function applyCustomFilter(Builder $query, string $field, mixed $value): void
{
    // Tu lógica personalizada
}
```

## Soporte

Para dudas o problemas, contacta al equipo de desarrollo.
