# Railway Deployment Architecture

## Current Issues Diagram

```
┌─────────────────────────────────────────────────────────────┐
│  CURRENT PROBLEMATIC SETUP (Single Service)                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Railway Single Service                                     │
│  ┌────────────────────────────────────────────────┐        │
│  │  nixpacks.toml tries to:                       │        │
│  │  1. Install PHP 8.2                            │        │
│  │  2. Install Node.js 20                         │        │
│  │  3. Install npm                                │        │
│  │  4. Run composer install (PHP)                 │        │
│  │  5. cd frontend && npm install (Node)         │        │
│  │  6. cd frontend && npm run build              │        │
│  │  7. Copy build to public/                     │        │
│  │  8. Start: php -S 0.0.0.0:$PORT router.php    │        │
│  └────────────────────────────────────────────────┘        │
│                                                              │
│  ❌ Problems:                                                │
│  - Build conflicts between PHP and Node                     │
│  - Complex routing logic in router.php                      │
│  - If frontend build fails, backend still runs (broken UI)  │
│  - Hard to debug which part is failing                      │
│  - "npm ERR! enoent package.json" if paths are wrong       │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Recommended Solution Diagram

```
┌──────────────────────────────────────────────────────────────────────┐
│  RECOMMENDED SETUP: Separate Services                                │
├──────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌─────────────────────┐    ┌─────────────────────┐                │
│  │  MySQL Database     │    │  Backend Service    │                │
│  │  ┌───────────────┐  │    │  ┌───────────────┐  │                │
│  │  │ word_tracker  │  │◄───┤  │  PHP 8.2      │  │                │
│  │  │ database      │  │    │  │  index.php    │  │                │
│  │  │               │  │    │  │  api/         │  │                │
│  │  │ - users       │  │    │  │  config/      │  │                │
│  │  │ - plans       │  │    │  │               │  │                │
│  │  │ - projects    │  │    │  │  Port: $PORT  │  │                │
│  │  └───────────────┘  │    │  └───────────────┘  │                │
│  │                     │    │                     │                │
│  │  Auto-created env:  │    │  Start:             │                │
│  │  - MYSQLHOST       │    │  php -S 0.0.0.0:    │                │
│  │  - MYSQLUSER       │    │    $PORT index.php  │                │
│  │  - MYSQLPASSWORD   │    │                     │                │
│  │  - MYSQLDATABASE   │    │  Health:            │                │
│  │  - MYSQLPORT       │    │  /api/ping.php      │                │
│  └─────────────────────┘    └─────────────────────┘                │
│                                     ▲                                │
│                                     │ API Calls                      │
│                                     │ (CORS enabled)                 │
│                              ┌──────┴──────────┐                    │
│                              │                 │                    │
│                       ┌──────────────────────────────┐              │
│                       │  Frontend Service            │              │
│                       │  ┌────────────────────────┐  │              │
│                       │  │  Node.js 18            │  │              │
│                       │  │  Angular 17            │  │              │
│                       │  │                        │  │              │
│                       │  │  Build:                │  │              │
│                       │  │  npm ci && npm run     │  │              │
│                       │  │  build --prod          │  │              │
│                       │  │                        │  │              │
│                       │  │  Serve:                │  │              │
│                       │  │  npx serve -s dist/    │  │              │
│                       │  │  word-tracker/browser  │  │              │
│                       │  │                        │  │              │
│                       │  │  Port: $PORT           │  │              │
│                       │  └────────────────────────┘  │              │
│                       │                              │              │
│                       │  Root Directory: frontend    │              │
│                       └──────────────────────────────┘              │
│                                     ▲                                │
│                                     │                                │
│                                ┌────┴────┐                          │
│                                │  Users  │                          │
│                                └─────────┘                          │
│                                                                       │
└──────────────────────────────────────────────────────────────────────┘

