<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas para gráficos
$stats_carreras = [];
$query = "SELECT c.nombre as carrera, COUNT(p.id) as total 
          FROM prestamos p 
          JOIN carreras c ON p.carrera_id = c.id 
          GROUP BY c.id, c.nombre 
          ORDER BY total DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$stats_carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats_estado_laptops = [];
$query = "SELECT estado, COUNT(*) as total FROM laptops GROUP BY estado";
$stmt = $db->prepare($query);
$stmt->execute();
$stats_estado_laptops = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats_tipo_solicitante = [];
$query = "SELECT tipo_solicitante, COUNT(*) as total FROM prestamos GROUP BY tipo_solicitante";
$stmt = $db->prepare($query);
$stmt->execute();
$stats_tipo_solicitante = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats_genero = [];
$query = "SELECT genero, COUNT(*) as total FROM prestamos GROUP BY genero";
$stmt = $db->prepare($query);
$stmt->execute();
$stats_genero = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener préstamos por fecha (últimos 7 días)
$prestamos_por_fecha = [];
$query = "SELECT fecha_prestamo, COUNT(*) as total 
          FROM prestamos 
          WHERE fecha_prestamo >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          GROUP BY fecha_prestamo 
          ORDER BY fecha_prestamo";
$stmt = $db->prepare($query);
$stmt->execute();
$prestamos_por_fecha = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - ITCA FEPADE</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .chart-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .chart-card h3 {
            margin-bottom: 15px;
            color: #333;
            text-align: center;
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
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="gestion_prestamos.php">Gestión de Préstamos</a></li>
            <li><a href="gestion_laptops.php">Gestión de Laptops</a></li>
            <li><a href="reportes.php" class="active">Reportes</a></li>
        </ul>
    </div>
    
    <div class="container">
        <div class="section">
            <h2>Estadísticas de Préstamos</h2>
            <div class="charts-container">
                <div class="chart-card">
                    <h3>Préstamos por Carrera</h3>
                    <canvas id="chartCarreras"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Estado de Laptops</h3>
                    <canvas id="chartEstadoLaptops"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Tipo de Solicitante</h3>
                    <canvas id="chartTipoSolicitante"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Distribución por Género</h3>
                    <canvas id="chartGenero"></canvas>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Préstamos Recientes (Últimos 7 Días)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Total de Préstamos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($prestamos_por_fecha) > 0): ?>
                        <?php foreach($prestamos_por_fecha as $prestamo): ?>
                            <tr>
                                <td><?php echo $prestamo['fecha_prestamo']; ?></td>
                                <td><?php echo $prestamo['total']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay préstamos en los últimos 7 días</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>Top Carreras con Más Préstamos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Carrera</th>
                        <th>Total de Préstamos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($stats_carreras) > 0): ?>
                        <?php foreach($stats_carreras as $carrera): ?>
                            <tr>
                                <td><?php echo $carrera['carrera']; ?></td>
                                <td><?php echo $carrera['total']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay datos de préstamos por carrera</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Gráfico de préstamos por carrera
        const chartCarreras = new Chart(document.getElementById('chartCarreras'), {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['carrera'] . "'"; }, $stats_carreras)); ?>],
                datasets: [{
                    label: 'Préstamos por Carrera',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['total']; }, $stats_carreras)); ?>],
                    backgroundColor: '#d32f2f',
                    borderColor: '#b71c1c',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Gráfico de estado de laptops
        const chartEstadoLaptops = new Chart(document.getElementById('chartEstadoLaptops'), {
            type: 'pie',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { 
                    if($item['estado'] == 'disponible') return "'Disponible'";
                    else if($item['estado'] == 'prestado') return "'Prestado'";
                    else if($item['estado'] == 'mantenimiento') return "'Mantenimiento'";
                }, $stats_estado_laptops)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(function($item) { return $item['total']; }, $stats_estado_laptops)); ?>],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
        
        // Gráfico de tipo de solicitante
        const chartTipoSolicitante = new Chart(document.getElementById('chartTipoSolicitante'), {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { 
                    if($item['tipo_solicitante'] == 'alumno') return "'Alumno'";
                    else if($item['tipo_solicitante'] == 'docente') return "'Docente'";
                }, $stats_tipo_solicitante)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(function($item) { return $item['total']; }, $stats_tipo_solicitante)); ?>],
                    backgroundColor: [
                        '#d32f2f',
                        '#1976d2'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
        
        // Gráfico de distribución por género
        const chartGenero = new Chart(document.getElementById('chartGenero'), {
            type: 'pie',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { 
                    if($item['genero'] == 'hombre') return "'Hombre'";
                    else if($item['genero'] == 'mujer') return "'Mujer'";
                }, $stats_genero)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(function($item) { return $item['total']; }, $stats_genero)); ?>],
                    backgroundColor: [
                        '#1976d2',
                        '#c2185b'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>
<?php unset($db); ?>