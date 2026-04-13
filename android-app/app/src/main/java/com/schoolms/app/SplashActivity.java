package com.schoolms.app;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;

import java.util.ArrayList;
import java.util.List;

public class SplashActivity extends AppCompatActivity {

    private static final long SPLASH_DELAY_MS = 3200;
    private ActivityResultLauncher<String[]> permissionsLauncher;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_splash);

        permissionsLauncher = registerForActivityResult(
            new ActivityResultContracts.RequestMultiplePermissions(),
            result -> openMainScreen()
        );

        new Handler(Looper.getMainLooper()).postDelayed(() -> {
            requestRequiredPermissions();
        }, SPLASH_DELAY_MS);
    }

    private void requestRequiredPermissions() {
        List<String> requiredPermissions = new ArrayList<>();

        requiredPermissions.add(Manifest.permission.CAMERA);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            requiredPermissions.add(Manifest.permission.READ_MEDIA_IMAGES);
        } else {
            requiredPermissions.add(Manifest.permission.READ_EXTERNAL_STORAGE);
        }

        List<String> missingPermissions = new ArrayList<>();
        for (String permission : requiredPermissions) {
            if (ContextCompat.checkSelfPermission(this, permission) != PackageManager.PERMISSION_GRANTED) {
                missingPermissions.add(permission);
            }
        }

        if (missingPermissions.isEmpty()) {
            openMainScreen();
            return;
        }

        permissionsLauncher.launch(missingPermissions.toArray(new String[0]));
    }

    private void openMainScreen() {
        startActivity(new Intent(SplashActivity.this, MainActivity.class));
        finish();
    }
}
