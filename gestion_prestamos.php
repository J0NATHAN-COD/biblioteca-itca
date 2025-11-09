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

// Obtener carreras para el select
$carreras = [];
$query = "SELECT id, nombre FROM carreras ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener laptops disponibles
$laptops_disponibles = [];
$query = "SELECT id, codigo, modelo FROM laptops WHERE estado = 'disponible' ORDER BY codigo";
$stmt = $db->prepare($query);
$stmt->execute();
$laptops_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario de nuevo préstamo
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nuevo_prestamo"])){
    $estudiante_nombre = trim($_POST["estudiante_nombre"]);
    $carrera_id = trim($_POST["carrera_id"]);
    $carnet = trim($_POST["carnet"]);
    $telefono = trim($_POST["telefono"]);
    $tipo_solicitante = trim($_POST["tipo_solicitante"]);
    $genero = trim($_POST["genero"]);
    $laptop_id = trim($_POST["laptop_id"]);
    $area_uso = trim($_POST["area_uso"]);
    $fecha_prestamo = trim($_POST["fecha_prestamo"]);
    $hora_entrega = trim($_POST["hora_entrega"]);
    $hora_devolucion = trim($_POST["hora_devolucion"]);
    
    // VALIDACIONES MEJORADAS
    $campos_obligatorios = [
        'Nombre del estudiante' => $estudiante_nombre,
        'Número de carnet' => $carnet,
        'Carrera técnica' => $carrera_id,
        'Laptop a prestar' => $laptop_id,
        'Fecha de préstamo' => $fecha_prestamo,
        'Hora de entrega' => $hora_entrega,
        'Hora de devolución' => $hora_devolucion
    ];
    
    $campos_vacios = [];
    foreach($campos_obligatorios as $campo => $valor) {
        if(empty($valor)) {
            $campos_vacios[] = $campo;
        }
    }
    
    if(!empty($campos_vacios)) {
        $error = "❌ Los siguientes campos son obligatorios: <strong>" . implode(', ', $campos_vacios) . "</strong>";
    } else if(!is_numeric($carrera_id) || !is_numeric($laptop_id)) {
        $error = "❌ Error en la selección de carrera o laptop. Por favor, verifique los datos.";
    } else if($hora_entrega >= $hora_devolucion) {
        $error = "❌ La hora de devolución debe ser posterior a la hora de entrega.";
    } else {
        // Convertir a enteros
        $carrera_id = (int)$carrera_id;
        $laptop_id = (int)$laptop_id;
        
        // Verificar que la laptop sigue disponible (protección contra doble préstamo)
        $query_verificar = "SELECT estado, codigo FROM laptops WHERE id = :laptop_id";
        $stmt_verificar = $db->prepare($query_verificar);
        $stmt_verificar->bindParam(":laptop_id", $laptop_id, PDO::PARAM_INT);
        $stmt_verificar->execute();
        $laptop_info = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
        
        if(!$laptop_info) {
            $error = "❌ La laptop seleccionada no existe en la base de datos.";
        } else if($laptop_info['estado'] != 'disponible') {
            $error = "❌ La laptop <strong>'{$laptop_info['codigo']}'</strong> ya no está disponible. Estado actual: <strong>{$laptop_info['estado']}</strong>";
        } else {
            // Insertar nuevo préstamo
            $query = "INSERT INTO prestamos (estudiante_nombre, carrera_id, carnet, telefono, tipo_solicitante, genero, laptop_id, area_uso, fecha_prestamo, hora_entrega, hora_devolucion) 
                      VALUES (:estudiante_nombre, :carrera_id, :carnet, :telefono, :tipo_solicitante, :genero, :laptop_id, :area_uso, :fecha_prestamo, :hora_entrega, :hora_devolucion)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":estudiante_nombre", $estudiante_nombre);
            $stmt->bindParam(":carrera_id", $carrera_id, PDO::PARAM_INT);
            $stmt->bindParam(":carnet", $carnet);
            $stmt->bindParam(":telefono", $telefono);
            $stmt->bindParam(":tipo_solicitante", $tipo_solicitante);
            $stmt->bindParam(":genero", $genero);
            $stmt->bindParam(":laptop_id", $laptop_id, PDO::PARAM_INT);
            $stmt->bindParam(":area_uso", $area_uso);
            $stmt->bindParam(":fecha_prestamo", $fecha_prestamo);
            $stmt->bindParam(":hora_entrega", $hora_entrega);
            $stmt->bindParam(":hora_devolucion", $hora_devolucion);
            
            if($stmt->execute()){
                // Actualizar estado de la laptop
                $query = "UPDATE laptops SET estado = 'prestado' WHERE id = :laptop_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":laptop_id", $laptop_id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Obtener información para el mensaje
                $query_info = "SELECT l.codigo, c.nombre as carrera FROM laptops l, carreras c WHERE l.id = :laptop_id AND c.id = :carrera_id";
                $stmt_info = $db->prepare($query_info);
                $stmt_info->bindParam(":laptop_id", $laptop_id, PDO::PARAM_INT);
                $stmt_info->bindParam(":carrera_id", $carrera_id, PDO::PARAM_INT);
                $stmt_info->execute();
                $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
                
                $message = "✅ Préstamo registrado exitosamente.<br>
                           <strong>Estudiante:</strong> $estudiante_nombre<br>
                           <strong>Carrera:</strong> {$info['carrera']}<br>
                           <strong>Laptop:</strong> {$info['codigo']}<br>
                           <strong>Fecha:</strong> $fecha_prestamo<br>
                           <strong>Horario:</strong> $hora_entrega - $hora_devolucion";
                
                // Limpiar el formulario después de éxito
                $_POST = array();
            } else {
                $error = "❌ Error al registrar el préstamo. Por favor, intente nuevamente.";
            }
        }
    }
}

