<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Obtener el historial completo de préstamos
$sql_history = "SELECT 
                    l.id,
                    l.student_name,
                    l.student_phone,
                    l.course,
                    l.parallel,
                    l.loan_date,
                    l.return_date,
                    l.returned_at,
                    c.internal_id,
                    c.brand_model
                FROM loans l
                JOIN computers c ON l.computer_id = c.id
                ORDER BY l.id DESC";
$result_history = $conn->query($sql_history);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Préstamos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
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
            
            --shadow-sm: 0 2px 8px rgba(123, 156, 173, 0.1);
            --shadow-md: 0 4px 16px rgba(123, 156, 173, 0.15);
            --shadow-lg: 0 8px 24px rgba(123, 156, 173, 0.2);
            --shadow-hover: 0 6px 20px rgba(123, 156, 173, 0.25);
            
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--light-cream) 0%, var(--soft-blue) 50%, var(--warm-beige) 100%);
            color: var(--dark-navy);
            padding: 40px 20px;
            min-height: 100vh;
        }

        .container-fluid {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-container {
            background: linear-gradient(135deg, #FFFFFF 0%, var(--light-cream) 100%);
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            border: 3px solid var(--soft-blue);
            margin: 0 auto;
            position: relative;
        }

        .card-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-slate), var(--secondary-sage), var(--accent-mint));
            border-radius: 15px 15px 0 0;
        }

        h2 {
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-slate), var(--secondary-sage));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary-slate);
        }

        h2 i {
            background: linear-gradient(135deg, var(--primary-slate), var(--secondary-sage));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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

        /* Estilo para filas de préstamos devueltos */
        .returned-row {
            background-color: rgba(143, 188, 143, 0.15) !important;
        }
        
        .pending-row {
            background-color: rgba(212, 165, 116, 0.15) !important;
        }
        
        .overdue-row {
            background-color: rgba(209, 123, 123, 0.15) !important;
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
            background: linear-gradient(135deg, var(--primary-slate), var(--muted-teal)) !important;
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
    <div class="container-fluid">
        <h2><i class="fas fa-history me-2"></i>Historial Completo de Préstamos</h2>
        
        <div class="card-container">
            <a href="index.php" class="btn btn-custom mb-3">
                <i class="fas fa-chevron-left me-1"></i> Volver al Inicio
            </a>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Equipo</th>
                            <th>Modelo</th>
                            <th>Estudiante</th>
                            <th>Teléfono</th>
                            <th>Curso / Paralelo</th>
                            <th>F. Préstamo</th>
                            <th>F. Devolución Prevista</th>
                            <th>Devuelto en</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_history && $result_history->num_rows > 0): ?>
                        <?php 
                        $current_date = date('Y-m-d');
                        while($loan = $result_history->fetch_assoc()): 
                            $is_returned = !is_null($loan['returned_at']);
                            $is_overdue = !$is_returned && (strtotime($loan['return_date']) < strtotime($current_date));
                            
                            // Determinar clase de la fila
                            if ($is_returned) {
                                $row_class = 'returned-row';
                            } elseif ($is_overdue) {
                                $row_class = 'overdue-row';
                            } else {
                                $row_class = 'pending-row';
                            }
                        ?>
                        <tr class="<?= $row_class ?>">
                            <td><?= htmlspecialchars($loan['id']) ?></td>
                            <td><?= htmlspecialchars($loan['internal_id']) ?></td>
                            <td><?= htmlspecialchars($loan['brand_model']) ?></td>
                            <td><?= htmlspecialchars($loan['student_name']) ?></td>
                            <td><?= htmlspecialchars($loan['student_phone']) ?></td>
                            <td><?= htmlspecialchars($loan['course']) . ' / ' . htmlspecialchars($loan['parallel']) ?></td>
                            <td><?= date('d/m/Y', strtotime($loan['loan_date'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($loan['return_date'])) ?></td>
                            <td>
                                <?php if ($is_returned): ?>
                                    <span class="badge bg-success">
                                        <?= date('d/m/Y H:i', strtotime($loan['returned_at'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_returned): ?>
                                    <span class="badge bg-success">Devuelto</span>
                                <?php elseif ($is_overdue): ?>
                                    <span class="badge bg-danger">Atrasado</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Activo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted">No hay historial de préstamos.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>