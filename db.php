<?php
// ============================================================================
// Bader Phones / بدر للهواتف - Core Database Connection (db.php)
// Features: PDO Secure Connection, Exception Mode, UTF-8 (utf8mb4),
// Strict Error Handling, Reusable PDO Instance
// ============================================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'bader_phones_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Creates or retrieves a shared singleton PDO database connection.
 * 
 * @return PDO
 * @throws PDOException
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            // Log real error for debugging
            error_log("Database Connection Error: " . $e->getMessage());

            // If running in development, output clear message
            die('<div style="font-family:sans-serif; padding:2rem; background:#fff1f2; color:#be123c; border-radius:12px; margin:2rem auto; max-width:600px; border:1px solid #fecdd3;">'
                . '<h3 style="margin-top:0;">⚠️ تعذر الاتصال بقاعدة البيانات / Database Connection Failed</h3>'
                . '<p>يرجى التأكد من تشغيل خادم MySQL عبر XAMPP واستيراد ملف <code>bader_phones_db.sql</code> بنجاح.</p>'
                . '<p><small>Error: ' . htmlspecialchars($e->getMessage()) . '</small></p>'
                . '</div>');
        }
    }

    return $pdo;
}

// Global connection instance for direct procedural use
$pdo = getDBConnection();
