# Android WebView App - SchoolMS

This is a native Android wrapper for the School Management System web application using WebView.

## Setup Instructions

### 1. Prerequisites
- Android Studio (Arctic Fox or later)
- Android SDK 34
- Java 8+

### 2. Configure Server URL

Open `app/build.gradle` and change the `BASE_URL` to your server's URL:

```gradle
buildConfigField "String", "BASE_URL", "\"https://your-actual-server-url.com\""
```

**For local development with Android Emulator:**
```gradle
buildConfigField "String", "BASE_URL", "\"http://10.0.2.2:8000\""
```
(10.0.2.2 maps to localhost on the host machine from the Android emulator)

**For Codespace deployment:**
```gradle
buildConfigField "String", "BASE_URL", "\"https://your-codespace-url-8000.preview.app.github.dev\""
```

### 3. Build & Run

1. Open this folder in Android Studio
2. Wait for Gradle sync to complete
3. Connect a device or start an emulator
4. Click **Run** (or press Shift+F10)

## Features

- **Full WebView integration** - Loads your school management web app
- **Splash screen** - Branded loading screen on app start
- **Swipe to refresh** - Pull down to reload the page
- **Back navigation** - Hardware back button navigates within the web app
- **Offline handling** - Shows friendly error screen when no internet
- **Cookie persistence** - Login sessions are maintained
- **Progress bar** - Shows page loading progress at the top
- **Security** - HTTPS enforced, no mixed content, no file access

## App Structure

```
app/src/main/
├── java/com/schoolms/app/
│   ├── MainActivity.java      # WebView container
│   └── SplashActivity.java    # Splash screen
├── res/
│   ├── layout/
│   │   ├── activity_main.xml    # Main layout with WebView
│   │   └── activity_splash.xml  # Splash layout
│   ├── values/
│   │   ├── strings.xml
│   │   ├── colors.xml
│   │   └── themes.xml
│   ├── drawable/                # Vector icons
│   └── xml/
│       └── network_security_config.xml
└── AndroidManifest.xml
```

## Generating Signed APK

1. In Android Studio: **Build → Generate Signed Bundle / APK**
2. Create a keystore (or use existing)
3. Select **APK** and **release** build type
4. Find the APK in `app/build/outputs/apk/release/`
