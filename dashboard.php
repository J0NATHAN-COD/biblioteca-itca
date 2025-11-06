<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Estadísticas
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

// Libros más prestados
$query = "SELECT l.titulo, COUNT(p.id) as veces_prestado 
          FROM prestamos p 
          JOIN libros l ON p.libro_id = l.id 
          GROUP BY l.id, l.titulo 
          ORDER BY veces_prestado DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$libros_populares = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2>Dashboard Principal</h2>
        <p class="text-muted">Bienvenido, <?php echo getUserName(); ?></p>
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
                <p class="stats-label">Estudiantes Activos</p>
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

<!-- Libros Más Prestados -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Libros Más Prestados</h5>
            </div>
            <div class="card-body">
                <?php if ($libros_populares): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Libro</th>
                                <th>Veces Prestado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($libros_populares as $libro): ?>
                            <tr>
                                <td><?php echo $libro['titulo']; ?></td>
                                <td>
                                    <span class="badge bg-danger"><?php echo $libro['veces_prestado']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No hay datos de préstamos disponibles.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Acciones Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="prestamos.php" class="btn btn-primary">
                        Realizar Préstamo
                    </a>
                    <a href="libros.php" class="btn btn-outline-primary">
                        Agregar Libro
                    </a>
                    <a href="estudiantes.php" class="btn btn-outline-primary">
                        Registrar Estudiante
                    </a>
                    <a href="reportes.php" class="btn btn-outline-primary">
                        Ver Reportes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>