URLs:
Backend:  https://word-tracker-backend-production.up.railway.app
Frontend: https://word-tracker-frontend-production.up.railway.app
```

## Data Flow Diagram

```
┌─────────────┐
│   Browser   │
└──────┬──────┘
       │
       │ 1. User visits Frontend URL
       ▼
┌──────────────────────────────────────┐
│  Frontend (Angular SPA)              │
│  https://...-frontend-....railway.app│
│                                      │
│  - Loads static files (HTML/CSS/JS) │
│  - Renders UI                        │
│  - Handles routing                   │
└──────┬───────────────────────────────┘
       │
       │ 2. API Request (e.g., login)
       │    POST /api/login.php
       │    Origin: https://...-frontend-...
       ▼
┌──────────────────────────────────────┐
│  Backend (PHP API)                   │
│  https://...-backend-....railway.app │
│                                      │
│  1. CORS Check (config/cors.php)     │
│     ✓ Origin allowed?                │
│     ✓ Send CORS headers              │
│                                      │
│  2. Route Request (index.php)        │
│     ✓ Parse URI → /api/login.php     │
│     ✓ Load api/login.php             │
│                                      │
│  3. Process Request (api/login.php)  │
│     ✓ Validate credentials           │
│     ├─▼ Query Database               │
│     │  ┌─────────────────┐           │
│     │  │  MySQL Database │           │
│     │  │  - Check users  │           │
│     │  │  - Verify hash  │           │
│     │  └────────┬────────┘           │
│     ◄──────────┘                     │
│     ✓ Generate response              │
│                                      │
│  4. Send JSON Response               │
│     {"status": "success", ...}       │
└──────┬───────────────────────────────┘
       │
       │ 3. API Response
       │    200 OK + JSON data
       ▼
┌──────────────────────────────────────┐
│  Frontend (Angular SPA)              │
│                                      │
│  - Parse JSON response               │
│  - Update UI                         │
│  - Store auth token                  │
│  - Navigate to dashboard             │
└──────────────────────────────────────┘
```

## File Structure Mapping

```
word-tracker-main/
│
├── 📁 Backend Files (Root)
│   ├── index.php                    → Entry point for all API requests
│   ├── router.php                   → (Not used in separate deployment)
│   ├── config.php                   → Database config
│   ├── composer.json                → PHP dependencies
│   ├── nixpacks-backend.toml        → ✨ NEW: Backend build config
│   ├── railway-backend.json         → ✨ NEW: Backend Railway config
│   │
│   ├── 📁 api/                      → API endpoints
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── ping.php                 → Health check
│   │   ├── get_plans.php
│   │   └── ... (46 files total)
│   │
│   ├── 📁 config/
│   │   ├── cors.php                 → ✅ UPDATED: CORS handling
│   │   └── database.php
│   │
│   └── 📁 database/
│       └── schema.sql
│
└── 📁 Frontend Files (frontend/)
    ├── package.json                 → ✅ UPDATED: Added 'serve'
    ├── railway.json                 → ✅ UPDATED: Build command
    │
    ├── 📁 src/
    │   ├── 📁 app/
    │   │   ├── components/
    │   │   └── services/
    │   │
    │   └── 📁 environments/
    │       ├── environment.ts       → Dev config
    │       └── environment.prod.ts  → ⚠️ UPDATE with backend URL
    │
    └── 📁 dist/ (after build)
        └── word-tracker/
            └── browser/             → Built files served by 'serve'
                ├── index.html
                ├── main.*.js
                └── styles.*.css
```

## Deployment Flow

```
┌─────────────────────┐
│  Local Development  │
└──────────┬──────────┘
           │
           │ 1. git add .
           │ 2. git commit -m "..."
           │ 3. git push origin main
           ▼
┌─────────────────────┐
│  GitHub Repository  │
└──────────┬──────────┘
           │
           │ Webhook triggers
           ▼
