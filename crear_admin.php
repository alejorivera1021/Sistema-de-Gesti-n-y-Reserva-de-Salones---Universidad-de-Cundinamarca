<?php
require_once 'config/db.php';

$email = 'admin@ucundinamarca.edu.co';
$password = '123456';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verificar si existe por email
$stmt = $pdo->prepare("SELECT id, codigo FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if ($usuario) {
    // Si existe, actualizar contraseña y activar
    $pdo->prepare("UPDATE usuarios SET password = ?, activo = 1 WHERE email = ?")
        ->execute([$hash, $email]);
    echo "✅ Exito: El usuario admin fue actualizado y activado.<br>";
    echo "Código existente: " . $usuario['codigo'];
} else {
    // Si no existe, crear con código único
    $codigo_unico = 'ADM' . time();
    $pdo->prepare("INSERT INTO usuarios (codigo, nombre, email, password, rol, departamento, activo) 
                   VALUES (?, ?, ?, ?, ?, ?, 1)")
        ->execute([$codigo_unico, 'Administrador', $email, $hash, 'admin', 'Sistemas']);
    echo "✅ Exito: El usuario admin fue creado con código: " . $codigo_unico;
}

echo "<br><br>Ya puedes borrar este archivo e iniciar sesion con: $email / $password";
?>