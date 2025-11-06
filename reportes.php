<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas
$stats = [];

// Total libros
$query = "SELECT COUNT(*) as total FROM libros";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_libros'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total estudiantes
$query = "SELECT COUNT(*) as total FROM estudiantes WHERE activo = TRUE";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_estudiantes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Préstamos activos
$query = "SELECT COUNT(*) as total FROM prestamos WHERE estado = 'activo'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['prestamos_activos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Préstamos atrasados
$query = "SELECT COUNT(*) as total FROM prestamos WHERE estado = 'activo' AND fecha_devolucion_estimada < CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['prestamos_atrasados'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Libros por categoría
$query = "SELECT categoria, COUNT(*) as total FROM libros GROUP BY categoria";
$stmt = $db->prepare($query);
$stmt->execute();
$libros_por_categoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préstamos por mes
$query = "SELECT MONTH(fecha_prestamo) as mes, COUNT(*) as total 
          FROM prestamos 
          WHERE YEAR(fecha_prestamo) = YEAR(CURDATE())
          GROUP BY MONTH(fecha_prestamo)";
$stmt = $db->prepare($query);
$stmt->execute();
$prestamos_por_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préstamos por carrera
$query = "SELECT e.carrera, COUNT(p.id) as total 
          FROM prestamos p
          JOIN estudiantes e ON p.estudiante_id = e.id
          GROUP BY e.carrera";
$stmt = $db->prepare($query);
$stmt->execute();
$prestamos_por_carrera = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2>Reportes y Estadísticas</h2>
        <p class="text-muted">Estadísticas generales del sistema de biblioteca</p>
    </div>
</div>

<!-- Estadísticas -->
<div class="row">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <h3 class="stats-number"><?php echo $stats['total_libros']; ?></h3>
                <p class="stats-label">Total Libros</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <h3 class="stats-number"><?php echo $stats['total_estudiantes']; ?></h3>
                <p class="stats-label">Estudiantes</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <h3 class="stats-number"><?php echo $stats['prestamos_activos']; ?></h3>
                <p class="stats-label">Préstamos Activos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <h3 class="stats-number"><?php echo $stats['prestamos_atrasados']; ?></h3>
                <p class="stats-label">Préstamos Atrasados</p>
            </div>
        </div>
    </div>
</div>

<!-- Tablas de Reportes -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Libros por Categoría</h5>
            </div>
            <div class="card-body">
                <?php if ($libros_por_categoria): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($libros_por_categoria as $categoria): ?>
                            <tr>
                                <td><?php echo $categoria['categoria'] ?: 'Sin categoría'; ?></td>
                                <td>
                                    <span class="badge bg-danger"><?php echo $categoria['total']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No hay datos de categorías.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Préstamos por Carrera</h5>
            </div>
            <div class="card-body">
                <?php if ($prestamos_por_carrera): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Carrera</th>
                                <th>Préstamos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestamos_por_carrera as $carrera): ?>
                            <tr>
                                <td><?php echo $carrera['carrera'] ?: 'No especificada'; ?></td>
                                <td>
                                    <span class="badge bg-danger"><?php echo $carrera['total']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No hay datos de préstamos por carrera.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Préstamos Mensuales -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Préstamos Mensuales - <?php echo date('Y'); ?></h5>
            </div>
            <div class="card-body">
                <?php if ($prestamos_por_mes): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th>Total Préstamos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $meses = [
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ];
                            
                            foreach ($prestamos_por_mes as $prestamo): 
                            ?>
                            <tr>
                                <td><?php echo $meses[$prestamo['mes']] ?? 'Desconocido'; ?></td>
                                <td>
                                    <span class="badge bg-danger"><?php echo $prestamo['total']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No hay datos de préstamos mensuales para este año.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>