// Procesar devolución
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["devolver_laptop"])){
    $prestamo_id = trim($_POST["prestamo_id"]);
    $laptop_id = trim($_POST["laptop_id"]);
    
    if(empty($prestamo_id) || empty($laptop_id)) {
        $error = "❌ Error: Datos incompletos para procesar la devolución.";
    } else {
        // Obtener información del préstamo para el mensaje
        $query_info = "SELECT p.estudiante_nombre, l.codigo 
                       FROM prestamos p 
                       JOIN laptops l ON p.laptop_id = l.id 
                       WHERE p.id = :prestamo_id";
        $stmt_info = $db->prepare($query_info);
        $stmt_info->bindParam(":prestamo_id", $prestamo_id);
        $stmt_info->execute();
        $prestamo_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
        
        if(!$prestamo_info) {
            $error = "❌ El préstamo seleccionado no existe.";
        } else {
            // Eliminar préstamo
            $query = "DELETE FROM prestamos WHERE id = :prestamo_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":prestamo_id", $prestamo_id);
            
            if($stmt->execute()){
                // Actualizar estado de la laptop
                $query = "UPDATE laptops SET estado = 'disponible' WHERE id = :laptop_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":laptop_id", $laptop_id);
                $stmt->execute();
                
                $message = "✅ Devolución registrada exitosamente.<br>
                           <strong>Laptop:</strong> {$prestamo_info['codigo']}<br>
                           <strong>Estudiante:</strong> {$prestamo_info['estudiante_nombre']}<br>
                           <strong>Estado:</strong> La laptop ahora está disponible para nuevos préstamos.";
            } else {
                $error = "❌ Error al registrar la devolución. Por favor, intente nuevamente.";
            }
        }
    }
}

// Obtener préstamos activos
$prestamos_activos = [];
$query = "SELECT p.id, p.estudiante_nombre, p.carnet, p.telefono, c.nombre as carrera, 
                 p.tipo_solicitante, p.genero, l.codigo as laptop, l.id as laptop_id,
                 p.area_uso, p.fecha_prestamo, p.hora_entrega, p.hora_devolucion
          FROM prestamos p 
          JOIN carreras c ON p.carrera_id = c.id 
          JOIN laptops l ON p.laptop_id = l.id 
          ORDER BY p.fecha_prestamo DESC, p.hora_entrega DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$prestamos_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas para el panel informativo
