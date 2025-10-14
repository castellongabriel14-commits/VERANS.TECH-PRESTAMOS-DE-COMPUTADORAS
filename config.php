<?php
// Credenciales de la Base de Datos
$servername = "localhost";
$username = "root"; // O tu usuario de MySQL
$password = "";     // O tu contraseña de MySQL
$dbname = "computadoras_db"; // Nombre de la base de datos optimizada

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>