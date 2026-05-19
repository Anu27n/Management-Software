# Startup Guide: Web Installer and Android App

This guide explains how to start the web app and how to run or build the Android app.

## 1) Web App: Quick Start (Local)

### Prerequisites
- PHP 8.1 or newer
- Composer
- MySQL or SQLite
- Node.js and npm (for frontend assets if needed)

### Steps
1. Go to project folder:
   - school-management
2. Install dependencies:
   - composer install
3. Create environment file:
   - copy .env.example to .env
4. Generate app key:
   - php artisan key:generate
5. Configure database in .env
6. Run migrations:
   - php artisan migrate --force
7. Start app:
   - php artisan serve --host=127.0.0.1 --port=8000

Open in browser:
- http://127.0.0.1:8000

## 2) Web Installer (Hosting / Fresh Install)

Use the installer when deploying to hosting.

### Installer file location
- school-management/public/installer.php

### Required PHP extensions
- pdo
- pdo_mysql
- mbstring
- openssl
- tokenizer
- json
- curl
- fileinfo
- gd
- zip

### Install flow
1. Upload the Laravel project so that public points to school-management/public.
2. Open installer URL in browser:
   - https://your-domain.com/installer.php
3. Follow steps in installer:
   - requirements check
   - database setup
   - admin account and app URL
   - install
4. After success, delete installer.php for security.

## 3) Android App: Run Debug Build

Project path:
- android-app

### Configure web URL in app
Edit file:
- android-app/app/build.gradle

Set BASE_URL to your live or local web URL.

Examples:
- Live: https://wahs.stxs.cloud
- Emulator local web: http://10.0.2.2:8000

### Build debug APK
From android-app folder:
- ./gradlew assembleDebug

APK output:
- android-app/app/build/outputs/apk/debug/app-debug.apk

## 4) Android App: Signed Release APK and AAB

Release signing is configured in:
- android-app/keystore.properties
- android-app/keystore/wahs-release.jks

Build signed release artifacts:
- ./gradlew assembleRelease bundleRelease

Outputs:
- android-app/app/build/outputs/apk/release/app-release.apk
- android-app/app/build/outputs/bundle/release/app-release.aab

Prepared deliverables (already copied):
- android-app/release-artifacts/WAHS-release-signed.apk
- android-app/release-artifacts/WAHS-release-signed.aab
- android-app/release-artifacts/WAHS-debug.apk

## 5) Notes

- App name is WAHS.
- Splash screen is green with school logo branding.
- Upload/photo selection support and runtime permission flow are enabled.
- Session persistence in WebView was improved using cookie restore/flush handling.

Keystore credentials (needed for future Play Store updates):

storePassword: Wahs@2026Store
keyAlias: wahsrelease
keyPassword: Wahs@2026Key