$stats_prestamos = [];
$query_stats = "SELECT 
    COUNT(*) as total_prestamos,
    COUNT(DISTINCT estudiante_nombre) as estudiantes_unicos,
    COUNT(DISTINCT laptop_id) as laptops_prestadas
    FROM prestamos";
$stmt_stats = $db->prepare($query_stats);
$stmt_stats->execute();
$stats_prestamos = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Préstamos - ITCA FEPADE</title>
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
        .action-btn {
            background-color: #d32f2f;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .action-btn:hover {
            background-color: #b71c1c;
        }
        .required {
            color: #d32f2f;
        }
        .no-laptops {
            text-align: center;
            padding: 30px;
            color: #666;
            background-color: #f9f9f9;
            border-radius: 5px;
            margin: 20px 0;
        }
        .no-laptops h3 {
            margin-bottom: 10px;
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
            <li><a href="gestion_prestamos.php" class="active">Gestión de Préstamos</a></li>
            <li><a href="gestion_laptops.php">Gestión de Laptops</a></li>
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
            <h3>📊 Estadísticas de Préstamos</h3>
            <ul>
                <li>✅ Préstamos activos: <strong><?php echo count($prestamos_activos); ?></strong></li>
                <li>👥 Estudiantes únicos: <strong><?php echo $stats_prestamos['estudiantes_unicos']; ?></strong></li>
                <li>💻 Laptops prestadas: <strong><?php echo $stats_prestamos['laptops_prestadas']; ?></strong></li>
                <li>🟢 Laptops disponibles: <strong><?php echo count($laptops_disponibles); ?></strong></li>
            </ul>
        </div>
        
        <div class="section">
            <h2>➕ Nuevo Préstamo</h2>
            
            <?php if(empty($laptops_disponibles)): ?>
                <div class="no-laptops">
                    <h3>📭 No hay laptops disponibles</h3>
                    <p>Actualmente no hay laptops disponibles para préstamo.</p>
                    <p>Puede agregar nuevas laptops o cambiar el estado de las existentes en la sección <strong>Gestión de Laptops</strong>.</p>
                    <a href="gestion_laptops.php" class="btn" style="margin-top: 10px;">Ir a Gestión de Laptops</a>
                </div>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="nuevo_prestamo" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="estudiante_nombre">Nombre del Estudiante/Docente <span class="required">*</span></label>
                            <input type="text" id="estudiante_nombre" name="estudiante_nombre" 
                                   value="<?php echo isset($_POST['estudiante_nombre']) ? htmlspecialchars($_POST['estudiante_nombre']) : ''; ?>" 
                                   required placeholder="Ej: Juan Pérez García">
                        </div>
                        <div class="form-group">
                            <label for="carnet">Número de Carnet/DUI <span class="required">*</span></label>
                            <input type="text" id="carnet" name="carnet" 
                                   value="<?php echo isset($_POST['carnet']) ? htmlspecialchars($_POST['carnet']) : ''; ?>" 
                                   required placeholder="Ej: 20230001 o 12345678-9">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="carrera_id">Carrera Técnica <span class="required">*</span></label>
                            <select id="carrera_id" name="carrera_id" required>
                                <option value="">Seleccione una carrera</option>
                                <?php foreach($carreras as $carrera): ?>
                                    <option value="<?php echo $carrera['id']; ?>" 
                                        <?php echo (isset($_POST['carrera_id']) && $_POST['carrera_id'] == $carrera['id']) ? 'selected' : ''; ?>>
                                        <?php echo $carrera['nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="telefono">Número de Teléfono</label>
                            <input type="text" id="telefono" name="telefono" 
                                   value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>" 
                                   placeholder="Ej: 1234-5678">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tipo_solicitante">Tipo de Solicitante</label>
                            <select id="tipo_solicitante" name="tipo_solicitante">
                                <option value="alumno" <?php echo (isset($_POST['tipo_solicitante']) && $_POST['tipo_solicitante'] == 'alumno') ? 'selected' : ''; ?>>🎓 Alumno</option>
                                <option value="docente" <?php echo (isset($_POST['tipo_solicitante']) && $_POST['tipo_solicitante'] == 'docente') ? 'selected' : ''; ?>>👨‍🏫 Docente</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="genero">Género</label>
                            <select id="genero" name="genero">
                                <option value="hombre" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'hombre') ? 'selected' : ''; ?>>👨 Hombre</option>
                                <option value="mujer" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'mujer') ? 'selected' : ''; ?>>👩 Mujer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="laptop_id">Laptop a Prestar <span class="required">*</span></label>
                            <select id="laptop_id" name="laptop_id" required>
                                <option value="">Seleccione una laptop disponible</option>
                                <?php foreach($laptops_disponibles as $laptop): ?>
                                    <option value="<?php echo $laptop['id']; ?>" 
                                        <?php echo (isset($_POST['laptop_id']) && $_POST['laptop_id'] == $laptop['id']) ? 'selected' : ''; ?>>
                                        <?php echo $laptop['codigo'] . ' - ' . $laptop['modelo']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color: #666;">Solo se muestran laptops disponibles</small>
                        </div>
                        <div class="form-group">
                            <label for="area_uso">Área de Uso</label>
                            <select id="area_uso" name="area_uso">
                                <option value="salon" <?php echo (isset($_POST['area_uso']) && $_POST['area_uso'] == 'salon') ? 'selected' : ''; ?>>🏫 Salón</option>
                                <option value="taller" <?php echo (isset($_POST['area_uso']) && $_POST['area_uso'] == 'taller') ? 'selected' : ''; ?>>🔧 Taller</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_prestamo">Fecha de Préstamo <span class="required">*</span></label>
                            <input type="date" id="fecha_prestamo" name="fecha_prestamo" 
                                   value="<?php echo isset($_POST['fecha_prestamo']) ? $_POST['fecha_prestamo'] : date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="hora_entrega">Hora de Entrega <span class="required">*</span></label>
                            <input type="time" id="hora_entrega" name="hora_entrega" 
                                   value="<?php echo isset($_POST['hora_entrega']) ? $_POST['hora_entrega'] : date('H:i'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="hora_devolucion">Hora de Devolución <span class="required">*</span></label>
                            <input type="time" id="hora_devolucion" name="hora_devolucion" 
                                   value="<?php echo isset($_POST['hora_devolucion']) ? $_POST['hora_devolucion'] : ''; ?>" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">✅ Registrar Préstamo</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>📋 Préstamos Activos (<?php echo count($prestamos_activos); ?>)</h2>
            <?php if(count($prestamos_activos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Carnet</th>
                            <th>Carrera</th>
                            <th>Laptop</th>
                            <th>Fecha</th>
                            <th>Hora Entrega</th>
                            <th>Hora Devolución</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($prestamos_activos as $prestamo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prestamo['estudiante_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($prestamo['carnet']); ?></td>
                                <td><?php echo htmlspecialchars($prestamo['carrera']); ?></td>
                                <td><strong><?php echo htmlspecialchars($prestamo['laptop']); ?></strong></td>
                                <td><?php echo $prestamo['fecha_prestamo']; ?></td>
                                <td><?php echo substr($prestamo['hora_entrega'], 0, 5); ?></td>
                                <td><?php echo substr($prestamo['hora_devolucion'], 0, 5); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="devolver_laptop" value="1">
                                        <input type="hidden" name="prestamo_id" value="<?php echo $prestamo['id']; ?>">
                                        <input type="hidden" name="laptop_id" value="<?php echo $prestamo['laptop_id']; ?>">
                                        <button type="submit" class="action-btn" onclick="return confirm('¿Está seguro de que desea registrar la devolución de esta laptop?')">
                                            🔄 Devolver
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: #666;">
                    📭 No hay préstamos activos en este momento.
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php unset($db); ?>