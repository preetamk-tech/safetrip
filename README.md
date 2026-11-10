# SafeTrip - Travel Safer Together 🛡️🚗

SafeTrip is a web-based community travel and safety companion platform designed specifically for solo travellers, women, students, and daily commuters. It facilitates carpooling, group journeys, live trip tracking, and instant emergency alerts.

---

## 🌟 Key Features

- **🚨 1-Click Emergency SOS**: Captures real-time GPS coordinates via HTML5 Geolocation, generates an emergency map link, and instantly dispatches email alerts to trusted emergency contacts.
- **🔗 Live Trip Escort & Tracking**: Generates unique, secure shareable tracking links (`escort.php`) so family and friends can monitor trip progress live without needing an account.
- **⏰ Periodic Safety Check-ins**: Automated interval prompts asking travellers to confirm their safety status while en route.
- **👥 Carpooling & Group Trips**: Create or discover trips with filters for destination, date, transport mode, and exclusive "Women Only" options.
- **💬 In-App Messaging**: Coordinate travel plans securely without revealing personal phone numbers.
- **💰 Trip Expense Splitting**: Log shared travel costs (fuel, food, tolls) and calculate settlements automatically.
- **🤖 SafeTrip AI Assistant**: Integrated AI chatbot powered by Groq (Llama 3) providing 24/7 travel advice, emergency guidelines, and safety tips.
- **👨‍💼 Admin Management**: Comprehensive moderation dashboard for verifying user IDs, tracking active trips, monitoring system health, and reviewing real-time SOS alerts.

---

## 🛠️ Tech Stack & Tools

- **Backend**: Native PHP 8.x (Modular / Prepared Statements with MySQLi)
- **Database**: MySQL / MariaDB (24 relational tables)
- **Frontend**: Responsive HTML5, CSS3 (Modern Glassmorphism & Card UI), Vanilla JavaScript (AJAX / Fetch API)
- **APIs & Libraries**:
  - **Groq Cloud API** (`llama-3.1-8b-instant`) for AI safety assistance
  - **PHPMailer** for OTP verification and automated SOS emergency alerts
  - **HTML5 Geolocation API** & Google Maps integration
  - **Font Awesome 6** & Google Fonts (Poppins)

---

## 🚀 Setup & Installation

### 1. Prerequisites
- PHP 8.0 or higher
- MySQL / MariaDB server
- Web server (Apache, Nginx, or local dev tools like XAMPP / MAMP / Laragon)

### 2. Clone the Repository
```bash
git clone https://github.com/<your-username>/safetrip.git
cd safetrip
```

### 3. Database Configuration
1. Open your MySQL management tool (e.g., phpMyAdmin, MySQL Workbench, or CLI).
2. Create a new database named `safetrip`.
3. Import the SQL file located at:
   ```
   sql/safetrip.sql
   ```

### 4. Environment Setup
1. Copy the sample environment file:
   ```bash
   cp .env.example .env
   ```
2. Open `.env` and fill in your local settings:
   - Database credentials (`DB_NAME`, `DB_USER`, `DB_PASS`)
   - SMTP Mail settings (for OTP and SOS alert emails)
   - Groq API Key (optional, for AI chatbot functionality)

### 5. Run the Application
Place the project inside your web server root (e.g., `htdocs` for XAMPP or `www` for Laragon), or run with PHP's built-in server:
```bash
php -S localhost:8000
```
Then visit: `http://localhost:8000` in your web browser.

---

## 🔒 Security & Privacy

- Sensitive credentials are kept out of version control via `.gitignore`.
- Prepared statements prevent SQL Injection across all queries.
- CSRF protection and secure session management.
- Passwords hashed using standard PHP `password_hash()` (Bcrypt).
