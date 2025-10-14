<?php
// =======================================================
// ADVERTENCIA: NUNCA USAR ESTE CÓDIGO EN UN ENTORNO DE 
// PRODUCCIÓN O DE FORMA PERMANENTE. Es solo para generar
// un HASH por única vez.
// =======================================================

// La contraseña que deseas hashear (en este caso, 'admin123')
$password_to_hash = 'admin123';

// Generar el hash utilizando el algoritmo seguro BCRYPT (PASSWORD_DEFAULT)
$hashed_password = password_hash($password_to_hash, PASSWORD_DEFAULT);

echo "<h2>Generador de Hash de Contraseña</h2>";
echo "<p><strong>Contraseña Original:</strong> " . htmlspecialchars($password_to_hash) . "</p>";
echo "<p><strong>Hash Generado (CÓPIALO COMPLETO):</strong></p>";
echo "<pre style='background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc; word-wrap: break-word; font-size: 1.1em;'>";
echo $hashed_password;
echo "</pre>";

echo "<p style='color: #4a90e2; font-weight: bold;'>PASO SIGUIENTE:</p>";
echo "<p>Copia el HASH completo de arriba e insértalo en la tabla `users` para el usuario `admin` usando la consulta SQL:</p>";
echo "<pre>UPDATE users SET password = '" . $hashed_password . "' WHERE username = 'admin';</pre>";
echo "<p>¡Asegúrate de que no haya espacios al copiar!</p>";
?>