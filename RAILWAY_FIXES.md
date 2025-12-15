# Railway Deployment - Complete Fix Guide

## 🔍 Issues Identified & Fixed

### Issue 1: Monolithic Deployment (Backend + Frontend Together)
**Problem:** Trying to build and serve both PHP backend and Angular frontend in one Railway service causes:
- Build conflicts between Node.js and PHP
- Complex routing logic
- Harder to debug and scale

**Solution:** Deploy as TWO separate Railway services

---

## 🎯 Recommended Deployment Strategy

### **Option A: Two Separate Railway Services** (RECOMMENDED)

#### Service 1: PHP Backend Only
#### Service 2: Angular Frontend Only

### **Option B: Backend on Railway + Frontend on Vercel** (ALTERNATIVE)

---

## 📝 OPTION A: Two Railway Services Setup

### Step 1: Deploy Backend Service

#### 1.1 Create Backend Railway Service
```bash
# In Railway Dashboard:
# 1. Create New Project → "Deploy from GitHub"
# 2. Select your repository
# 3. Service Name: "word-tracker-backend"
```

#### 1.2 Add MySQL Database
```bash
# In Railway Project:
# Click "+ New" → "Database" → "MySQL"
# This automatically creates environment variables:
# - MYSQLHOST
# - MYSQLDATABASE
# - MYSQLUSER
# - MYSQLPASSWORD
# - MYSQLPORT
```

#### 1.3 Configure Backend Service
```yaml
Root Directory: (leave empty - project root)
Start Command: (auto-detected from nixpacks)
```

#### 1.4 Add Backend Environment Variables
```bash
# Go to Backend Service → Variables
# Add these variables:

# PHP-specific
PHP_VERSION=8.2

# App Config (IMPORTANT)
DEPLOY_MODE=backend-only
```

#### 1.5 Update Backend Files

**File: `railway-backend.json`** (NEW FILE - Create this)
```json
{
    "$schema": "https://railway.app/railway.schema.json",
    "build": {
        "builder": "NIXPACKS"
    },
    "deploy": {
        "startCommand": "php -S 0.0.0.0:$PORT index.php",
        "healthcheckPath": "/api/ping.php",
        "healthcheckTimeout": 100,
        "restartPolicyType": "ON_FAILURE",
        "restartPolicyMaxRetries": 10
    }
}
```

**File: `nixpacks-backend.toml`** (NEW FILE)
```toml
[phases.setup]
nixPkgs = ["php82"]

[phases.install]
cmds = [
  "composer install --no-dev --optimize-autoloader --ignore-platform-reqs"
]

[start]
cmd = "php -S 0.0.0.0:$PORT index.php"
```

#### 1.6 Update index.php (Fix Double 404)

**Current Issue:** Lines 44-52 in index.php always return 404 after serving API files

**Fixed index.php:**
```php
<?php
// backend-php/index.php

// 1. Init Configuration
require_once 'config/cors.php';
require_once 'config/database.php';

// Handle Preflight and CORS headers
handleCors();

// 2. Parse URL to determine API Endpoint
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = parse_url($request_uri, PHP_URL_PATH);
$filename = basename($path);

// If no extension, assume .php
if (strpos($filename, '.') === false) {
    $filename .= '.php';
}

// Security: Prevent directory traversal
$filename = basename($filename);

$apiFile = __DIR__ . '/api/' . $filename;

if (file_exists($apiFile)) {
    require $apiFile;
    exit; // IMPORTANT: Exit after serving API file
}

// 3. Fallback / 404 - Only reached if file not found
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    "message" => "API Endpoint not found",
    "status" => "error",
    "path" => $request_uri,
    "looking_for" => $apiFile
]);
?>
```

#### 1.7 Initialize Database
Once backend is deployed:
1. Get backend URL: `https://word-tracker-backend-production.up.railway.app`
2. Visit: `https://your-backend-url.railway.app/init_railway_db.php`
3. Verify tables are created

---

### Step 2: Deploy Frontend Service

#### 2.1 Create Frontend Railway Service
```bash
# In Same Railway Project:
# Click "+ New" → "GitHub Repo" → Select same repository
# Service Name: "word-tracker-frontend"
```

#### 2.2 Configure Frontend Service
```yaml
Root Directory: frontend
Build Command: npm ci && npm run build
Start Command: npx serve -s dist/word-tracker/browser -l $PORT
```

#### 2.3 Add Frontend Environment Variables
```bash
# Go to Frontend Service → Variables
NODE_VERSION=18
```

#### 2.4 Update Frontend package.json

