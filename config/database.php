<?php
//definindo variaveis de ambiente
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Usuário padrão do XAMPP
define('DB_PASS', ''); // Senha padrão do XAMPP (vazia)
define('DB_NAME', 'gerenciador_usuarios');

$con = new mysqli(DB_HOST , DB_NAME , DB_PASS , DB_USER);

if ($conn->connect_error) {
    die("falha na conexão: ". $conn->connect_error);
}

$conn->set_charset ("utf8mb4")


?>