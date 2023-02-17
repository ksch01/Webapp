<?php
    const SIGNUP_MAIL_SUBJECT = "Ihre Regestrierung";
    function getSignupMailMessage($key){
        "
        <html>
            <head>
                <title>Ihre Regestrierung</title>
            </head>
            <body>
                Klicken Sie <a href=localhost/skygate/index.php/account/?key=$key>hier</a> um Ihre Regestrierung abzuschließen
            </body>
        </html>
        ";
    }

    function sendSignupMail($email, $key){
        return mail($email, SIGNUP_MAIL_SUBJECT, getSignupMailMessage($key));
    }
?>