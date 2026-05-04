# Finance Module

## Overview
Managing wallets, transactions, vouchers, and reward points.

## UC-43: Manage Wallet (Driver Dashboard)
Endpoint: `GET /api/v1/finance/wallet/manage`
Middleware: `auth:sanctum`, `check.account.status`

### Response Format:
```json
{
  "success": true,
  "message": "Tải thông tin ví thành công.",
  "data": {
    "driver_status": {
      "is_online": true,
      "label": "Trực tuyến"
    },
    "wallet": {
      "id": "1",
      "balance": 150000,
      "total_earned": 500000,
      "total_withdrawn": 350000
    },
    "recent_transactions": [
      {
        "id": "10",
        "type": 1,
        "type_label": "Thu nhập từ chuyến đi",
        "amount": 50000,
        "symbol": "+",
        "description": "Thu nhập từ chuyến đi #XE123",
        "created_at": "2026-04-22T13:47:27+07:00"
      }
    ]
  }
}
```

## Realtime Events
Channel: `ride.communication.events`
Event: `wallet.updated`
Payload Example:
```json
{
  "event": "wallet.updated",
  "user_id": "1",
  "balance": 150000,
  "occurred_at": "2026-04-22T13:47:27+07:00"
}
```
