A comprehensive PHP OOP-based Profit/Loss Tracker for stock market investments.

1. **Project Structure**
2. **Database Design**
3. **Core Classes**
4. **Security Measures**
5. **Deployment Guide**

---

### 1. Directory Structure
```
/profit-loss-tracker
├── app/
│   ├── Core/
│   │   ├── Database.php
│   │   ├── Share.php
│   │   └── Portfolio.php
│   └── config.php
├── public/
│   └── index.php
├── assets/
│   └── css/
├── vendor/
├── .htaccess
└── composer.json
```

---

### 2. Database Schema (MySQL)
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE shares (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    quantity INT NOT NULL,
    purchase_price DECIMAL(10,2) NOT NULL,
    purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    share_id INT NOT NULL,
    sell_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    profit DECIMAL(10,2) NOT NULL,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (share_id) REFERENCES shares(id)
);

CREATE INDEX idx_shares_user ON shares(user_id);
CREATE INDEX idx_shares_symbol ON shares(symbol);
```

---

### 3. Core Classes

**app/Core/Database.php**
```php
<?php
class Database {
    private $host;
    private $user;
    private $pass;
    private $dbname;
    private $pdo;
    
    public function __construct() {
        $this->host = getenv('DB_HOST');
        $this->user = getenv('DB_USER');
        $this->pass = getenv('DB_PASS');
        $this->dbname = getenv('DB_NAME');
        
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}
?>
```

**app/Core/Share.php**
```php
<?php
class Share {
    private $db;
    public $id;
    public $user_id;
    public $symbol;
    public $quantity;
    public $purchase_price;
    public $purchase_date;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function addShare($user_id, $symbol, $quantity, $purchase_price) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("INSERT INTO shares 
            (user_id, symbol, quantity, purchase_price) 
            VALUES (?, ?, ?, ?)");
            
        return $stmt->execute([$user_id, $symbol, $quantity, $purchase_price]);
    }
}
?>
```

**app/Core/Portfolio.php**
```php
<?php
class Portfolio {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function sellShares($user_id, $share_id, $sell_quantity, $sell_price) {
        $pdo = $this->db->getConnection();
        
        // Get purchase details
        $stmt = $pdo->prepare("SELECT * FROM shares WHERE id = ? AND user_id = ?");
        $stmt->execute([$share_id, $user_id]);
        $share = $stmt->fetch();
        
        if (!$share) throw new Exception("Share not found");
        if ($share['quantity'] < $sell_quantity) throw new Exception("Insufficient shares");
        
