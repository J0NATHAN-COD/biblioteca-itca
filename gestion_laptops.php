<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();

$message = "";
$error = "";

// Procesar formulario de nueva laptop
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nueva_laptop"])){
    $codigo = trim($_POST["codigo"]);
    $modelo = trim($_POST["modelo"]);
    $estado = trim($_POST["estado"]);
    $fecha_ingreso = trim($_POST["fecha_ingreso"]);
    
    // VALIDACIONES MEJORADAS
    if(empty($codigo) || empty($modelo) || empty($estado) || empty($fecha_ingreso)){
        $error = "❌ Por favor complete TODOS los campos obligatorios.";
    } else {
        // Verificar si el código de laptop ya existe
        $query = "SELECT id FROM laptops WHERE codigo = :codigo";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":codigo", $codigo);
        $stmt->execute();
        
        if($stmt->rowCount() > 0){
            $error = "❌ El código de laptop <strong>'$codigo'</strong> ya existe en la base de datos. Por favor, use un código diferente.";
        } else {
            // Insertar nueva laptop
            $query = "INSERT INTO laptops (codigo, modelo, estado, fecha_ingreso) 
                      VALUES (:codigo, :modelo, :estado, :fecha_ingreso)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":codigo", $codigo);
            $stmt->bindParam(":modelo", $modelo);
            $stmt->bindParam(":estado", $estado);
            $stmt->bindParam(":fecha_ingreso", $fecha_ingreso);
            
            if($stmt->execute()){
                $message = "✅ Laptop <strong>'$codigo'</strong> registrada exitosamente.";
                
                // Limpiar el formulario después de éxito
                $_POST = array();
            } else {
                $error = "❌ Error al registrar la laptop. Por favor, intente nuevamente.";
            }
        }
    }
}

// Procesar actualización de estado
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["actualizar_estado"])){
    $laptop_id = trim($_POST["laptop_id"]);
    $estado = trim($_POST["estado"]);
    
    if(empty($laptop_id) || empty($estado)){
        $error = "❌ Error: Datos incompletos para actualizar el estado.";
    } else {
        // Obtener información de la laptop para el mensaje
        $query_info = "SELECT codigo FROM laptops WHERE id = :laptop_id";
        $stmt_info = $db->prepare($query_info);
        $stmt_info->bindParam(":laptop_id", $laptop_id);
        $stmt_info->execute();
        $laptop_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
        $laptop_codigo = $laptop_info['codigo'];
        
        // Actualizar estado de la laptop
        $query = "UPDATE laptops SET estado = :estado WHERE id = :laptop_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":laptop_id", $laptop_id);
        
        if($stmt->execute()){
            $message = "✅ Estado de la laptop <strong>'$laptop_codigo'</strong> actualizado exitosamente a <strong>'$estado'</strong>.";
        } else {
            $error = "❌ Error al actualizar el estado de la laptop.";
        }
    }
}

