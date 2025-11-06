<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Agregar libro
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_libro'])) {
    $isbn = $_POST['isbn'];
    $titulo = $_POST['titulo'];
    $autor = $_POST['autor'];
    $editorial = $_POST['editorial'];
    $anio_publicacion = $_POST['anio_publicacion'];
    $categoria = $_POST['categoria'];
    $ejemplares = $_POST['ejemplares'];
    
    try {
        $query = "INSERT INTO libros (isbn, titulo, autor, editorial, anio_publicacion, categoria, ejemplares, ejemplares_disponibles) 
                  VALUES (:isbn, :titulo, :autor, :editorial, :anio_publicacion, :categoria, :ejemplares, :ejemplares)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':isbn', $isbn);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':autor', $autor);
        $stmt->bindParam(':editorial', $editorial);
        $stmt->bindParam(':anio_publicacion', $anio_publicacion);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->bindParam(':ejemplares', $ejemplares);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Libro agregado exitosamente';
            $_SESSION['message_type'] = 'success';
            header('Location: libros.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Error al agregar libro: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}

// Obtener lista de libros
$query = "SELECT * FROM libros ORDER BY titulo";
$stmt = $db->prepare($query);
$stmt->execute();
$libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Gestión de Libros</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agregarLibroModal">
                Agregar Libro
            </button>
        </div>
    </div>
</div>

<!-- Lista de Libros -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Inventario de Libros</h5>
            </div>
            <div class="card-body">
                <?php if ($libros): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ISBN</th>
                                <th>Título</th>
                                <th>Autor</th>
                                <th>Editorial</th>
                                <th>Categoría</th>
                                <th>Ejemplares</th>
                                <th>Disponibles</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($libros as $libro): ?>
                            <tr>
                                <td><?php echo $libro['isbn'] ?: 'N/A'; ?></td>
                                <td><strong><?php echo $libro['titulo']; ?></strong></td>
                                <td><?php echo $libro['autor']; ?></td>
                                <td><?php echo $libro['editorial'] ?: 'N/A'; ?></td>
                                <td><?php echo $libro['categoria'] ?: 'General'; ?></td>
                                <td><?php echo $libro['ejemplares']; ?></td>
                                <td>
                                    <span class="badge <?php echo $libro['ejemplares_disponibles'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $libro['ejemplares_disponibles']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                        switch($libro['estado']) {
                                            case 'disponible': echo 'bg-success'; break;
                                            case 'prestado': echo 'bg-warning'; break;
                                            default: echo 'bg-secondary';
                                        }
                                        ?>">
                                        <?php echo ucfirst($libro['estado']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No hay libros registrados en el sistema.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Libro -->
<div class="modal fade" id="agregarLibroModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nuevo Libro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="libros.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" class="form-control" name="isbn">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" class="form-control" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Autor *</label>
                        <input type="text" class="form-control" name="autor" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Editorial</label>
                        <input type="text" class="form-control" name="editorial">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Año Publicación</label>
                                <input type="number" class="form-control" name="anio_publicacion" min="1900" max="2024">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Categoría</label>
                                <input type="text" class="form-control" name="categoria">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ejemplares *</label>
                        <input type="number" class="form-control" name="ejemplares" value="1" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="agregar_libro" class="btn btn-primary">Guardar Libro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>