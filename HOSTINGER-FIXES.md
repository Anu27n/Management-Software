# Fix 500 Errors & Empty Database on Hostinger

If you get a **500 error** after the installer or after login, or if **the database stays empty** (migrations did not run), use these fixes.

---

## Still getting 500 / mb_split / "Table users doesn't exist"?

Do these in order:

1. **Enable mbstring and zip** (Section 1 below). Without them, the app shows 500 or "Call to undefined function mb_split()".
2. **Re-run the installer** so migrations run: delete the file **`.installed`** from the Laravel root (same folder as `app`, `config`, `public`), then open **`/installer.php`** in the browser and complete all steps. Use **DB host: localhost** and the **exact** database name, username and password from hPanel → Databases.
3. **Fix DB credentials** in `.env` if you see "Access denied" or "Unknown database" (Section 2).

---

## 1. Enable PHP extensions: **mbstring** and **zip**

**Installer shows "Zip Extension FAIL" or log shows `mb_split()`:**  
These extensions are required. Enable both:

1. Log in to **Hostinger hPanel**.
2. Go to **Advanced** → **PHP Configuration** (or **Website** → **PHP Configuration**).
3. Find and **enable**: **mbstring** and **zip**.
4. Save and wait a minute, then reload the installer or your site.

---

## 2. App uses a different database than the one you entered in the installer

**Symptom:** You entered correct DB details in the installer but the site still connects to another database (e.g. old name or wrong credentials).

**Cause:** Laravel is using **cached config** from a previous install. It reads `bootstrap/cache/config.php` instead of `.env`.

**Fix (choose one):**

- **Option A:** In your hosting File Manager or FTP, go to the Laravel root (folder containing `app`, `config`, `public`). Open **`bootstrap/cache`** and **delete** the file **`config.php`** if it exists. Then reload the site.
- **Option B:** In the browser open: **https://yourdomain.com/school/public/clear-config-cache.php** (use your real URL). You should see “Config cache cleared”. Then **delete** the file **`clear-config-cache.php`** from the `public` folder on the server, and reload the site.

After this, the app will use the database settings from your `.env` file (the one the installer wrote).

---

## 3. Fix database connection (Access denied)

**Error in log:** `Access denied for user '...'@'127.0.0.1'` or `...@'localhost'`

**Fix:**
1. In hPanel go to **Databases** → **MySQL Databases**.
2. Note the **exact** database name, username and password (and host if shown).
3. On the server, edit the Laravel **`.env`** file (in the folder that contains `app`, `config`, `public`).
4. Set:
   - `DB_HOST=localhost`  
     (use **localhost**, not 127.0.0.1, on Hostinger)
   - `DB_DATABASE=` your exact database name  
   - `DB_USERNAME=` your exact database username  
   - `DB_PASSWORD=` your exact database password (in quotes if it contains special characters)
5. Save `.env`.
6. Clear config cache: delete the file `bootstrap/cache/config.php` on the server (if it exists), or run `php artisan config:clear` if you have SSH.

---

## 4. Sessions / “sessions table” or DB errors on every page

**Fix:** Use file-based sessions so the app does not need the `sessions` table.

In `.env` set:
```env
SESSION_DRIVER=file
CACHE_STORE=file
```
Then delete `bootstrap/cache/config.php` (or run `php artisan config:clear`).

---

## 5. Re-run the installer with correct data

If the installer already ran but the site still shows 500:

1. On the server, delete the file **`.installed`** (in the same folder as `.env`).
2. Open **yourdomain.com/installer.php** again.
3. In **Step 2** use:
   - **Host:** `localhost`
   - **Database name, username, password:** exactly as in hPanel → MySQL.
4. Complete the installer again.
5. After a successful install, delete **installer.php** from the `public` folder.

---

## 6. Database is empty (migrations did not run)

**Symptom:** Installer says complete but the database has no tables.

**Cause:** **mbstring** was disabled when the installer ran, so migrations never ran.

**Fix:** Enable **mbstring** (section 1). Then: **(A)** Delete **`.installed`** and open **installer.php** again and click Install; or **(B)** Open the **run-migrate.php?t=...** link the installer showed when migration failed (then delete run-migrate.php); or **(C)** Via SSH: `php artisan migrate --force`.

---

## Quick checklist

| Issue | Action |
|-------|--------|
| App uses wrong database (not the one you entered) | Delete **bootstrap/cache/config.php** on the server, or open **clear-config-cache.php** in the browser then delete that file. |
| 500 after any page load | Enable **mbstring** in PHP Configuration. |
| DB empty / migrations not run | Enable **mbstring**, then re-run installer or open run-migrate.php link. |
| 500 on login or after install | Set **DB_HOST=localhost** and correct DB credentials in `.env`. |
| “Sessions table” or DB errors | Set **SESSION_DRIVER=file** and **CACHE_STORE=file** in `.env`, clear config cache. |

If you still see 500, download the **laravel.log** file from `storage/logs/laravel.log` on the server and check the last error message; it will match one of the cases above.
