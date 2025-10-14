<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// ----------------------------------------------------------------------
// LÓGICA DE DATOS Y CONSULTAS
// ----------------------------------------------------------------------
$current_date = date('Y-m-d');
$active_tab = $_GET['tab'] ?? 'dashboard';

// 1. Contar el total de equipos
$sql_total = "SELECT COUNT(*) as total FROM computers";
$total_equipos = $conn->query($sql_total)->fetch_assoc()['total'] ?? 0;

// 2. Contar equipos disponibles
$sql_available_count = "SELECT COUNT(*) as available_count FROM computers WHERE status = 'available'";
$equipos_disponibles = $conn->query($sql_available_count)->fetch_assoc()['available_count'] ?? 0;

// 3. Contar equipos en préstamo
$sql_loaned_count = "SELECT COUNT(*) as loaned_count FROM computers WHERE status = 'loaned'";
$equipos_en_prestamo = $conn->query($sql_loaned_count)->fetch_assoc()['loaned_count'] ?? 0;

// --- DATOS PARA FORMULARIO DE PRÉSTAMO ---
$sql_available = "SELECT id, internal_id, brand_model FROM computers WHERE status = 'available'";
$result_available = $conn->query($sql_available);

// --- TABLA DE PRÉSTAMOS ACTIVOS ---
$sql_active_loans = "SELECT
    l.*,
    c.internal_id,
    c.brand_model
FROM loans l
JOIN computers c ON l.computer_id = c.id
WHERE l.returned_at IS NULL
ORDER BY l.return_date ASC";
$result_active_loans = $conn->query($sql_active_loans);

// --- DEVOLUCIONES ATRASADAS HISTÓRICAS ---
$sql_delayed_returns = "
    SELECT
        l.student_name,
        l.student_phone,
        l.return_date AS expected_date,
        l.returned_at AS actual_date,
        l.course,
        l.parallel,
        c.internal_id,
        c.brand_model,
        DATEDIFF(DATE(l.returned_at), l.return_date) AS days_delayed
    FROM loans l
    JOIN computers c ON l.computer_id = c.id
    WHERE
        l.returned_at IS NOT NULL
        AND DATE(l.returned_at) > l.return_date
    ORDER BY l.returned_at DESC
    LIMIT 50
";
$result_delayed_returns = $conn->query($sql_delayed_returns);

