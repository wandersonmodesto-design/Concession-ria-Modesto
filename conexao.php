<?php
$dbHost = "localhost";
$dbUsername = "root";
$dbPassword = "alunolab";
$dbName = "concessionaria";

$conexao = new mysqli(
    $dbHost,
    $dbUsername,
    $dbPassword,
    $dbName
);

if ($conexao->connect_errno) {
    die("Erro na conexão: " . $conexao->connect_error);
}

$conexao->set_charset("utf8mb4");
?>
