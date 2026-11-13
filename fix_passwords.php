<?php
require_once 'config/db.php';

// La contraseña que quieres usar para todos
$password = "123456"; 

// Generar el hash correcto para TU servidor
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h3>Generando nuevo hash...</h3>";
echo "Hash: " . $hash . "<br><br>";

// Actualizar TODOS los usuarios en la base de datos
$stmt = $pdo->prepare("UPDATE usuarios SET password = ?");
if ($stmt->execute([$hash])) {
    echo "✅ <b>¡Éxito!</b> Las contraseñas de todos los usuarios han sido actualizadas.<br>";
    echo "Ahora puedes entrar con la contraseña: <b>123456</b>";
} else {
    echo "❌ Error al actualizar la base de datos.";
}
?>