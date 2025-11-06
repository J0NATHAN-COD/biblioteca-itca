<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM usuarios WHERE username = :username AND password = :password AND activo = TRUE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        
        $_SESSION['message'] = '¡Bienvenido ' . $user['nombre'] . '!';
        $_SESSION['message_type'] = 'success';
        
        header('Location: dashboard.php');
        exit();
    } else {
        $_SESSION['message'] = 'Usuario o contraseña incorrectos';
        $_SESSION['message_type'] = 'danger';
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="login-container">
            <div class="login-header">
                <h3>Iniciar Sesión</h3>
                <p class="text-muted">Sistema de Biblioteca ITCA</p>
            </div>
            
            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario:</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Ingresar</button>
                </div>
            </form>
            
            <div class="mt-3 text-center">
                <small class="text-muted">
                    Usuario demo: <strong>admin</strong> / Contraseña: <strong>admin123</strong>
                </small>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>