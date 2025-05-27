<?php
define('host', 'localhost');
define('user', 'root');
define('pass', '');
define('dbname', 'news_portal');
try {
    $dbh = new PDO("mysql:host=" . host . ";dbname=" . dbname, user, pass);
} catch (PDOException $e) {
    exit("Error: " . $e->getMessage());
}
