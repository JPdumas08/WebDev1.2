<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=web_dev','root','');
foreach ($pdo->query('SHOW TABLES') as $r) {
    echo $r[0] . "\n";
}