// Obtener todas las laptops
$laptops = [];
$query = "SELECT * FROM laptops ORDER BY codigo";
$stmt = $db->prepare($query);
$stmt->execute();
$laptops = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar laptops por estado para mostrar en mensajes informativos
$estados_count = [];
$query_estados = "SELECT estado, COUNT(*) as total FROM laptops GROUP BY estado";
$stmt_estados = $db->prepare($query_estados);
$stmt_estados->execute();
$estados_count = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Laptops - ITCA FEPADE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        body {
            background-color: #f5f5f5;
        }
        .header {
            background-color: #d32f2f;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 24px;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-info span {
            margin-right: 15px;
        }
        .logout-btn {
            background-color: white;
            color: #d32f2f;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .nav {
            background-color: #b71c1c;
            padding: 10px;
        }
        .nav ul {
            list-style: none;
            display: flex;
        }
        .nav li {
            margin-right: 20px;
        }
        .nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .nav a:hover, .nav a.active {
            background-color: #d32f2f;
        }
        .container {
            padding: 20px;
        }
        .section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .section h2 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #d32f2f;
            padding-bottom: 10px;
        }
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box h3 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        .info-box ul {
            list-style-type: none;
        }
        .info-box li {
            padding: 5px 0;
            color: #555;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #d32f2f;
            outline: none;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .btn {
            background-color: #d32f2f;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #b71c1c;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f9f9f9;
            color: #333;
        }
        .estado-disponible {
            color: #28a745;
            font-weight: bold;
        }
        .estado-prestado {
            color: #ffc107;
            font-weight: bold;
        }
        .estado-mantenimiento {
            color: #dc3545;
            font-weight: bold;
        }
        .action-btn {
            background-color: #d32f2f;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        .action-btn:hover {
            background-color: #b71c1c;
        }
        .required {
            color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ITCA FEPADE - Sistema de Biblioteca</h1>
        <div class="user-info">
            <span>Bienvenido, <?php echo $_SESSION["nombre"]; ?></span>
            <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="nav">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="gestion_prestamos.php">Gestión de Préstamos</a></li>
            <li><a href="gestion_laptops.php" class="active">Gestión de Laptops</a></li>
            <li><a href="reportes.php">Reportes</a></li>
        </ul>
    </div>
    
    <div class="container">
        <?php if(!empty($message)): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>📊 Resumen de Laptops en Sistema</h3>
            <ul>
                <li>✅ Total de laptops registradas: <strong><?php echo count($laptops); ?></strong></li>
                <?php foreach($estados_count as $estado): ?>
                    <li>
                        <?php 
                        if($estado['estado'] == 'disponible') echo '🟢';
                        else if($estado['estado'] == 'prestado') echo '🟡';
                        else if($estado['estado'] == 'mantenimiento') echo '🔴';
                        ?>
                        Laptops <?php echo $estado['estado']; ?>: <strong><?php echo $estado['total']; ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="section">
            <h2>➕ Agregar Nueva Laptop</h2>
            <form method="post">
                <input type="hidden" name="nueva_laptop" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label for="codigo">Código de Laptop <span class="required">*</span></label>
                        <input type="text" id="codigo" name="codigo" value="<?php echo isset($_POST['codigo']) ? htmlspecialchars($_POST['codigo']) : ''; ?>" required placeholder="Ej: LAP001, LAP002">
                        <small style="color: #666;">Este código debe ser único en el sistema</small>
                    </div>
                    <div class="form-group">
                        <label for="modelo">Modelo <span class="required">*</span></label>
                        <input type="text" id="modelo" name="modelo" value="<?php echo isset($_POST['modelo']) ? htmlspecialchars($_POST['modelo']) : ''; ?>" required placeholder="Ej: Dell Latitude 3420, HP ProBook 450">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="estado">Estado <span class="required">*</span></label>
                        <select id="estado" name="estado" required>
                            <option value="">Seleccione un estado</option>
                            <option value="disponible" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'disponible') ? 'selected' : ''; ?>>🟢 Disponible</option>
                            <option value="prestado" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'prestado') ? 'selected' : ''; ?>>🟡 Prestado</option>
                            <option value="mantenimiento" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'mantenimiento') ? 'selected' : ''; ?>>🔴 En Mantenimiento</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha_ingreso">Fecha de Ingreso <span class="required">*</span></label>
                        <input type="date" id="fecha_ingreso" name="fecha_ingreso" value="<?php echo isset($_POST['fecha_ingreso']) ? $_POST['fecha_ingreso'] : date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn">✅ Registrar Laptop</button>
            </form>
        </div>
        
        <div class="section">
            <h2>📋 Lista de Laptops Registradas</h2>
            <?php if(count($laptops) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Modelo</th>
                            <th>Estado</th>
                            <th>Fecha de Ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($laptops as $laptop): ?>
                            <tr>
                                <td><strong><?php echo $laptop['codigo']; ?></strong></td>
                                <td><?php echo $laptop['modelo']; ?></td>
                                <td>
                                    <?php 
                                    $estado_class = '';
                                    $estado_icon = '';
                                    if($laptop['estado'] == 'disponible') {
                                        $estado_class = 'estado-disponible';
                                        $estado_icon = '🟢';
                                    } else if($laptop['estado'] == 'prestado') {
                                        $estado_class = 'estado-prestado';
                                        $estado_icon = '🟡';
                                    } else if($laptop['estado'] == 'mantenimiento') {
                                        $estado_class = 'estado-mantenimiento';
                                        $estado_icon = '🔴';
                                    }
                                    ?>
                                    <span class="<?php echo $estado_class; ?>">
                                        <?php echo $estado_icon; ?>
                                        <?php 
                                        if($laptop['estado'] == 'disponible') echo 'Disponible';
                                        else if($laptop['estado'] == 'prestado') echo 'Prestado';
                                        else if($laptop['estado'] == 'mantenimiento') echo 'En Mantenimiento';
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo $laptop['fecha_ingreso']; ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="actualizar_estado" value="1">
                                        <input type="hidden" name="laptop_id" value="<?php echo $laptop['id']; ?>">
                                        <select name="estado" onchange="this.form.submit()" style="padding: 5px;">
                                            <option value="disponible" <?php echo $laptop['estado'] == 'disponible' ? 'selected' : ''; ?>>🟢 Disponible</option>
                                            <option value="prestado" <?php echo $laptop['estado'] == 'prestado' ? 'selected' : ''; ?>>🟡 Prestado</option>
                                            <option value="mantenimiento" <?php echo $laptop['estado'] == 'mantenimiento' ? 'selected' : ''; ?>>🔴 Mantenimiento</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: #666;">
                    📭 No hay laptops registradas en el sistema. Agrega la primera laptop usando el formulario superior.
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php unset($db); ?>