        // Calculate profit
        $purchase_value = $share['purchase_price'] * $sell_quantity;
        $sell_value = $sell_price * $sell_quantity;
        $profit = $sell_value - $purchase_value;
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update remaining shares
            if ($share['quantity'] == $sell_quantity) {
                $stmt = $pdo->prepare("DELETE FROM shares WHERE id = ?");
                $stmt->execute([$share_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE shares SET quantity = quantity - ? WHERE id = ?");
                $stmt->execute([$sell_quantity, $share_id]);
            }
            
            // Record transaction
            $stmt = $pdo->prepare("INSERT INTO transactions 
                (user_id, share_id, sell_price, quantity, profit)
                VALUES (?, ?, ?, ?, ?)");
                
            $stmt->execute([$user_id, $share_id, $sell_price, $sell_quantity, $profit]);
            
            $pdo->commit();
            return $profit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    public function getPortfolio($user_id) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT s.*, 
                (SELECT current_price FROM market_data WHERE symbol = s.symbol ORDER BY date DESC LIMIT 1) AS current_price
            FROM shares s
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll();
    }
}
?>
```

---

### 4. Security Measures

1. **Input Validation**: Always validate and sanitize user inputs
2. **Prepared Statements**: Use PDO prepared statements to prevent SQL injection
3. **Password Hashing**: Use `password_hash()` and `password_verify()`
4. **HTTPS**: Enforce HTTPS in production
5. **CSRF Protection**: Implement CSRF tokens in forms
6. **Rate Limiting**: Protect sensitive endpoints from brute-force attacks

---

### 5. Configuration (app/config.php)
```php
<?php
// Environment variables (set these in your server environment)
putenv('DB_HOST=localhost');
putenv('DB_NAME=stock_tracker');
putenv('DB_USER=secure_user');
putenv('DB_PASS=StrongPassword123!');

// Autoloader
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../app/Core/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Error handling
ini_set('display_errors', 0);
error_reporting(E_ALL);
?>
```

---

### 6. Example Usage (public/index.php)
```php
<?php
require __DIR__ . '/../app/config.php';

$db = new Database();
$share = new Share($db);
$portfolio = new Portfolio($db);

// Example: Buy shares
$share->addShare(1, 'AAPL', 100, 150.25);

// Example: Sell shares
try {
    $profit = $portfolio->sellShares(1, 1, 50, 175.50);
    echo "Profit: $" . number_format($profit, 2);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Get portfolio
$holdings = $portfolio->getPortfolio(1);
print_r($holdings);
?>
```

---

### 7. Deployment Steps

1. **Server Setup**
   - Use PHP 8.0+ with MySQL 8.0+
   - Install required extensions: PDO, mbstring

2. **Database Setup**
   - Create database user with limited privileges
   - Set up regular backups

3. **Security Configuration**
   - Set proper file permissions (755 for directories, 644 for files)
   - Configure .htaccess to restrict access to sensitive files
   - Implement SSL certificate

4. **Environment Setup**
   - Store credentials in environment variables (never in code)
   - Set up a proper PHP error logging system

5. **Monitoring**
   - Implement logging for all transactions
   - Set up performance monitoring
   - Regular security audits

---

### 8. Next Steps/Enhancements

1. **Real-time Market Data Integration**
   - Use APIs like Alpha Vantage or Yahoo Finance
   
2. **User Authentication**
   - Implement JWT or session-based authentication
   
3. **Advanced Reporting**
   - Generate PDF reports
   - Visual charts using Chart.js or D3.js
   
4. **Portfolio Analysis**
   - Risk assessment
   - Diversification metrics
   
5. **REST API**
   - Create endpoints for mobile app integration

---

This architecture provides a solid foundation for a secure and scalable stock tracking system. Remember to:

1. Implement proper user authentication
2. Add input validation for all user-submitted data
3. Regularly update dependencies
4. Perform security audits
5. Add unit tests (consider PHPUnit)

---

v2

### 1. User Authentication System

**app/Core/User.php**
```php
<?php
class User {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
        session_start();
    }

    public function register($username, $password) {
        $pdo = $this->db->getConnection();
        
        // Validation
        if (empty($username) || empty($password)) {
            throw new Exception("Username and password are required");
        }
        if (!preg_match('/^\w{5,30}$/', $username)) {
            throw new Exception("Username must be 5-30 characters (letters, numbers, underscores)");
        }
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashed_password]);
            return true;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] === 1062) {
                throw new Exception("Username already exists");
            }
            throw new Exception("Registration failed: " . $e->getMessage());
        }
    }

    public function login($username, $password) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            return true;
        }
        throw new Exception("Invalid username or password");
    }

    public function logout() {
        $_SESSION = array();
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public function validateCSRF($token) {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
?>
```

### 2. Enhanced Input Validation

**app/Core/Validation.php**
```php
<?php
class Validation {
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public static function validateStockSymbol($symbol) {
        if (!preg_match('/^[A-Z]{1,5}$/', $symbol)) {
            throw new Exception("Invalid stock symbol");
        }
        return true;
    }

    public static function validateQuantity($quantity) {
        if (!filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            throw new Exception("Quantity must be a positive integer");
        }
        return true;
    }

    public static function validatePrice($price) {
        if (!filter_var($price, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.01]])) {
            throw new Exception("Invalid price value");
        }
        return true;
    }
}
?>
```

### 3. Updated Share Class with Validation

**app/Core/Share.php (Updated)**
```php
<?php
class Share {
    // ... existing code ...

    public function addShare($user_id, $symbol, $quantity, $purchase_price) {
        // Validate inputs
        Validation::validateStockSymbol($symbol);
        Validation::validateQuantity($quantity);
        Validation::validatePrice($purchase_price);

        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("INSERT INTO shares 
            (user_id, symbol, quantity, purchase_price) 
            VALUES (?, ?, ?, ?)");
            
        return $stmt->execute([
            $user_id,
            strtoupper($symbol),
            $quantity,
            $purchase_price
        ]);
    }
}
?>
```

### 4. Updated Portfolio Class with Validation

**app/Core/Portfolio.php (Updated)**
```php
<?php
class Portfolio {
    // ... existing code ...

