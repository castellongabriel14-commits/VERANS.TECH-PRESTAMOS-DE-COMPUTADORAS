<?php
session_start();
require 'config.php'; // Asegúrate de que $conn esté definida aquí

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Variables para editar o añadir
$is_editing = false;
$computer_data = [
    'id' => '',
    'internal_id' => '',
    'brand_model' => '',
    'serial_number' => '',
    'os' => ''
];
$form_title = "Registrar Nuevo Equipo";
$action_value = "add_computer";

// Lógica para modo edición
if (isset($_GET['id'])) {
    // Es CRUCIAL usar sentencias preparadas aquí también, aunque solo sea un GET
    // El original usaba real_escape_string, lo corregimos a Sentencia Preparada (mejor seguridad)
    $sql = "SELECT * FROM computers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $computer_data = $result->fetch_assoc();
            $is_editing = true;
            $form_title = "Editar Equipo: " . htmlspecialchars($computer_data['internal_id']);
            $action_value = "edit_computer";
        }
        $stmt->close();
    }
}

// Obtener computadoras para la MINI-TABLA (Inventario Rápido)
$sql_mini_inventory = "SELECT internal_id, brand_model, status FROM computers ORDER BY internal_id ASC LIMIT 10"; 
$result_mini_inventory = $conn->query($sql_mini_inventory);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $is_editing ? 'Editar Equipo' : 'Registrar Equipo' ?> - Préstamos</title>
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

        .container {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-slate), var(--secondary-sage));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        h2 i {
            background: linear-gradient(135deg, var(--primary-slate), var(--secondary-sage));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card-form {
            background: linear-gradient(135deg, #FFFFFF 0%, var(--light-cream) 100%);
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            border: 3px solid var(--soft-blue);
            position: relative;
        }

        .card-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-slate), var(--secondary-sage), var(--accent-mint));
            border-radius: 15px 15px 0 0;
        }

        .h5 {
            font-weight: 600;
            color: var(--dark-navy);
            margin-bottom: 1rem;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-slate);
        }

        .text-primary {
            background: linear-gradient(135deg, var(--primary-slate), var(--secondary-sage));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid var(--soft-blue);
            padding: 12px 15px;
            transition: all var(--transition-fast);
            background-color: #FFFFFF;
        }

        .form-control:focus {
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

        .btn-primary {
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

        .btn-primary::before {
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

        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--muted-teal), var(--primary-slate));
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--steel-gray), var(--muted-teal));
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 500;
            transition: all var(--transition-normal);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--muted-teal), var(--steel-gray));
            transform: translateY(-2px);
            color: white;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table > :not(caption) > * > * {
            padding: 12px;
            font-size: 0.85rem;
        }

        .table thead {
            background: linear-gradient(135deg, var(--primary-slate), var(--muted-teal));
            color: white;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .table thead th {
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(193, 213, 224, 0.1);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(123, 156, 173, 0.15);
            transition: var(--transition-fast);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.8rem;
            box-shadow: var(--shadow-sm);
        }

        .bg-success {
            background: linear-gradient(135deg, var(--success-green), #7FAF7F) !important;
        }

        .bg-danger {
            background: linear-gradient(135deg, #D17B7B, #C16868) !important;
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
    </style>
</head>
<body>

<div class="container">
    <div class="text-center mb-4">
        <h2><i class="fas fa-desktop me-2"></i><?= htmlspecialchars($form_title) ?></h2>
    </div>

    <div class="row g-4 justify-content-center">
        
        <div class="col-lg-7">
            <div class="card-form">
                <form action="procesar.php" method="POST" class="row g-3">
                    <input type="hidden" name="action" value="<?= htmlspecialchars($action_value) ?>">
                    <?php if ($is_editing): ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($computer_data['id']) ?>">
                    <?php endif; ?>

                    <div class="col-md-6">
                        <label for="internal_id" class="form-label"><i class="fas fa-tag me-1"></i>ID Interno o Etiqueta</label>
                        <input type="text" name="internal_id" id="internal_id" class="form-control" 
                               required value="<?= htmlspecialchars($computer_data['internal_id']) ?>"
                               <?= $is_editing ? 'readonly' : '' // Evitar editar el ID Interno una vez registrado ?>>
                    </div>

                    <div class="col-md-6">
                        <label for="brand_model" class="form-label"><i class="fas fa-laptop me-1"></i>Marca y Modelo</label>
                        <input type="text" name="brand_model" id="brand_model" class="form-control" 
                               required value="<?= htmlspecialchars($computer_data['brand_model']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="serial_number" class="form-label"><i class="fas fa-barcode me-1"></i>Número de Serie</label>
                        <input type="text" name="serial_number" id="serial_number" class="form-control" 
                               required value="<?= htmlspecialchars($computer_data['serial_number']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="os" class="form-label"><i class="fas fa-windows me-1"></i>Sistema Operativo</label>
                        <input type="text" name="os" id="os" class="form-control" 
                               value="<?= htmlspecialchars($computer_data['os']) ?>">
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="index.php" class="btn btn-secondary me-2"><i class="fas fa-angle-left me-1"></i> Volver</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> <?= $is_editing ? 'Guardar Cambios' : 'Registrar Equipo' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-lg-5">
            <div class="card-form">
                <p class="h5 text-primary"><i class="fas fa-boxes me-2"></i>Inventario Rápido</p>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>ID Interno</th>
                                <th>Modelo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_mini_inventory->num_rows > 0): ?>
                                <?php while($mini = $result_mini_inventory->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($mini['internal_id']) ?></td>
                                    <td><?= htmlspecialchars($mini['brand_model']) ?></td>
                                    <td>
                                        <?php 
                                        $status_class = ($mini['status'] == 'available') ? 'bg-success' : 'bg-danger';
                                        echo "<span class='badge {$status_class}'>" . htmlspecialchars(ucfirst($mini['status'])) . "</span>";
                                        ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No hay equipos registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>