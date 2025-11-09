<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas
$stats = [];
$query = "SELECT COUNT(*) as total FROM laptops";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_laptops'] = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as total FROM laptops WHERE estado = 'disponible'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['laptops_disponibles'] = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as total FROM laptops WHERE estado = 'prestado'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['laptops_prestadas'] = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as total FROM laptops WHERE estado = 'mantenimiento'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['laptops_mantenimiento'] = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as total FROM prestamos WHERE DATE(fecha_registro) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['prestamos_hoy'] = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ITCA FEPADE</title>
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
        .welcome {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #d32f2f;
        }
        .recent-activity {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .recent-activity h2 {
            margin-bottom: 15px;
            color: #333;
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
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="gestion_prestamos.php">Gestión de Préstamos</a></li>
            <li><a href="gestion_laptops.php">Gestión de Laptops</a></li>
            <li><a href="reportes.php">Reportes</a></li>
        </ul>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h2>Bienvenido al Sistema de Gestión de Préstamos de Computadoras</h2>
            <p>Desde aquí puedes gestionar los préstamos de laptops, ver estadísticas y generar reportes.</p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Total de Laptops</h3>
                <div class="stat-number"><?php echo $stats['total_laptops']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Laptops Disponibles</h3>
                <div class="stat-number"><?php echo $stats['laptops_disponibles']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Laptops Prestadas</h3>
                <div class="stat-number"><?php echo $stats['laptops_prestadas']; ?></div>
            </div>
            <div class="stat-card">
                <h3>En Mantenimiento</h3>
                <div class="stat-number"><?php echo $stats['laptops_mantenimiento']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Préstamos Hoy</h3>
                <div class="stat-number"><?php echo $stats['prestamos_hoy']; ?></div>
            </div>
        </div>
        
        <div class="recent-activity">
            <h2>Préstamos Recientes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Carnet</th>
                        <th>Laptop</th>
                        <th>Fecha</th>
                        <th>Hora Entrega</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT p.estudiante_nombre, p.carnet, l.codigo, p.fecha_prestamo, p.hora_entrega 
                              FROM prestamos p 
                              JOIN laptops l ON p.laptop_id = l.id 
                              ORDER BY p.fecha_registro DESC 
                              LIMIT 5";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    
                    if($stmt->rowCount() > 0){
                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                            echo "<tr>";
                            echo "<td>" . $row['estudiante_nombre'] . "</td>";
                            echo "<td>" . $row['carnet'] . "</td>";
                            echo "<td>" . $row['codigo'] . "</td>";
                            echo "<td>" . $row['fecha_prestamo'] . "</td>";
                            echo "<td>" . $row['hora_entrega'] . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No hay préstamos recientes</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php unset($db); ?>