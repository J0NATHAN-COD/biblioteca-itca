<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Agregar estudiante
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_estudiante'])) {
    $carnet = $_POST['carnet'];
    $dui = $_POST['dui'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $carrera = $_POST['carrera'];
    
    try {
        $query = "INSERT INTO estudiantes (carnet, dui, nombre, email, telefono, carrera) 
                  VALUES (:carnet, :dui, :nombre, :email, :telefono, :carrera)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':carnet', $carnet);
        $stmt->bindParam(':dui', $dui);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':carrera', $carrera);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Estudiante agregado exitosamente';
            $_SESSION['message_type'] = 'success';
            header('Location: estudiantes.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Error al agregar estudiante: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}

// Obtener lista de estudiantes
$query = "SELECT * FROM estudiantes WHERE activo = TRUE ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Gestión de Estudiantes</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agregarEstudianteModal">
                Agregar Estudiante
            </button>
        </div>
    </div>
</div>

<!-- Lista de Estudiantes -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Estudiantes Registrados</h5>
            </div>
            <div class="card-body">
                <?php if ($estudiantes): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Carnet</th>
                                <th>DUI</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Carrera</th>
                                <th>Fecha Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estudiantes as $estudiante): ?>
                            <tr>
                                <td><strong><?php echo $estudiante['carnet']; ?></strong></td>
                                <td><?php echo $estudiante['dui'] ?: 'N/A'; ?></td>
                                <td><?php echo $estudiante['nombre']; ?></td>
                                <td><?php echo $estudiante['email'] ?: 'N/A'; ?></td>
                                <td><?php echo $estudiante['telefono'] ?: 'N/A'; ?></td>
                                <td><?php echo $estudiante['carrera'] ?: 'N/A'; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($estudiante['fecha_registro'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No hay estudiantes registrados en el sistema.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Estudiante -->
<div class="modal fade" id="agregarEstudianteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nuevo Estudiante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="estudiantes.php">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Carnet *</label>
                                <input type="text" class="form-control" name="carnet" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">DUI</label>
                                <input type="text" class="form-control" name="dui" placeholder="00000000-0">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Carrera</label>
                        <input type="text" class="form-control" name="carrera">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="agregar_estudiante" class="btn btn-primary">Guardar Estudiante</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>