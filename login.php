<?php
session_start();
require 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT id, username, password FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = $row['username'];
            $_SESSION['user_id'] = $row['id'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
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
            
            --shadow-md: 0 4px 16px rgba(123, 156, 173, 0.15);
            --shadow-lg: 0 8px 24px rgba(123, 156, 173, 0.2);
            --shadow-hover: 0 6px 20px rgba(123, 156, 173, 0.25);
            
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--light-cream) 0%, var(--soft-blue) 50%, var(--warm-beige) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(123, 156, 173, 0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .login-container {
            background: linear-gradient(135deg, #FFFFFF 0%, var(--light-cream) 100%);
            padding: 50px 45px;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 3px solid var(--soft-blue);
            width: 100%;
            max-width: 420px;
            text-align: center;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-slate), var(--secondary-sage), var(--accent-mint));
            border-radius: 20px 20px 0 0;
        }

        .login-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-slate), var(--muted-teal));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: var(--shadow-md);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: var(--shadow-md);
            }
            50% {
                transform: scale(1.05);
                box-shadow: var(--shadow-hover);
            }
        }

        .login-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .login-container h2 {
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-slate), var(--secondary-sage));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 35px;
            padding-bottom: 12px;
            border-bottom: 3px solid var(--primary-slate);
            font-size: 1.8rem;
            letter-spacing: 0.5px;
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid var(--soft-blue);
            padding: 14px 18px;
            margin-bottom: 20px;
            transition: all var(--transition-fast);
            background-color: #FFFFFF;
            font-size: 0.95rem;
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
            text-align: left;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--steel-gray);
            font-size: 1.1rem;
            z-index: 1;
        }

        .input-group .form-control {
            padding-left: 50px;
            margin-bottom: 0;
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--primary-slate), var(--muted-teal));
            border: none;
            color: white;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 500;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-md);
            width: 100%;
            margin-top: 15px;
            font-size: 1rem;
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
            width: 400px;
            height: 400px;
        }

        .btn-custom:hover {
            background: linear-gradient(135deg, var(--muted-teal), var(--primary-slate));
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .alert {
            border-radius: 12px;
            padding: 12px 18px;
            border: 2px solid;
            animation: slideDown 0.3s ease;
            margin-bottom: 20px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background-color: rgba(209, 123, 123, 0.15);
            border-color: #D17B7B;
            color: #6D4545;
        }

        .login-footer {
            margin-top: 25px;
            color: var(--steel-gray);
            font-size: 0.85rem;
        }

        ::-webkit-scrollbar {
            width: 10px;
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
    <div class="login-container">
        <div class="login-icon">
            <i class="fas fa-laptop-code"></i>
        </div>
        
        <h2>Iniciar Sesión</h2>
        
        <form method="POST">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>
            
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" class="form-control" id="username" name="username" placeholder="Usuario" required>
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
            </div>
            
            <button type="submit" class="btn btn-custom">
                <i class="fas fa-sign-in-alt me-2"></i>Ingresar
            </button>
        </form>
        
        <div class="login-footer">
            <i class="fas fa-shield-alt me-1"></i>
            Sistema de Préstamos de Computadoras
        </div>
    </div>
</body>
</html>