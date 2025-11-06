<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Realizar préstamo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['realizar_prestamo'])) {
    $libro_id = $_POST['libro_id'];
    $estudiante_id = $_POST['estudiante_id'];
    $dias_prestamo = $_POST['dias_prestamo'];
    
    try {
        $fecha_prestamo = date('Y-m-d');
        $fecha_devolucion = date('Y-m-d', strtotime("+$dias_prestamo days"));
        
        // Insertar préstamo
        $query = "INSERT INTO prestamos (libro_id, estudiante_id, usuario_id, fecha_prestamo, fecha_devolucion_estimada) 
                  VALUES (:libro_id, :estudiante_id, :usuario_id, :fecha_prestamo, :fecha_devolucion)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':libro_id', $libro_id);
        $stmt->bindParam(':estudiante_id', $estudiante_id);
        $stmt->bindParam(':usuario_id', $_SESSION['user_id']);
        $stmt->bindParam(':fecha_prestamo', $fecha_prestamo);
        $stmt->bindParam(':fecha_devolucion', $fecha_devolucion);
        $stmt->execute();
        
        // Actualizar disponibilidad del libro
        $query = "UPDATE libros SET ejemplares_disponibles = ejemplares_disponibles - 1 WHERE id = :libro_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':libro_id', $libro_id);
        $stmt->execute();
        
        $_SESSION['message'] = 'Préstamo realizado exitosamente';
        $_SESSION['message_type'] = 'success';
        header('Location: prestamos.php');
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Error al realizar préstamo: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}

// Devolver libro
if (isset($_GET['devolver'])) {
    $prestamo_id = $_GET['devolver'];
    
    try {
        // Obtener información del préstamo
        $query = "SELECT libro_id FROM prestamos WHERE id = :prestamo_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':prestamo_id', $prestamo_id);
        $stmt->execute();
        $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($prestamo) {
            // Actualizar préstamo
            $query = "UPDATE prestamos SET estado = 'devuelto', fecha_devolucion_real = CURDATE() WHERE id = :prestamo_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':prestamo_id', $prestamo_id);
            $stmt->execute();
            
            // Actualizar disponibilidad del libro
            $query = "UPDATE libros SET ejemplares_disponibles = ejemplares_disponibles + 1 WHERE id = :libro_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':libro_id', $prestamo['libro_id']);
            $stmt->execute();
            
            $_SESSION['message'] = 'Libro devuelto exitosamente';
            $_SESSION['message_type'] = 'success';
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Error al devolver libro: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: prestamos.php');
    exit();
}

// Obtener préstamos activos
$query = "SELECT p.*, l.titulo, l.isbn, e.nombre as estudiante_nombre, e.carnet,
          u.nombre as usuario_nombre, 
          DATEDIFF(p.fecha_devolucion_estimada, CURDATE()) as dias_restantes
          FROM prestamos p
          JOIN libros l ON p.libro_id = l.id
          JOIN estudiantes e ON p.estudiante_id = e.id
          JOIN usuarios u ON p.usuario_id = u.id
          WHERE p.estado = 'activo'
          ORDER BY p.fecha_prestamo DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$prestamos_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener libros disponibles
$query = "SELECT * FROM libros WHERE ejemplares_disponibles > 0 AND estado = 'disponible'";
$stmt = $db->prepare($query);
$stmt->execute();
$libros_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estudiantes activos
$query = "SELECT * FROM estudiantes WHERE activo = TRUE";
$stmt = $db->prepare($query);
$stmt->execute();
$estudiantes_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Gestión de Préstamos</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#realizarPrestamoModal">
                Realizar Préstamo
            </button>
        </div>
    </div>
</div>

<!-- Préstamos Activos -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Préstamos Activos</h5>
            </div>
            <div class="card-body">
                <?php if ($prestamos_activos): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Libro</th>
                                <th>Estudiante</th>
                                <th>Carnet</th>
                                <th>Fecha Préstamo</th>
                                <th>Fecha Devolución</th>
                                <th>Días Restantes</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestamos_activos as $prestamo): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $prestamo['titulo']; ?></strong><br>
                                    <small class="text-muted">ISBN: <?php echo $prestamo['isbn']; ?></small>
                                </td>
                                <td><?php echo $prestamo['estudiante_nombre']; ?></td>
                                <td><?php echo $prestamo['carnet']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_estimada'])); ?></td>
                                <td>
                                    <?php if ($prestamo['dias_restantes'] < 0): ?>
                                        <span class="badge bg-danger">Atrasado <?php echo -$prestamo['dias_restantes']; ?> días</span>
                                    <?php else: ?>
                                        <span class="badge <?php echo $prestamo['dias_restantes'] <= 2 ? 'bg-warning' : 'bg-success'; ?>">
                                            <?php echo $prestamo['dias_restantes']; ?> días
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="prestamos.php?devolver=<?php echo $prestamo['id']; ?>" 
                                       class="btn btn-success btn-sm"
                                       onclick="return confirm('¿Está seguro de marcar este libro como devuelto?')">
                                        Devolver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No hay préstamos activos en este momento.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Realizar Préstamo -->
<div class="modal fade" id="realizarPrestamoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Realizar Nuevo Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="prestamos.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Libro *</label>
                        <select class="form-select" name="libro_id" required>
                            <option value="">Seleccione un libro...</option>
                            <?php foreach ($libros_disponibles as $libro): ?>
                            <option value="<?php echo $libro['id']; ?>">
                                <?php echo $libro['titulo']; ?> - <?php echo $libro['autor']; ?> (Disponibles: <?php echo $libro['ejemplares_disponibles']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Estudiante *</label>
                        <select class="form-select" name="estudiante_id" required>
                            <option value="">Seleccione un estudiante...</option>
                            <?php foreach ($estudiantes_activos as $estudiante): ?>
                            <option value="<?php echo $estudiante['id']; ?>">
                                <?php echo $estudiante['nombre']; ?> - <?php echo $estudiante['carnet']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Días de Préstamo *</label>
                        <select class="form-select" name="dias_prestamo" required>
                            <option value="7">7 días</option>
                            <option value="14" selected>14 días</option>
                            <option value="21">21 días</option>
                            <option value="30">30 días</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="realizar_prestamo" class="btn btn-primary">Registrar Préstamo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>