<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITCA FEPADE - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header ITCA -->
    <div class="itca-header">
        <div class="container">
            <h1>ITCA FEPADE</h1>
            <p class="lead">Sistema de Gestión de Biblioteca</p>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="card">
                    <div class="card-header">
                        <h2>Bienvenido al Sistema de Biblioteca</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <!-- Aquí puedes agregar el logo de ITCA -->
                            <div style="width: 200px; height: 200px; background: #dc3545; border-radius: 50%; margin: 0 auto 2rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                ITCA
                            </div>
                        </div>
                        <h3 class="text-danger mb-4">ITCA FEPADE</h3>
                        <p class="lead mb-4">
                            Sistema integral de gestión de biblioteca para el control de libros, 
                            estudiantes y préstamos.
                        </p>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="login.php" class="btn btn-primary btn-lg me-md-2">
                                Iniciar Sesión
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-5">
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h4 class="stats-number">Gestión de Libros</h4>
                                <p class="stats-label">Control completo del inventario</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h4 class="stats-number">Estudiantes</h4>
                                <p class="stats-label">Registro con carnet y DUI</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h4 class="stats-number">Reportes</h4>
                                <p class="stats-label">Estadísticas y gráficos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer mt-5">
        <div class="container">
            <p>&copy; 2024 ITCA FEPADE - Sistema de Biblioteca. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>