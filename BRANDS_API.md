# Brands API Documentation

## Overview
The Brands API provides endpoints to manage pharmaceutical brands in the pharmacy POS system. Brands have a one-to-many relationship with drugs.

## Base URL
```
/api/brands
```

## Endpoints

### 1. List All Brands
**GET** `/api/brands`

**Query Parameters:**
- `search` (string): Search in name, description, or country
- `status` (string): Filter by status (`active` or `inactive`)
- `country` (string): Filter by country
- `with_drugs_count` (boolean): Include drugs count (`true`)
- `with_active_drugs_count` (boolean): Include active drugs count (`true`)
- `per_page` (integer): Number of items per page (default: 15)

**Example Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Pfizer",
      "slug": "pfizer",
      "description": "Leading global biopharmaceutical company.",
      "logo": "uploads/brands/pfizer.png",
      "country": "USA",
      "status": "active",
      "created_at": "2025-10-31T00:00:00.000000Z",
      "updated_at": "2025-10-31T00:00:00.000000Z"
    }
  ],
  "current_page": 1,
  "per_page": 15,
  "total": 1
}
```

### 2. Get Single Brand
**GET** `/api/brands/{id}`

**Example Response:**
```json
{
  "id": 1,
  "name": "Pfizer",
  "slug": "pfizer",
  "description": "Leading global biopharmaceutical company.",
  "logo": "uploads/brands/pfizer.png",
  "country": "USA",
  "status": "active",
  "created_at": "2025-10-31T00:00:00.000000Z",
  "updated_at": "2025-10-31T00:00:00.000000Z",
  "drugs": [
    {
      "id": 1,
      "name": "Ibuprofen 200mg",
      "brand_id": 1,
      "generic_name": "Ibuprofen",
      "price": "0.30",
      "status": "active"
    }
  ]
}
```

### 3. Create New Brand
**POST** `/api/brands`

**Required Fields:**
- `name` (string): Brand name

**Optional Fields:**
- `slug` (string): URL slug (auto-generated if not provided, must be unique)
- `description` (text): Brand description
- `logo` (string): Logo image path
- `country` (string): Country of origin
- `status` (string): Status (`active` or `inactive`, default: `active`)

**Example Request:**
```json
{
  "name": "Pfizer",
  "slug": "pfizer",
  "description": "Leading global biopharmaceutical company.",
  "logo": "uploads/brands/pfizer.png",
  "country": "USA",
  "status": "active"
}
```

### 4. Update Brand
**PUT/PATCH** `/api/brands/{id}`

Same fields as create, but all are optional for partial updates.

### 5. Delete Brand
**DELETE** `/api/brands/{id}`

Returns 204 No Content on success.

## Relationship with Drugs

### Get Drugs by Brand
You can filter drugs by brand using the drugs API:
```
GET /api/drugs?brand_id=1
```

### Brand-Drug Relationship
- Each drug can belong to one brand (`brand_id` foreign key)
- Each brand can have many drugs
- When creating/updating drugs, you can specify `brand_id`

## Example Usage

### Create a brand:
```bash
curl -X POST /api/brands \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Pfizer",
    "description": "Leading global biopharmaceutical company.",
    "country": "USA"
  }'
```

### Search brands:
```bash
curl "/api/brands?search=pfizer&status=active"
```

### Get brand with drugs count:
```bash
curl "/api/brands?with_drugs_count=true"
```

### Filter by country:
```bash
curl "/api/brands?country=USA"
```