**Add `serve` to dependencies:**
```json
{
    "name": "word-tracker",
    "version": "0.0.0",
    "engines": {
        "node": ">=18.13.0"
    },
    "scripts": {
        "ng": "ng",
        "start": "ng serve",
        "build": "ng build",
        "watch": "ng build --watch --configuration development",
        "test": "ng test"
    },
    "private": true,
    "dependencies": {
        "@angular/animations": "^17.2.0",
        "@angular/common": "^17.2.0",
        "@angular/compiler": "^17.2.0",
        "@angular/core": "^17.2.0",
        "@angular/forms": "^17.2.0",
        "@angular/platform-browser": "^17.2.0",
        "@angular/platform-browser-dynamic": "^17.2.0",
        "@angular/router": "^17.2.0",
        "rxjs": "~7.8.0",
        "tslib": "^2.3.0",
        "zone.js": "~0.14.3",
        "serve": "^14.2.1"
    },
    "devDependencies": {
        "@angular-devkit/build-angular": "^17.2.0",
        "@angular/cli": "^17.2.0",
        "@angular/compiler-cli": "^17.2.0",
        "@types/jasmine": "~5.1.0",
        "@types/node": "^18.18.0",
        "autoprefixer": "^10.4.22",
        "jasmine-core": "~5.1.0",
        "karma": "~6.4.0",
        "karma-chrome-launcher": "~3.2.0",
        "karma-coverage": "~2.2.0",
        "karma-jasmine": "~5.1.0",
        "karma-jasmine-html-reporter": "~2.1.0",
        "postcss": "^8.5.6",
        "tailwindcss": "^3.4.19",
        "typescript": "~5.3.2"
    }
}
```

#### 2.5 Update Frontend Railway Config

**File: `frontend/railway.json`** (UPDATE)
```json
{
    "$schema": "https://railway.app/railway.schema.json",
    "build": {
        "builder": "NIXPACKS",
        "buildCommand": "npm ci && npm run build -- --configuration production"
    },
    "deploy": {
        "startCommand": "npx serve -s dist/word-tracker/browser -l $PORT",
        "restartPolicyType": "ON_FAILURE",
        "restartPolicyMaxRetries": 5
    }
}
```

#### 2.6 Update Frontend Environment

**AFTER** you get your backend URL from Railway, update:

**File: `frontend/src/environments/environment.prod.ts`**
```typescript
export const environment = {
    production: true,
    apiUrl: 'https://word-tracker-backend-production.up.railway.app'
    // ☝️ Replace with your ACTUAL backend Railway URL
};
```

**Then commit and push:**
```bash
cd d:\00.1\word-tracker-main\word-tracker-main
git add frontend/src/environments/environment.prod.ts
git commit -m "Update production API URL"
git push
```

---

### Step 3: Update CORS Configuration

#### 3.1 Update cors.php with Frontend URL

**File: `config/cors.php`** (UPDATE)
```php
<?php
// backend-php/config/cors.php

function handleCors()
{
    // Check if headers have already been sent
    if (headers_sent()) {
        return;
    }

    // Allowed origins for CORS
    $allowedOrigins = [
        'http://localhost:4200',           // Local Angular dev
        'http://localhost',                // Local XAMPP
        'http://localhost:8000',           // Alternative local port
        
        // ADD YOUR RAILWAY FRONTEND URL HERE:
        'https://word-tracker-frontend-production.up.railway.app',
        
        // Vercel (if using)
        'https://word-tracker.vercel.app',
    ];

    // Get the origin from the request
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

    // Check if origin is allowed
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: {$origin}");
    } elseif (getenv('RAILWAY_ENVIRONMENT') || strpos($origin, 'railway.app') !== false) {
        // Allow all Railway domains in Railway environment
        header("Access-Control-Allow-Origin: {$origin}");
    } elseif (empty($origin) || $origin === 'null') {
        // For direct API access (testing)
        header("Access-Control-Allow-Origin: *");
    } else {
        // Default: allow all for development (remove in production if needed)
        header("Access-Control-Allow-Origin: *");
    }

    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400"); // Cache preflight for 1 day

    // Handle Preflight Options Request immediately
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
}
?>
```

---

## 📝 OPTION B: Backend Railway + Frontend Vercel

### Step 1: Deploy Backend (Same as Option A)
Follow Option A Steps 1.1 - 1.7

### Step 2: Deploy Frontend to Vercel

#### 2.1 Create Vercel Account
1. Go to https://vercel.com
2. Sign up with GitHub

#### 2.2 Import Project
1. Click "Add New Project"
2. Import your GitHub repository
3. Configure:

```yaml
Framework Preset: Angular
Root Directory: frontend
Build Command: npm run build
Output Directory: dist/word-tracker/browser
Install Command: npm ci
```

#### 2.3 Add Environment Variables
```bash
# In Vercel Project Settings → Environment Variables:
BACKEND_URL=https://word-tracker-backend-production.up.railway.app
```

#### 2.4 Update environment.prod.ts
```typescript
export const environment = {
    production: true,
    apiUrl: 'https://word-tracker-backend-production.up.railway.app'
};
```

#### 2.5 Deploy
```bash
git add frontend/src/environments/environment.prod.ts
git commit -m "Update API URL for Vercel"
git push
```

Vercel will auto-deploy on push!

---

## 🐛 Common Errors & Solutions

