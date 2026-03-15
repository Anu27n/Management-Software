# Reply to client – "System connecting to different database"

Send something like this (you can paste in WhatsApp/email):

---

**Quick fix (ask them to do this first):**

The app was still using an **old cached config** with the previous database name. We’ve fixed this in the new package and added a one-step clear.

**Do this once:**

1. Upload the new zip (**Mark 3**) and replace the files on the server (or at least replace the `public` folder and the `installer` so you have the new `clear-config-cache.php` and updated installer).

2. **Clear the config cache** so the site uses the database you entered in the installer:
   - Open in the browser: **https://styxcorp.in/school/public/clear-config-cache.php**  
     (replace with your actual site URL if different)
   - You should see a message like “Config cache cleared”.
   - Then **delete** the file **clear-config-cache.php** from the `public` folder on the server (File Manager or FTP).

3. Reload the site and try logging in again.

**If you don’t have the new zip yet:** In File Manager, go to the folder that contains `app`, `config`, and `public` → open **bootstrap/cache** → delete the file **config.php** if it exists. Then reload the site. That has the same effect.

---

**Shorter version (copy-paste):**

The issue was cached config (old DB name). Do this once: open **https://yoursite.com/school/public/clear-config-cache.php** in the browser, then delete that file from the server and reload the site. If you don’t have that file yet, in File Manager go to **bootstrap/cache** and delete **config.php**, then reload.

---
