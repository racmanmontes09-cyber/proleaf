<?php
// test_db.php
$host = '153.92.15.71';
$dbname = 'u575872597_proleaf';
$username = 'u575872597_proleaf';
$password = 'Proleafv1'; // CHANGE THIS!

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=3306", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connected successfully!\n";
    echo "📊 Database: $dbname\n";
    echo "🔌 Host: $host\n\n";
    
    $tables = $pdo->query('SHOW TABLES');
    $tableCount = 0;
    while($row = $tables->fetch(PDO::FETCH_NUM)) {
        $tableCount++;
        echo "📋 Table: " . $row[0] . "\n";
    }
    
    if($tableCount == 0) {
        echo "⚠️  No tables found. You may need to run migrations.\n";
    } else {
        echo "\n✅ Found $tableCount tables.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Connection failed:\n";
    echo "Error: " . $e->getMessage() . "\n";
}
?>