┌─────────────────────────────────────────┐
│  Railway Auto-Deploy                    │
├─────────────────────────────────────────┤
│                                         │
│  ┌─────────────────┐  ┌──────────────┐ │
│  │ Backend Service │  │ Frontend     │ │
│  │                 │  │ Service      │ │
│  │ 1. Pull repo    │  │ 1. Pull repo │ │
│  │ 2. Root dir: /  │  │ 2. Root dir: │ │
│  │ 3. composer     │  │    frontend/ │ │
│  │    install      │  │ 3. npm ci    │ │
│  │ 4. Start PHP    │  │ 4. npm build │ │
│  │    server       │  │ 5. Serve     │ │
│  │ 5. Health check │  │    static    │ │
│  │    /api/ping.php│  │    files     │ │
│  └────────┬────────┘  └──────┬───────┘ │
│           │                  │         │
│           │  ✅ Deploy       │ ✅      │
│           │     Success      │  Deploy │
│           │                  │  Success│
└───────────┴──────────────────┴─────────┘
           │                  │
           ▼                  ▼
┌─────────────────────────────────────────┐
│  Live Production URLs                   │
│  Backend:  https://...-backend...       │
│  Frontend: https://...-frontend...      │
└─────────────────────────────────────────┘
```

## CORS Flow Explained

```
User Browser (Origin: https://...-frontend-....railway.app)
    │
    │ 1. REQUEST: POST /api/login.php
    │    Headers:
    │      Origin: https://...-frontend-....railway.app
    │      Content-Type: application/json
    ▼
Backend (config/cors.php)
    │
    │ 2. CORS CHECK
    ├─► Is origin in allowedOrigins array?
    │   ├─ YES → Set header: Access-Control-Allow-Origin: {origin}
    │   └─ NO ─┐
    │          │
    │          ├─► Does origin contain 'railway.app'?
    │          │   ├─ YES → Set header: Access-Control-Allow-Origin: {origin}
    │          │   └─ NO ─┐
    │          │          │
    │          │          └─► Set header: Access-Control-Allow-Origin: *
    │
    │ 3. SET CORS HEADERS
    │    Access-Control-Allow-Origin: ...
    │    Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
    │    Access-Control-Allow-Headers: Content-Type, Authorization, ...
    │    Access-Control-Allow-Credentials: true
    ▼
    │ 4. PROCESS REQUEST
    │    - Execute api/login.php
    │    - Query database
    │    - Generate response
    │
    │ 5. RESPONSE
    │    200 OK
    │    Access-Control-Allow-Origin: https://...-frontend-....railway.app
    │    Content-Type: application/json
    │    {"status": "success", ...}
    ▼
User Browser
    │
    └─► ✅ Response accepted (CORS check passed)
```

## Error Resolution Map

```
┌─────────────────────────────────────────────┐
│  Common Deployment Errors                   │
├─────────────────────────────────────────────┤
│                                             │
│  ❌ "npm ERR! enoent package.json"          │
│  ├─► Cause: Wrong Root Directory            │
│  └─► Fix: Set Root Directory = "frontend"   │
│                                             │
│  ❌ "Frontend shows blank page"             │
│  ├─► Cause: Wrong build output path         │
│  └─► Fix: Check dist/word-tracker/browser/  │
│                                             │
│  ❌ "CORS policy error"                     │
│  ├─► Cause: Frontend URL not in CORS list   │
│  └─► Fix: Add to config/cors.php            │
│                                             │
│  ❌ "API returns 404"                       │
│  ├─► Cause: index.php double 404            │
│  └─► Fix: Add 'exit' after require (FIXED)  │
│                                             │
│  ❌ "Database connection failed"            │
│  ├─► Cause: MySQL not linked                │
│  └─► Fix: Add MySQL database to project     │
│                                             │
│  ❌ "Build timeout"                         │
│  ├─► Cause: Installing both PHP + Node      │
│  └─► Fix: Separate into two services        │
│                                             │
└─────────────────────────────────────────────┘
```

---

*This architecture ensures clean separation of concerns, easier debugging, and independent scaling of frontend and backend services.*
