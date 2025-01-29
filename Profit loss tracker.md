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

Would you like me to elaborate on any specific part or add additional features to the implementation?