// Obtener todas las computadoras (para Inventario)
$sql = "SELECT * FROM computers ORDER BY id DESC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Préstamos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Variables CSS */
        :root {
            --primary-slate: #7B9CAD;
            --secondary-sage: #96B6A3;
            --accent-mint: #A8C9B8;
            --dark-navy: #4A5F6D;
            --light-cream: #F8F9FA;
            --soft-blue: #C1D5E0;
            --warm-beige: #E8E4DF;
            --muted-teal: #89A5AE;
            --steel-gray: #6B7F8C;
            
            --success-green: #8FBC8F;
            --warning-amber: #D4A574;
            --danger-coral: #D17B7B;
            --info-blue: #91B5C9;
            
            --shadow-sm: 0 2px 8px rgba(123, 156, 173, 0.1);
            --shadow-md: 0 4px 16px rgba(123, 156, 173, 0.15);
            --shadow-lg: 0 8px 24px rgba(123, 156, 173, 0.2);
            --shadow-hover: 0 6px 20px rgba(123, 156, 173, 0.25);
            
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--light-cream) 0%, var(--soft-blue) 50%, var(--warm-beige) 100%);
            color: var(--dark-navy);
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* BARRA LATERAL (SIDEBAR) */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #FFFFFF 0%, var(--light-cream) 100%);
            padding: 25px 20px;
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
            border-right: 2px solid var(--soft-blue);
            position: relative;
            transition: var(--transition-normal);
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-slate), var(--secondary-sage), var(--accent-mint));
        }

        .sidebar-header {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary-slate);
            margin-bottom: 30px;
            padding-bottom: 15px;
            padding-left: 15px;
            position: relative;
            letter-spacing: 0.5px;
        }

        .sidebar-header::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 15px;
            width: 5px;
            background: linear-gradient(180deg, var(--primary-slate), var(--secondary-sage));
            border-radius: 3px;
            box-shadow: 0 0 10px rgba(123, 156, 173, 0.3);
        }

        .sidebar-nav {
            margin-bottom: 15px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            margin-bottom: 8px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--steel-gray);
            font-weight: 500;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .sidebar-nav a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--primary-slate), var(--secondary-sage));
            transition: width var(--transition-normal);
            opacity: 0.1;
        }

        .sidebar-nav a:hover::before,
        .sidebar-nav a.active::before {
            width: 100%;
        }

        .sidebar-nav a:hover {
            background-color: var(--soft-blue);
            color: var(--dark-navy);
            transform: translateX(5px);
            box-shadow: var(--shadow-sm);
        }

        .sidebar-nav a.active {
            background: linear-gradient(135deg, var(--primary-slate), var(--muted-teal));
            color: white;
            font-weight: 600;
            box-shadow: var(--shadow-md);
        }

        .sidebar-nav a i {
            margin-right: 12px;
            font-size: 1.1rem;
            min-width: 20px;
            text-align: center;
        }

        /* CONTENIDO PRINCIPAL */
        .main-content {
            flex-grow: 1;
            padding: 0;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-header {
            background: linear-gradient(135deg, #FFFFFF 0%, var(--light-cream) 100%);
            padding: 20px 30px;
            box-shadow: var(--shadow-md);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--primary-slate);
        }

        .main-header h2 {
            font-weight: 700;
            color: var(--dark-navy);
            font-size: 1.8rem;
            margin: 0;
            line-height: 1;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
        }

        .main-header h2 i {
            background: linear-gradient(135deg, var(--primary-slate), var(--secondary-sage));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .header-actions .btn {
            font-size: 0.9rem;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .header-actions .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.4s, height 0.4s;
        }

        .header-actions .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-password-header {
            background: linear-gradient(135deg, var(--primary-slate), var(--muted-teal));
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-password-header:hover {
            background: linear-gradient(135deg, var(--muted-teal), var(--primary-slate));
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .btn-logout-header {
            background: linear-gradient(135deg, var(--danger-coral), #C16868);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-logout-header:hover {
            background: linear-gradient(135deg, #C16868, var(--danger-coral));
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .content-area {
            padding: 0 25px 25px 25px;
        }

        /* WIDGETS DE ESTADÍSTICAS */
        .stat-widget {
            background: linear-gradient(135deg, #FFFFFF 0%, var(--light-cream) 100%);
            border-radius: 15px;
            padding: 25px 20px;
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: all var(--transition-normal);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .stat-widget::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(123, 156, 173, 0.1) 0%, transparent 70%);
            transition: var(--transition-slow);
            opacity: 0;
        }

        .stat-widget:hover::before {
            opacity: 1;
            transform: scale(1.2);
        }

        .stat-widget:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-slate);
        }

        .stat-widget h5 {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--steel-gray);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-widget .count {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            position: relative;
            z-index: 1;
        }

        .widget-total .count {
            background: linear-gradient(135deg, var(--success-green), #7FAF7F);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .widget-available .count {
            background: linear-gradient(135deg, var(--primary-slate), var(--info-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .widget-loaned .count {
            background: linear-gradient(135deg, var(--warning-amber), #C69563);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card-container {
            background: linear-gradient(135deg, #FFFFFF 0%, var(--light-cream) 100%);
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--shadow-md);
            border: 2px solid var(--soft-blue);
            height: 100%;
            transition: var(--transition-normal);
        }

        .card-container:hover {
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-slate);
        }

        h4 {
            font-weight: 600;
            color: var(--dark-navy);
            font-size: 1.4rem;
            margin-bottom: 1.2rem;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary-slate);
        }

        h4 i {
            background: linear-gradient(90deg, var(--primary-slate), var(--secondary-sage));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid var(--soft-blue);
            padding: 12px 15px;
            transition: all var(--transition-fast);
            background-color: #FFFFFF;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-slate);
            box-shadow: 0 0 0 4px rgba(123, 156, 173, 0.1);
            outline: none;
            background-color: #FFFFFF;
        }

        .form-label {
            font-weight: 500;
            color: var(--steel-gray);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--primary-slate), var(--muted-teal));
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 500;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .btn-custom::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.4s, height 0.4s;
        }

        .btn-custom:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-custom:hover {
            background: linear-gradient(135deg, var(--muted-teal), var(--primary-slate));
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            color: white;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table > :not(caption) > * > * {
            padding: 14px 16px;
            font-size: 0.9rem;
        }

        .table thead {
            background: linear-gradient(135deg, var(--primary-slate), var(--muted-teal));
            color: white;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .table thead th {
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(193, 213, 224, 0.1);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(123, 156, 173, 0.15);
            transition: var(--transition-fast);
        }

        .table-actions {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .table-actions .btn-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.8rem;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-fast);
        }

        .btn-edit-clean {
            background: linear-gradient(135deg, var(--warning-amber), #C69563);
            border: none;
            color: white;
        }

        .btn-edit-clean:hover {
            background: linear-gradient(135deg, #C69563, var(--warning-amber));
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .btn-delete-clean {
            background: linear-gradient(135deg, var(--danger-coral), #C16868);
            border: none;
            color: white;
        }

        .btn-delete-clean:hover {
            background: linear-gradient(135deg, #C16868, var(--danger-coral));
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--steel-gray), var(--muted-teal));
            border: none;
            color: white;
            border-radius: 10px;
            transition: all var(--transition-normal);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--muted-teal), var(--steel-gray));
            transform: translateY(-2px);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-amber), #C69563);
            border: none;
            color: white;
            border-radius: 8px;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #C69563, var(--warning-amber));
            color: white;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.85rem;
            box-shadow: var(--shadow-sm);
        }

        .bg-success {
            background: linear-gradient(135deg, var(--success-green), #7FAF7F) !important;
        }

        .bg-warning {
            background: linear-gradient(135deg, var(--warning-amber), #C69563) !important;
        }

        .bg-danger {
            background: linear-gradient(135deg, var(--danger-coral), #C16868) !important;
        }

        .bg-primary {
            background: linear-gradient(135deg, var(--primary-slate), var(--info-blue)) !important;
        }

        .bg-secondary {
            background: linear-gradient(135deg, var(--steel-gray), var(--muted-teal)) !important;
        }

        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            border: 2px solid;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background-color: rgba(143, 188, 143, 0.15);
            border-color: var(--success-green);
            color: #4A5F4A;
        }

        .alert-danger {
            background-color: rgba(209, 123, 123, 0.15);
            border-color: var(--danger-coral);
            color: #6D4545;
        }

        .alert-warning {
            background-color: rgba(212, 165, 116, 0.15);
            border-color: var(--warning-amber);
            color: #6D5F45;
        }

        .returned-row {
            background-color: rgba(143, 188, 143, 0.15) !important;
        }

        .pending-row {
            background-color: rgba(212, 165, 116, 0.15) !important;
        }

        .overdue-row {
            background-color: rgba(209, 123, 123, 0.15) !important;
        }

        .table-danger {
            background-color: rgba(209, 123, 123, 0.2) !important;
        }

        .table-warning {
            background-color: rgba(212, 165, 116, 0.2) !important;
        }

        .table-delayed-returns thead th {
            background: linear-gradient(135deg, var(--warning-amber), #C69563) !important;
            color: white !important;
        }

        hr {
            border: none;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--soft-blue), transparent);
            margin: 20px 0;
        }

        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-cream);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--primary-slate), var(--muted-teal));
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--muted-teal), var(--primary-slate));
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            Préstamos
        </div>

        <nav class="sidebar-nav">
            <a href="index.php?tab=dashboard" class="<?= $active_tab == 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Inicio
            </a>

            <a href="index.php?tab=loans" class="<?= $active_tab == 'loans' ? 'active' : '' ?>">
                <i class="fas fa-book-open"></i> Préstamos Activos
            </a>

            <a href="index.php?tab=delayed" class="<?= $active_tab == 'delayed' ? 'active' : '' ?>">
                <i class="fas fa-exclamation-triangle"></i> Devoluciones Atrasadas
            </a>

            <a href="editar.php">
                <i class="fas fa-plus-circle"></i> Registrar Equipo
            </a>

            <a href="historial.php">
                <i class="fas fa-history"></i> Historial
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="main-header">
            <h2><i class="fas fa-laptop-code me-2"></i>Control de Préstamos de computadoras</h2>
            <div class="header-actions">
                <a href="change_password.php" class="btn btn-password-header">
                    <i class="fas fa-lock me-1"></i> Cambiar Contraseña
                </a>
                <a href="logout.php" class="btn btn-logout-header">
                    <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                </a>
            </div>
        </div>

        <div class="content-area">
            <?php
            if (isset($_SESSION['message'])) {
                $message_class = ($_SESSION['message_type'] == 'success') ? 'alert-success' : 'alert-danger';
                if ($_SESSION['message_type'] == 'warning') {
                     $message_class = 'alert-warning';
                }

                echo '<div class="alert ' . $message_class . ' alert-dismissible fade show mb-4" role="alert">';
                echo $_SESSION['message'];
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
                
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>
            
            <div class="row g-3 mb-4">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="stat-widget widget-total">
                        <h5>TOTAL DE EQUIPOS</h5>
                        <div class="count"><?= $total_equipos ?></div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="stat-widget widget-available">
                        <h5>DISPONIBLES</h5>
                        <div class="count"><?= $equipos_disponibles ?></div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="stat-widget widget-loaned">
                        <h5>EN PRÉSTAMO</h5>
                        <div class="count"><?= $equipos_en_prestamo ?></div>
                    </div>
                </div>
            </div>

            <?php if ($active_tab == 'dashboard'): ?>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card-container">
                            <h4><i class="fas fa-share-square me-2"></i>Registrar Préstamo</h4>
                            <hr>
                            <form action="procesar.php" method="POST" class="row g-3">
                                <input type="hidden" name="action" value="add_loan">

                                <div class="col-lg-6 col-md-6">
                                    <label class="form-label"><i class="fas fa-laptop-code me-1"></i>Laptop</label>
                                    <select name="laptop" class="form-select" required> 
                                        <option value="">-- Seleccione uno --</option>
                                        <?php
                                        if ($result_available) $result_available->data_seek(0);

                                        if ($result_available && $result_available->num_rows > 0): ?>
                                            <?php while($computer = $result_available->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($computer['id']) ?>"> 
                                                    <?= htmlspecialchars($computer['internal_id']) . ' - ' . htmlspecialchars($computer['brand_model']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <option value="" disabled>No hay equipos disponibles</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="col-lg-6 col-md-6">
                                    <label class="form-label"><i class="fas fa-user-graduate me-1"></i>Estudiante</label>
                                    <input type="text" name="student_name" class="form-control" required>
                                </div>

                                <div class="col-lg-6 col-md-6">
                                    <label class="form-label"><i class="fas fa-phone me-1"></i>Teléfono</label>
                                    <input type="text" name="student_phone" class="form-control" required>
                                </div>

                                <div class="col-lg-6 col-md-6">
                                    <label class="form-label"><i class="fas fa-book me-1"></i>Curso</label>
                                    <input type="text" name="course" class="form-control" required>
                                </div>

                                <div class="col-lg-6 col-md-6">
                                    <label class="form-label"><i class="fas fa-users me-1"></i>Paralelo</label>
                                    <input type="text" name="parallel" class="form-control">
                                </div>

                                <div class="col-lg-6 col-md-6">
                                    <label class="form-label"><i class="fas fa-calendar-alt me-1"></i>F. Préstamo</label>
                                    <input type="date" name="loan_date" class="form-control" required value="<?= date('Y-m-d'); ?>">
                                </div>

                                <div class="col-lg-6 col-md-6">
                                    <label class="form-label"><i class="fas fa-calendar-check me-1"></i>F. Devolución</label>
                                    <input type="date" name="return_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                                </div>

                                <div class="col-lg-6 col-md-6 d-flex align-items-end">
                                    <button type="submit" class="btn btn-custom w-100"><i class="fas fa-paper-plane me-1"></i> Registrar Préstamo</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card-container">
                            <h4><i class="fas fa-list-ul me-2"></i>Inventario de Computadoras</h4>
                            <hr>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="text-white">
                                        <tr>
                                            <th>ID Interno</th>
                                            <th>Marca y Modelo</th>
                                            <th>N. de Serie</th>
                                            <th>SO</th>
                                            <th>Estado</th>
                                            <th style="width: 170px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while($computer = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($computer['internal_id']) ?></td>
                                            <td><?= htmlspecialchars($computer['brand_model']) ?></td>
                                            <td><?= htmlspecialchars($computer['serial_number']) ?></td>
                                            <td><?= htmlspecialchars($computer['os']) ?></td>
                                            <td>
                                                <?php
                                                $status_class = ($computer['status'] == 'available') ? 'bg-success' :
                                                                     (($computer['status'] == 'loaned') ? 'bg-warning text-dark' : 'bg-secondary');
                                                echo "<span class='badge {$status_class}'>" . htmlspecialchars(ucfirst($computer['status'])) . "</span>";
                                                ?>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="editar.php?id=<?= $computer['id'] ?>" class="btn btn-edit-clean btn-sm"><i class="fas fa-edit"></i> Editar</a>
                                                    <a href="procesar.php?action=delete_computer&id=<?= $computer['id'] ?>" class="btn btn-delete-clean btn-sm" onclick="return confirm('ADVERTENCIA: ¿Estás seguro de que deseas eliminar este equipo? Esto eliminará también su historial de préstamos.');"><i class="fas fa-trash-alt"></i> Eliminar</a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No hay computadoras registradas.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($active_tab == 'loans'):
                $hay_atrasos = false;
                $result_active_loans->data_seek(0);
                while ($loan_check = $result_active_loans->fetch_assoc()) {
                    if (strtotime($loan_check['return_date']) < strtotime($current_date)) {
                        $hay_atrasos = true;
                        break;
                    }
                }
            ?>

                <?php if ($hay_atrasos): ?>
                    <div class="alert alert-danger mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> **¡ALERTA!** Hay préstamos vencidos que requieren atención inmediata.
                    </div>
                <?php endif; ?>

                <div class="card-container mb-3">
                    <h4><i class="fas fa-book-open me-2"></i>Detalle de Préstamos Activos</h4>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="text-white">
                                <tr>
                                    <th>Equipo (ID Interno)</th>
                                    <th>Estudiante</th>
                                    <th>Teléfono</th>
                                    <th>Curso / Paralelo</th>
                                    <th>F. Préstamo</th>
                                    <th>F. Devolución (Esperada)</th>
                                    <th style="width: 100px;">Estatus</th>
                                    <th style="width: 100px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result_active_loans->data_seek(0);
                                if ($result_active_loans && $result_active_loans->num_rows > 0): ?>
                                <?php while($loan = $result_active_loans->fetch_assoc()):
                                    $is_overdue = (strtotime($loan['return_date']) < strtotime($current_date));
                                    $row_class = $is_overdue ? 'table-danger' : '';
                                ?>
                                <tr class="<?= $row_class ?>">
                                    <td><?= htmlspecialchars($loan['internal_id']) . ' - ' . htmlspecialchars($loan['brand_model']) ?></td>
                                    <td><?= htmlspecialchars($loan['student_name']) ?></td>
                                    <td><?= htmlspecialchars($loan['student_phone']) ?></td>
                                    <td><?= htmlspecialchars($loan['course']) . ' / ' . htmlspecialchars($loan['parallel']) ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($loan['loan_date']))) ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($loan['return_date']))) ?></td>
                                    <td>
                                        <?php if ($is_overdue): ?>
                                            <span class="badge bg-danger"><i class="fas fa-clock"></i> ATRASADO</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="procesar.php?action=return_loan&id=<?= $loan['id'] ?>&computer_id=<?= $loan['computer_id'] ?>"
                                            class="btn btn-sm btn-warning"
                                            onclick="return confirm('¿Confirmar devolución?')">Devolver</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No hay préstamos activos.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($active_tab == 'delayed'): ?>

                <div class="card-container">
                    <h4><i class="fas fa-exclamation-triangle me-2"></i>Historial de Devoluciones Atrasadas</h4>
                    <hr>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover table-delayed-returns">
                            <thead class="text-dark">
                                <tr>
                                    <th>ID Interno</th>
                                    <th>Modelo</th>
                                    <th>Estudiante</th>
                                    <th>Teléfono</th>
                                    <th>Curso / Paralelo</th>
                                    <th>F. Esperada</th>
                                    <th>F. Real</th>
                                    <th>Días de Retraso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_delayed_returns && $result_delayed_returns->num_rows > 0): ?>
                                    <?php while ($delay = $result_delayed_returns->fetch_assoc()): ?>
                                        <tr class="table-warning">
                                            <td><?= htmlspecialchars($delay['internal_id']) ?></td>
                                            <td><?= htmlspecialchars($delay['brand_model']) ?></td>
                                            <td><?= htmlspecialchars($delay['student_name']) ?></td>
                                            <td><?= htmlspecialchars($delay['student_phone']) ?></td>
                                            <td><?= htmlspecialchars($delay['course']) . ' / ' . htmlspecialchars($delay['parallel']) ?></td>
                                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($delay['expected_date']))) ?></td>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($delay['actual_date']))) ?></td>
                                            <td><span class="badge bg-danger"><?= htmlspecialchars($delay['days_delayed']) ?> días</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No hay devoluciones con retraso registradas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>