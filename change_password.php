<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    if ($new_password !== $confirm_password) {
        $error = "La nueva contraseña y la confirmación no coinciden.";
    } else {
        // 1. Obtener el hash de la contraseña actual
        $sql = "SELECT password FROM users WHERE id = $user_id";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $hashed_password = $row['password'];

        if (password_verify($current_password, $hashed_password)) {
            // 2. Hashear la nueva contraseña y actualizar
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = '$new_hashed_password' WHERE id = $user_id";

            if ($conn->query($update_sql) === TRUE) {
                $message = "Contraseña cambiada exitosamente.";
            } else {
                $error = "Error al actualizar la contraseña: " . $conn->error;
            }
        } else {
            $error = "La contraseña actual es incorrecta.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 600px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
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
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary-slate);
            text-align: center;
        }

        h2 i {
            background: linear-gradient(135deg, var(--primary-slate), var(--secondary-sage));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card-container {
            background: linear-gradient(135deg, #FFFFFF 0%, var(--light-cream) 100%);
            border-radius: 15px;
            padding: 35px;
            box-shadow: var(--shadow-lg);
            border: 3px solid var(--soft-blue);
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

        .btn-custom {
            background: linear-gradient(135deg, var(--primary-slate), var(--muted-teal));
            border: none;
            color: white;
            padding: 12px 28px;
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

        .btn-secondary {
            background: linear-gradient(135deg, var(--steel-gray), var(--muted-teal));
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 500;
            transition: all var(--transition-normal);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--muted-teal), var(--steel-gray));
            transform: translateY(-2px);
            color: white;
        }

        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            border: 2px solid;
            animation: slideDown 0.3s ease;
            margin-bottom: 25px;
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

        .text-end {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-lock me-2"></i>Cambiar Contraseña</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $message ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
        <?php endif; ?>

        <div class="card-container">
            <form method="POST">
                <div class="mb-3">
                    <label for="current_password" class="form-label">
                        <i class="fas fa-key me-1"></i>Contraseña Actual
                    </label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">
                        <i class="fas fa-lock me-1"></i>Nueva Contraseña
                    </label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-shield-alt me-1"></i>Confirmar Nueva Contraseña
                    </label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="mt-4 text-end">
                    <a href="index.php" class="btn btn-secondary me-2">
                        <i class="fas fa-chevron-left me-1"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-custom">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>