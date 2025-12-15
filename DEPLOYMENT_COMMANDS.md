# Railway Deployment - Quick Commands Cheat Sheet

## 🚀 Pre-Deployment

### 1. Test Local Backend
```powershell
cd d:\00.1\word-tracker-main\word-tracker-main
php -S localhost:8000 index.php
# Open browser: http://localhost:8000/api/ping.php
# Should see: {"status":"ok","message":"Backend is reachable"}
```

### 2. Test Local Frontend
```powershell
cd d:\00.1\word-tracker-main\word-tracker-main\frontend
npm install
npm start
# Open browser: http://localhost:4200
```

---

## 📦 Push to GitHub

```powershell
cd d:\00.1\word-tracker-main\word-tracker-main

# Check status
git status

# Add all changes
git add .

# Commit with message
git commit -m "Railway deployment fixes - separated backend and frontend"

# Push to main branch
git push origin main
```

---

## ⚙️ Railway Setup Commands

### Backend Service Setup

**In Railway Dashboard:**

1. **Create new project** → "Deploy from GitHub repo"
2. **Select repository**: `word-tracker`
3. **Service name**: `word-tracker-backend`
4. **Root directory**: (leave empty)

**Add MySQL Database:**
- Click "+ New" → "Database" → "MySQL"
- Wait for database to initialize
- Environment variables are auto-created

**Environment Variables:**
```bash
# These are auto-created by Railway MySQL:
MYSQLHOST=(auto)
MYSQLDATABASE=(auto)
MYSQLUSER=(auto)
MYSQLPASSWORD=(auto)
MYSQLPORT=(auto)
```

**Deploy Settings:**
- Build Command: (auto-detected)
- Start Command: `php -S 0.0.0.0:$PORT index.php`

### Frontend Service Setup

**In Same Railway Project:**

1. **Click "+ New"** → "GitHub Repo"
2. **Select repository**: `word-tracker` (same repo)
3. **Service name**: `word-tracker-frontend`
4. **Root directory**: `frontend` ⚠️ IMPORTANT

**Environment Variables:**
```bash
NODE_VERSION=18
```

**Deploy Settings:**
- Build Command: `npm ci && npm run build -- --configuration production`
- Start Command: `npx serve -s dist/word-tracker/browser -l $PORT`

---

## 🔧 Post-Deployment

### 1. Initialize Database

```bash
# Get your backend URL from Railway (example):
https://word-tracker-backend-production.up.railway.app

# Visit in browser:
https://word-tracker-backend-production.up.railway.app/init_railway_db.php

# You should see:
# {"status":"success","message":"Database initialized"}
```

### 2. Update Frontend Environment

```powershell
cd d:\00.1\word-tracker-main\word-tracker-main

# Edit frontend/src/environments/environment.prod.ts
# Replace YOUR_BACKEND_URL with actual Railway backend URL

# Commit and push
git add frontend/src/environments/environment.prod.ts
git commit -m "Update production API URL"
git push origin main
```

### 3. Update CORS

```powershell
# Edit config/cors.php
# Add your Railway frontend URL to $allowedOrigins array:
# 'https://word-tracker-frontend-production.up.railway.app',

# Commit and push
git add config/cors.php
git commit -m "Add Railway frontend to CORS whitelist"
git push origin main
```

---

## ✅ Testing Commands

### Test Backend Health
```bash
# Replace with your actual backend URL
curl https://word-tracker-backend-production.up.railway.app/api/ping.php
```

### Test Backend Login
```bash
# Using PowerShell
$body = @{
    email = "test@example.com"
    password = "password123"
} | ConvertTo-Json

Invoke-RestMethod -Uri "https://word-tracker-backend-production.up.railway.app/api/login.php" -Method POST -Body $body -ContentType "application/json"
```

### Test Frontend
```bash
# Open browser to your Railway frontend URL
https://word-tracker-frontend-production.up.railway.app
```

---

## 🐛 Debugging Commands

### View Railway Logs (via CLI)
```bash
# Install Railway CLI first:
npm install -g @railway/cli

# Login
railway login

# Link to project
railway link

# View backend logs
railway logs --service word-tracker-backend

# View frontend logs
railway logs --service word-tracker-frontend
```

### View Railway Logs (via Dashboard)
```
1. Go to https://railway.app/dashboard
2. Click your project
3. Click service (backend or frontend)
4. Click "Deployments" tab
5. Click latest deployment
6. Click "View Logs"
```

### Check Database Connection
```bash
# Visit in browser:
https://your-backend.railway.app/test_db_connection.php
```

---

## 🔄 Redeploy After Changes

### Auto Deploy (Recommended)
```powershell
# Just push to GitHub - Railway auto-deploys
cd d:\00.1\word-tracker-main\word-tracker-main
git add .
git commit -m "Your change description"
git push origin main
# Railway will automatically deploy both services
```

### Manual Deploy (via Railway Dashboard)
```
1. Go to Railway Dashboard
2. Click service
3. Click "Deployments" tab
4. Click "Deploy" button
```

---

## 📊 Monitoring Commands

### Check Service Status
```bash
railway status
```

### Check Environment Variables
```bash
railway variables
```

### Connect to Database
```bash
railway connect mysql
```

---

## 🛑 Rollback Commands

### Via Railway Dashboard
```
1. Go to service → "Deployments"
2. Find previous successful deployment
3. Click "..." → "Redeploy"
```

### Via Git
```powershell
cd d:\00.1\word-tracker-main\word-tracker-main

# View commit history
git log --oneline

# Rollback to specific commit
git reset --hard COMMIT_HASH

# Force push (⚠️ use carefully)
git push -f origin main
```

---

## 📝 Common URLs

Replace with your actual Railway URLs:

```
Backend Base URL: https://word-tracker-backend-production.up.railway.app
Frontend URL: https://word-tracker-frontend-production.up.railway.app

Health Check: /api/ping.php
DB Health: /api/db-health.php
Init DB: /init_railway_db.php
Login API: /api/login.php
Register API: /api/register.php
```

---

## 💾 Backup Database

### Export Data
```bash
railway connect mysql
# Then in MySQL shell:
mysqldump -u $MYSQLUSER -p$MYSQLPASSWORD $MYSQLDATABASE > backup.sql
```

### Import Data
```bash
railway connect mysql
# Then:
mysql -u $MYSQLUSER -p$MYSQLPASSWORD $MYSQLDATABASE < backup.sql
```

---

## 🔐 Security Checklist

- [ ] Environment variables set (not hardcoded)
- [ ] CORS configured with specific domains
- [ ] Database credentials secure
- [ ] API endpoints authenticated
- [ ] Frontend uses HTTPS
- [ ] Backend uses HTTPS

---

## ⚡ Performance Optimization

### Backend
```php
// Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

### Frontend
```bash
# Build with optimization
npm run build -- --configuration production --optimization=true
```

---

## 📞 Support Resources

- Railway Docs: https://docs.railway.app
- Railway Discord: https://discord.gg/railway
- Angular Docs: https://angular.io/docs
- PHP Docs: https://www.php.net/docs.php

---

*Last Updated: December 2025*
