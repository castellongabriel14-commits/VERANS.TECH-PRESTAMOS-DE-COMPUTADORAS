<?php
session_start();
require 'config.php';

// Redireccionar si el usuario no está logueado
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';

// -------------------------------------------------------------------
// MANEJO DE SOLICITUDES GET (Para eliminar computadoras y devolver préstamos)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Eliminar computadora
    if ($action == 'delete_computer' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $sql = "DELETE FROM computers WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Equipo eliminado exitosamente.";
                $message_type = 'success';
            } else {
                $message = "Error al eliminar el equipo. Asegúrate de que no tenga préstamos activos: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = "Error de preparación de sentencia (delete_computer): " . $conn->error;
            $message_type = 'danger';
        }
    }
    
    // Devolver préstamo
    if ($action == 'return_loan' && isset($_GET['id']) && isset($_GET['computer_id'])) {
        $loan_id = (int)$_GET['id'];
        $computer_id = (int)$_GET['computer_id'];
        
        // Actualizar el préstamo con la fecha/hora de devolución
        $sql_return = "UPDATE loans SET returned_at = NOW() WHERE id = ?";
        $stmt_return = $conn->prepare($sql_return);
        
        if ($stmt_return) {
            $stmt_return->bind_param("i", $loan_id);
            
            if ($stmt_return->execute()) {
                // Actualizar el estado del equipo a 'available'
                $sql_update = "UPDATE computers SET status = 'available' WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                
                if ($stmt_update) {
                    $stmt_update->bind_param("i", $computer_id);
                    
                    if ($stmt_update->execute()) {
                        $message = "Préstamo devuelto exitosamente.";
                        $message_type = 'success';
                    } else {
                        $message = "Préstamo marcado como devuelto, pero error al actualizar estado del equipo.";
                        $message_type = 'warning';
                    }
                    $stmt_update->close();
                }
            } else {
                $message = "Error al procesar la devolución: " . $stmt_return->error;
                $message_type = 'danger';
            }
            $stmt_return->close();
        }
    }
    
    // Guardar mensaje en sesión y redireccionar
    if (!empty($message)) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
    }
    header("Location: index.php");
    exit();
}

// -------------------------------------------------------------------
// MANEJO DE SOLICITUDES POST
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // Variables POST genéricas
    $internal_id = $_POST['internal_id'] ?? '';
    $brand_model = $_POST['brand_model'] ?? '';
    $serial_number = $_POST['serial_number'] ?? '';
    $os = $_POST['os'] ?? '';
    $id = $_POST['id'] ?? '';

    // Variables para PRÉSTAMOS (NOMBRES CORREGIDOS)
    $loan_computer_id = $_POST['laptop'] ?? '';
    $student_name = $_POST['student_name'] ?? '';
    $phone = $_POST['student_phone'] ?? '';
    $course = $_POST['course'] ?? '';
    $parallel = $_POST['parallel'] ?? '';
    $loan_date = $_POST['loan_date'] ?? '';
    $return_date = $_POST['return_date'] ?? '';

    // -------------------------------------------------------------------
    // REGISTRAR NUEVO EQUIPO
    // -------------------------------------------------------------------
    if ($action == 'add_computer') {
        $sql = "INSERT INTO computers (internal_id, brand_model, serial_number, os, status) 
                VALUES (?, ?, ?, ?, 'available')";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssss", $internal_id, $brand_model, $serial_number, $os);
            
            if ($stmt->execute()) {
                $message = "Equipo registrado exitosamente.";
                $message_type = 'success';
            } else {
                $message = "Error al registrar el equipo: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = "Error de preparación de sentencia (add_computer): " . $conn->error;
            $message_type = 'danger';
        }

    // -------------------------------------------------------------------
    // EDITAR EQUIPO
    // -------------------------------------------------------------------
    } elseif ($action == 'edit_computer') {
        $sql = "UPDATE computers SET 
                brand_model = ?, 
                serial_number = ?, 
                os = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssi", $brand_model, $serial_number, $os, $id);
            
            if ($stmt->execute()) {
                $message = "Equipo actualizado exitosamente.";
                $message_type = 'success';
            } else {
                $message = "Error al actualizar el equipo: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = "Error de preparación de sentencia (edit_computer): " . $conn->error;
            $message_type = 'danger';
        }

    // -------------------------------------------------------------------
    // REGISTRAR PRÉSTAMO (CORREGIDO)
    // -------------------------------------------------------------------
    } elseif ($action == 'add_loan') {
        
        // El valor ya viene como ID numérico de la tabla computers
        $numeric_computer_id = (int)$loan_computer_id;
        
        // Verificar que el equipo existe y está disponible
        $sql_check = "SELECT id FROM computers WHERE id = ? AND status = 'available'";
        $stmt_check = $conn->prepare($sql_check);
        
        if ($stmt_check) {
            $stmt_check->bind_param("i", $numeric_computer_id);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            
            if ($result->num_rows > 0) {
                $stmt_check->close();
                
                // Insertar el préstamo
                $sql_loan = "INSERT INTO loans (
                                computer_id, student_name, student_phone, course, parallel, loan_date, return_date
                              ) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_loan = $conn->prepare($sql_loan);
                
                if ($stmt_loan) {
                    $stmt_loan->bind_param("issssss", 
                        $numeric_computer_id, 
                        $student_name, 
                        $phone, 
                        $course, 
                        $parallel, 
                        $loan_date, 
                        $return_date
                    );
                    
                    if ($stmt_loan->execute()) {
                        // Actualizar estado del equipo a 'loaned'
                        $sql_update = "UPDATE computers SET status = 'loaned' WHERE id = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        
                        if ($stmt_update) {
                            $stmt_update->bind_param("i", $numeric_computer_id);
                            
                            if ($stmt_update->execute()) {
                                $message = "Préstamo registrado exitosamente.";
                                $message_type = 'success';
                            } else {
                                $message = "Préstamo registrado, pero error al actualizar estado del equipo: " . $stmt_update->error;
                                $message_type = 'warning';
                            }
                            $stmt_update->close();
                        } else {
                            $message = "Préstamo registrado, pero falló la preparación de la sentencia de actualización.";
                            $message_type = 'warning';
                        }
                    } else {
                        $message = "Error al registrar el préstamo: " . $stmt_loan->error;
                        $message_type = 'danger';
                    }
                    $stmt_loan->close();
                } else {
                    $message = "Error de preparación de sentencia (add_loan): " . $conn->error;
                    $message_type = 'danger';
                }
            } else {
                $message = "El equipo seleccionado no existe o no está disponible.";
                $message_type = 'danger';
                $stmt_check->close();
            }
        } else {
            $message = "Error al verificar disponibilidad del equipo: " . $conn->error;
            $message_type = 'danger';
        }
    }
    
    // Guardar mensaje en sesión y redireccionar
    if (!empty($message)) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
    }
    header("Location: index.php");
    exit();
}

// Si no es POST ni GET con action, redireccionar
header("Location: index.php");
exit();
?>