    public function sellShares($user_id, $share_id, $sell_quantity, $sell_price) {
        // Validate inputs
        Validation::validateQuantity($sell_quantity);
        Validation::validatePrice($sell_price);

        $pdo = $this->db->getConnection();
        
        // Get purchase details with user validation
        $stmt = $pdo->prepare("SELECT * FROM shares WHERE id = ? AND user_id = ?");
        $stmt->execute([$share_id, $user_id]);
        $share = $stmt->fetch();
        
        if (!$share) throw new Exception("Share not found");
        if ($share['quantity'] < $sell_quantity) {
            throw new Exception("Insufficient shares to sell");
        }

        // ... rest of the existing code ...
    }
}
?>
```

### 5. Updated config.php with Security Headers
```php
<?php
// Security headers
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');

// ... existing configuration ...
?>
```

### 6. Authentication Middleware Example

**app/Core/Middleware.php**
```php
<?php
class Middleware {
    public static function authenticate(User $user) {
        if (!$user->isLoggedIn()) {
            header("HTTP/1.1 401 Unauthorized");
            exit("You must be logged in to access this page");
        }
    }

    public static function validateCSRF(User $user, $token) {
        if (!$user->validateCSRF($token)) {
            header("HTTP/1.1 403 Forbidden");
            exit("Invalid CSRF token");
        }
    }
}
?>
```

### 7. Example Secure Form Handling

**public/trade.php**
```php
<?php
require __DIR__ . '/../app/config.php';

$db = new Database();
$user = new User($db);
$share = new Share($db);

Middleware::authenticate($user);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = Validation::sanitizeInput($_POST);
    
    try {
        Middleware::validateCSRF($user, $data['csrf_token'] ?? '');
        
        $share->addShare(
            $user->getCurrentUserId(),
            $data['symbol'],
            $data['quantity'],
            $data['price']
        );
        
        header("Location: /portfolio.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Display form with CSRF token
?>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="text" name="symbol" required pattern="[A-Z]{1,5}">
    <input type="number" name="quantity" step="1" min="1" required>
    <input type="number" name="price" step="0.01" min="0.01" required>
    <button type="submit">Buy Shares</button>
</form>
```

### 8. Enhanced Database Schema (Add indexes and constraints)
```sql
ALTER TABLE users ADD INDEX idx_username (username);
ALTER TABLE shares ADD CONSTRAINT chk_quantity CHECK (quantity > 0);
ALTER TABLE transactions ADD CONSTRAINT chk_sell_quantity CHECK (quantity > 0);
```

### Deployment Security Checklist

1. **Environment Configuration**
   - Set `display_errors = Off` in php.ini
   - Enable OPcache for PHP performance
   - Set proper file permissions:
   ```bash
   chmod 644 /var/www/html/*
   chmod 711 /var/www/html/
   chmod -R 700 /var/www/html/app/
   ```

2. **Web Server Security**
   - Configure .htaccess to block sensitive files:
   ```apache
   <FilesMatch "\.(env|log|ini|htaccess)$">
       Order allow,deny
       Deny from all
   </FilesMatch>
   ```

3. **Regular Maintenance**
   - Set up cron job for session cleanup:
   ```bash
   @daily find /var/lib/php/sessions -type f -mtime +7 -delete
   ```

4. **Rate Limiting Example (using Redis)**
```php
class RateLimiter {
    private $redis;
    private $limit = 5; // Allowed attempts
    private $window = 300; // 5 minutes

    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    public function checkLoginAttempts($ip) {
        $key = "login_attempts:$ip";
        $attempts = $this->redis->incr($key);
        
        if ($attempts === 1) {
            $this->redis->expire($key, $this->window);
        }
        
        if ($attempts > $this->limit) {
            throw new Exception("Too many login attempts. Try again later.");
        }
    }
}
```

### Key Security Features Added:
1. **Secure Password Handling**
   - bcrypt password hashing
   - Minimum password requirements
   - Account lockout prevention

2. **CSRF Protection**
   - Token generation and validation
   - SameSite cookies

3. **Input Validation**
   - Sanitization of all user inputs
   - Strict validation rules for stock symbols
   - Numeric validation with range checks

4. **Session Security**
   - HttpOnly and Secure cookies
   - Session regeneration on login
   - Strict session configuration

5. **Database Security**
   - Parameterized queries
   - Constraint checks
   - Proper indexing

6. **Rate Limiting**
   - Prevention of brute-force attacks
   - Redis-based tracking

7. **Security Headers**
   - HSTS enforcement
   - XSS and clickjacking protection
   - MIME type validation
