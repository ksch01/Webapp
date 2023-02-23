<?php
    require_once __DIR__ . "/../vendor/autoload.php";

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Mailer = "smtp";
    $mail->SMTPAuth = true;
    $mail->SMPTSecure = "tls";

    $mail->Port = smtpPort;
    $mail->Host = smtpAddress;
    $mail->Username = mailUsername;
    $mail->Password = mailPassword;

    $mail->isHTML();
    $mail->setFrom(mailUsername);
    $mail->Subject = "Ihre Regestrierung";
    
    const SIGNUP_MAIL_SUBJECT = "Ihre Regestrierung";
    function getSignupMailMessage($key){
        return
        "
        <html>
            <body>
                <h1>Ihre Regestrierung</h1>
                Klicken Sie <a href=localhost/index.php/varify?key=$key>hier</a> um Ihre Regestrierung abzuschließen
            </body>
        </html>
        ";
    }

    function sendSignupMail($email, $key){
        global $mail;
        
        $mail->addAddress($email);
        $mail->msgHtml(getSignupMailMessage($key));
        
        if(!$mail->send()){
            echo "Error while sending Email.";
            http_response_code(500);
            exit;
        }
    }
?>