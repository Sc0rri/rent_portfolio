# Rent Portfolio

## About the full project

This is a real-world rental platform for bikes and cars built with **Yii2 (PHP)** and **MySQL**.

The system manages:

- **Vehicles** — bikes and cars with brands, models, types, GPS tracking, photos, status and location.
- **Seasons & tariffs** — seasonal pricing with per-day rates (min/max rental days) and monthly rates, including price calculation logic with a monthly ceiling.
- **Bookings (rentals)** — client reservations with date ranges, vehicle assignment, pricing and payments.
- **Clients & documents** — client profiles, contracts, scanned documents and ID verification.
- **Payments & cashboxes** — financial transactions, deposits, expenses and cashbox management.
- **API** — REST API (OpenAPI 3.0) with bearer-token authentication for external consumers.

## About this module

This repository contains a **self-contained excerpt** extracted from a private rental-platform codebase, focused on the **tariff calculation and season management subsystem**:

- Seasonal tariff pricing with configurable minimum/maximum rental days.
- Monthly tariff rates with a monthly ceiling (price cap) for longer rentals.
- REST API endpoints (`/auth`, `/seasons`, `/tariffs/daily`, `/tariffs/month`) with bearer-token authentication and per-user rate limiting.
- Admin UI for managing seasons and tariffs through the web interface.

It has been extracted from the original private codebase and trimmed to the tariff/season domain. Secrets, credentials and unrelated business logic have been removed.

## What is included

- `models/` — ActiveRecord models and search models for tariffs, monthly tariffs, and seasons, including the price-calculation logic.
- `controllers/` — backend (admin) controllers for managing tariffs and seasons through the web interface.
- `views/` — the corresponding admin views (grids, forms, tariff management).
- `components/api/` — a REST API (OpenAPI 3.0 documentation in `api.yaml`) exposing seasons and tariffs to external consumers. It includes a bearer-token authentication endpoint (`POST /auth`) with per-user rate limiting, plus read-only endpoints (`/seasons`, `/tariffs/daily`, `/tariffs/month`).
- `components/api/Api.php`, `components/api/components/DefaultController.php`, `components/api/components/RateLimiter.php`, `components/api/controllers/AuthController.php`, `components/api/components/BearerAuth.php`, `components/api/components/Entity.php` — the PHP side of the module: JSON content negotiation, language selection, per-user time zone, per-user rate limiting on the login attempt, the token issuing/verification pair (`AuthController` generates the `apiToken`, `BearerAuth` resolves it on every request) and the entity serializers.
- `migrations/` — database migrations that create the season, tariff, and monthly-tariff tables, add the rate-limiting and `apiToken` columns to the user table, and register the read-only `api_user` RBAC role used by the API access rules.

## Known limitations (what is NOT included)

- **`models/User.php`** — the user model is **not** included. `AuthController` and `BearerAuth` reference it (via `findByUsername`, `findByApiToken`, `validatePassword`, `generateApiToken`); the full user model contains profile, company, partner, password and RBAC logic that was left out to keep this excerpt focused. The authentication-related migrations (`apiToken`, `api_user` RBAC role) are kept as a skeleton of this system.
- **`models/Vehicle.php`** — the vehicle model is **not** included. The original model is ~1000 lines with mixed responsibilities (GPS, images, contracts, bookings, file uploads). It was not extracted to keep this module self-contained and focused on tariff logic.
- **Reference dictionaries**
- **Financial modules**
- **Configuration files**

## Stack

- PHP 8.x
- Yii2 framework
- MySQL / MariaDB
- OpenAPI 3.0 (REST API documentation)

## Purpose of this repository

This is a **portfolio excerpt** published to demonstrate my code style,
architecture decisions, and technical skills.

## Viewing only — All rights reserved

Copyright © 2026 Alex Puschev (Sc0rri). All rights reserved.

This code is published for **viewing and evaluation only**. No license to
copy, modify, redistribute, or reuse this code is granted. See [LICENSE](LICENSE)
for details.

