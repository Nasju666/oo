from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service
import time
import os

# Your username list
username_file = "/home/nasju/Desktop/usernames.txt"

# Check if file exists
if not os.path.exists(username_file):
    print(f"ERROR: File not found: {username_file}")
    exit(1)

# Read usernames
usernames = []
with open(username_file, "r") as f:
    usernames = [line.strip() for line in f if line.strip()]
    
print(f"✅ Loaded {len(usernames)} usernames")
print(f"First 5: {usernames[:5]}")

password = "wrongpass123"

# Setup Chrome options
options = webdriver.ChromeOptions()
options.add_argument("--no-sandbox")
options.add_argument("--disable-dev-shm-usage")
options.add_argument("--disable-blink-features=AutomationControlled")
options.add_argument("--window-size=1920,1080")
options.add_experimental_option("excludeSwitches", ["enable-automation"])
options.add_experimental_option('useAutomationExtension', False)

# Uncomment this line to run in background (no browser window)
# options.add_argument("--headless=new")

try:
    print("🚀 Starting Chrome browser...")
    driver = webdriver.Chrome(
        service=Service(ChromeDriverManager().install()), 
        options=options
    )
    
    for i, username in enumerate(usernames):
        print(f"\n📝 [{i+1}/{len(usernames)}] Trying: {username} / {password}")
        
        # Go to login page
        driver.get("https://justiniani.infinityfree.me/index.php?view=login")
        
        # Wait for form
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.NAME, "email"))
        )
        
        # Fill form
        email_field = driver.find_element(By.NAME, "email")
        password_field = driver.find_element(By.NAME, "password")
        
        email_field.clear()
        email_field.send_keys(username)
        password_field.clear()
        password_field.send_keys(password)
        
        # Submit
        password_field.submit()
        
        # Wait for JavaScript to execute
        time.sleep(3)
        
        # Check results
        current_url = driver.current_url
        page_source = driver.page_source
        cookies = driver.get_cookies()
        
        print(f"   URL: {current_url}")
        print(f"   Cookies: {[c['name'] for c in cookies]}")
        
        # Check for success
        if "dashboard" in current_url or "welcome" in page_source.lower():
            print(f"🎉🎉🎉 SUCCESS FOUND! Username: {username}, Password: {password} 🎉🎉🎉")
            with open("success.txt", "w") as f:
                f.write(f"Username: {username}\nPassword: {password}\nURL: {current_url}")
            break
            
        # Check for failure
        elif "Invalid email or password" in page_source:
            print(f"❌ Failed: {username}")
            
        # Check for __test cookie
        else:
            cookie_found = False
            for cookie in cookies:
                if cookie['name'] == '__test':
                    print(f"⚠️  __test cookie set - possible success for {username}")
                    cookie_found = True
                    with open("potential_success.txt", "a") as f:
                        f.write(f"Username: {username}, Password: {password}\n")
            
            if not cookie_found and "login" in current_url:
                print(f"⏳ Still on login page - likely failed")
            elif not cookie_found:
                print(f"❓ Unknown response - check manually")
                # Save page source for debugging
                with open(f"debug_{username}.html", "w") as f:
                    f.write(page_source)
                    
except Exception as e:
    print(f"❌ Error: {e}")
    import traceback
    traceback.print_exc()
    
finally:
    if 'driver' in locals():
        print("\n🔚 Closing browser...")
        driver.quit()
    else:
        print("\n🔚 Driver was not initialized")