<?php
// Incluir la librería PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Crear una instancia de PHPMailer
$mail = new PHPMailer(true);

try {
    // Configuración del servidor
    $mail->isSMTP();                                            // Usar SMTP
    $mail->Host       = 'smtp.gmail.com';                         // Servidor SMTP de Gmail (puedes cambiarlo por el que uses)
    $mail->SMTPAuth   = true;                                     // Activar autenticación SMTP
    $mail->Username   = 'tu_correo@gmail.com';                    // Tu correo electrónico
    $mail->Password   = 'tu_contraseña';                          // Tu contraseña (es recomendable usar una contraseña de aplicación si usas Gmail)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;           // Cifrado TLS
    $mail->Port       = 587;                                      // Puerto SMTP

    // Remitente y destinatario
    $mail->setFrom('tu_correo@gmail.com', 'Tu Nombre');
    $mail->addAddress('destinatario@dominio.com', 'Destinatario'); // Agregar destinatario

    // Archivos adjuntos
    $mail->addAttachment('/ruta/del/archivo/archivo.pdf');       // Ruta al archivo PDF

    // Contenido del correo
    $mail->isHTML(true);                                          // Establecer formato HTML
    $mail->Subject = 'Asunto del correo';
    $mail->Body    = 'Este es el cuerpo del correo. <b>Adjunto el archivo PDF.</b>';

    // Enviar correo
    $mail->send();
    echo 'El mensaje fue enviado correctamente';
} catch (Exception $e) {
    echo "No se pudo enviar el mensaje. Mailer Error: {$mail->ErrorInfo}";
}
?>