### Error 1: "Cannot find module 'serve'"
**Cause:** `serve` not in package.json dependencies
**Fix:** Add to frontend/package.json dependencies:
```json
"serve": "^14.2.1"
```

### Error 2: Frontend shows blank page
**Cause:** Wrong output directory in build command
**Fix:** Check Angular version output:
- Angular 17+: `dist/word-tracker/browser`
- Angular 16-: `dist/word-tracker`

### Error 3: CORS errors in browser console
**Cause:** Backend not allowing frontend domain
**Fix:** Add frontend URL to `config/cors.php` allowedOrigins array

### Error 4: API returns 404
**Cause:** Wrong API endpoint path
**Fix:** Ensure frontend calls `/api/endpoint.php` not `/endpoint.php`

### Error 5: Database connection failed
**Cause:** MySQL not linked to backend service
**Fix:** In Railway, ensure MySQL database is in same project and environment variables are set

### Error 6: Build fails with "npm ERR! enoent package.json"
**Cause:** Wrong root directory set in Railway
**Fix:** 
- Backend: Root Directory = (empty/root)
- Frontend: Root Directory = `frontend`

---

## ✅ Verification Checklist

### Backend
- [ ] Railway backend service deployed
- [ ] MySQL database created and linked
- [ ] Environment variables set (MYSQLHOST, MYSQLUSER, etc.)
- [ ] Health check passes: `https://your-backend.railway.app/api/ping.php`
- [ ] Database initialized: `/init_railway_db.php` run successfully
- [ ] Test login: `POST /api/login.php`

### Frontend
- [ ] Frontend service deployed (Railway or Vercel)
- [ ] Build completes successfully
- [ ] `environment.prod.ts` has correct backend URL
- [ ] Frontend loads without errors
- [ ] Can access login/register pages
- [ ] API calls work (check Network tab)

### CORS
- [ ] No CORS errors in browser console
- [ ] OPTIONS preflight requests return 200
- [ ] Frontend domain added to CORS whitelist

---

## 🚀 Quick Command Reference

### Push to GitHub
```bash
cd d:\00.1\word-tracker-main\word-tracker-main
git add .
git commit -m "Railway deployment fixes"
git push origin main
```

### Test Backend Locally
```bash
cd d:\00.1\word-tracker-main\word-tracker-main
php -S localhost:8000 index.php
# Visit: http://localhost:8000/api/ping.php
```

### Test Frontend Locally
```bash
cd d:\00.1\word-tracker-main\word-tracker-main\frontend
npm install
npm start
# Visit: http://localhost:4200
```

### View Railway Logs
```bash
# In Railway Dashboard:
# 1. Click on service
# 2. Go to "Deployments" tab
# 3. Click latest deployment
# 4. Click "View Logs"
```

---

## 📊 Expected Railway Setup

```
Railway Project: word-tracker
├── MySQL Database
│   └── Environment Variables (auto-created)
│       ├── MYSQLHOST
│       ├── MYSQLDATABASE
│       ├── MYSQLUSER
│       ├── MYSQLPASSWORD
│       └── MYSQLPORT
│
├── Backend Service (word-tracker-backend)
│   ├── Root Directory: (empty)
│   ├── Build: nixpacks (PHP 8.2)
│   ├── Start: php -S 0.0.0.0:$PORT index.php
│   └── URL: https://word-tracker-backend-production.up.railway.app
│
└── Frontend Service (word-tracker-frontend)
    ├── Root Directory: frontend
    ├── Build: npm ci && npm run build
    ├── Start: npx serve -s dist/word-tracker/browser -l $PORT
    └── URL: https://word-tracker-frontend-production.up.railway.app
```

---

## 💡 Pro Tips

1. **Always test locally first** before deploying
2. **Use Railway CLI** for easier debugging: `railway logs`
3. **Monitor costs**: Railway charges based on usage
4. **Set up domains**: Add custom domains in Railway Settings
5. **Use environment variables**: Never hardcode URLs or credentials
6. **Enable auto-deploy**: Enable in Railway Settings → GitHub integration
7. **Version control**: Always commit before deploying

---

## 🆘 Still Having Issues?

1. **Check Railway Logs**: Most errors are visible in deployment logs
2. **Test endpoints individually**: Use Postman/curl to test backend APIs
3. **Browser DevTools**: Check Network tab for failed requests
4. **Database connection**: Run `/test_db_connection.php` on backend
5. **CORS**: Check browser console for CORS-specific errors

---

## 📱 Next Steps After Deployment

1. **Test all features**: Login, Register, Create Plans, etc.
2. **Set up monitoring**: Use Railway's built-in monitoring
3. **Configure custom domain**: Set up your own domain name
4. **Set up CI/CD**: Enable automatic deployments on push
5. **Backup database**: Set up regular backups
6. **Monitor performance**: Track API response times

---

*Last Updated: December 2025*
*Railway Version: Latest*
*Angular Version: 17.2.0*
*PHP Version: 8.2*
