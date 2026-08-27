# Kebun Tebu MVP - REST API & Bearer Token Authentication Specs

## 1. Authentication Endpoints (Sanctum / OAuth Bearer Token)

### `POST /api/v1/auth/token`
Issue a new API Bearer Token for mobile apps or third-party GIS integrations.

**Headers**:
`Content-Type: application/json`

**Request Body**:
```json
{
  "email": "petugas@kebuntebu.id",
  "password": "password",
  "device_name": "android_field_tablet"
}
```

**Response (200 OK)**:
```json
{
  "status": "success",
  "token_type": "Bearer",
  "access_token": "1|7x9A...token_string",
  "user": {
    "id": 2,
    "name": "Petugas Lapangan",
    "email": "petugas@kebuntebu.id",
    "role": "field_officer"
  }
}
```

---

### `GET /api/v1/auth/me`
Get authenticated user details and active Spatie permissions.

**Headers**:
`Authorization: Bearer <access_token>`

**Response (200 OK)**:
```json
{
  "status": "success",
  "data": {
    "id": 2,
    "name": "Petugas Lapangan",
    "email": "petugas@kebuntebu.id",
    "role": "field_officer",
    "permissions": [
      "create report",
      "view map",
      "export report"
    ]
  }
}
```

---

### `POST /api/v1/auth/logout`
Revoke active API token.

**Headers**:
`Authorization: Bearer <access_token>`

**Response (200 OK)**:
```json
{
  "status": "success",
  "message": "Token API berhasil dicabut (logged out)."
}
```

---

## 2. Spatial & Report Endpoints

### `GET /api/v1/blocks/geojson`
Fetch all active plantation block polygons formatted in GeoJSON FeatureCollection.

### `POST /api/v1/reports/sync`
Batch sync offline draft incident reports collected in the field.
