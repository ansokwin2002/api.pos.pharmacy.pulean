# Drugs API Documentation

## Overview
The Drugs API provides endpoints to manage pharmaceutical drugs in the pharmacy POS system.

## Base URL
```
/api/drugs
```

## Endpoints

### 1. List All Drugs
**GET** `/api/drugs`

**Query Parameters:**
- `search` (string): Search in name, generic_name, brand_name, manufacturer, or barcode
- `status` (string): Filter by status (`active` or `inactive`)
- `category_id` (integer): Filter by category ID
- `brand_id` (integer): Filter by brand ID
- `in_stock` (boolean): Filter drugs with quantity > 0 (`true`)
- `expiring_soon` (boolean): Filter drugs expiring soon (`true`)
- `expiring_days` (integer): Number of days for expiring soon filter (default: 30)
- `per_page` (integer): Number of items per page (default: 15)

**Example Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Paracetamol 500mg",
      "slug": "paracetamol-500mg",
      "generic_name": "Paracetamol",
      "brand_name": "Panadol",
      "brand_id": 1,
      "category_id": 3,
      "image": "uploads/drugs/paracetamol.png",
      "unit": "tablet",
      "price": "0.25",
      "cost_price": "0.15",
      "quantity": 1500,
      "expiry_date": "2026-07-15",
      "barcode": "1234567890123",
      "manufacturer": "GSK",
      "dosage": "500mg",
      "instructions": "Take 1 tablet every 6 hours after meals",
      "side_effects": "Nausea, dizziness",
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

### 2. Get Single Drug
**GET** `/api/drugs/{id}`

**Example Response:**
```json
{
  "id": 1,
  "name": "Paracetamol 500mg",
  "slug": "paracetamol-500mg",
  "generic_name": "Paracetamol",
  "brand_name": "Panadol",
  "brand_id": 1,
  "category_id": 3,
  "image": "uploads/drugs/paracetamol.png",
  "unit": "tablet",
  "price": "0.25",
  "cost_price": "0.15",
  "quantity": 1500,
  "expiry_date": "2026-07-15",
  "barcode": "1234567890123",
  "manufacturer": "GSK",
  "dosage": "500mg",
  "instructions": "Take 1 tablet every 6 hours after meals",
  "side_effects": "Nausea, dizziness",
  "status": "active",
  "created_at": "2025-10-31T00:00:00.000000Z",
  "updated_at": "2025-10-31T00:00:00.000000Z"
}
```

### 3. Create New Drug
**POST** `/api/drugs`

**Required Fields:**
- `name` (string): Drug name
- `generic_name` (string): Generic name
- `unit` (string): Unit of measurement
- `price` (decimal): Selling price
- `cost_price` (decimal): Cost price
- `quantity` (integer): Stock quantity
- `expiry_date` (date): Expiry date (format: YYYY-MM-DD, must be after today)

**Optional Fields:**
- `slug` (string): URL slug (auto-generated if not provided)
- `brand_name` (string): Brand name
- `brand_id` (integer): Brand ID (must exist in brands table)
- `category_id` (integer): Category ID
- `image` (string): Image path
- `barcode` (string): Barcode (must be unique)
- `manufacturer` (string): Manufacturer name
- `dosage` (string): Dosage information
- `instructions` (text): Usage instructions
- `side_effects` (text): Side effects
- `status` (string): Status (`active` or `inactive`, default: `active`)

### 4. Update Drug
**PUT/PATCH** `/api/drugs/{id}`

Same fields as create, but all are optional for partial updates.

### 5. Delete Drug
**DELETE** `/api/drugs/{id}`

Returns 204 No Content on success.

## Example Usage

### Create a drug:
```bash
curl -X POST /api/drugs \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Paracetamol 500mg",
    "generic_name": "Paracetamol",
    "brand_name": "Panadol",
    "unit": "tablet",
    "price": 0.25,
    "cost_price": 0.15,
    "quantity": 1500,
    "expiry_date": "2026-07-15"
  }'
```

### Search drugs:
```bash
curl "/api/drugs?search=paracetamol&status=active&in_stock=true"
```

### Get expiring drugs:
```bash
curl "/api/drugs?expiring_soon=true&expiring_days=30"
```

### Filter drugs by brand:
```bash
curl "/api/drugs?brand_id=1"
```

## Relationship with Brands
- Each drug can belong to one brand via `brand_id`
- Use the `/api/brands` endpoint to manage brands
- When creating drugs, you can specify both `brand_name` (text) and `brand_id` (relationship)
