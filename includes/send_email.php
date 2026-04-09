<?php
/**
 * FloraFit Email - BULLETPROOF GMAIL VERSION
 * Forces email delivery + detailed error logging
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function sendEmail($to, $code, $type = 'signup') {
    $mail = new PHPMailer(true);
    
    try {
        // Gmail SMTP - Exact working config
        $mail->isSMTP();
        $mail->Host             = 'smtp.gmail.com';
        $mail->SMTPAuth         = true;
        $mail->Username         = 'masmayala23@bpsu.edu.ph';
        $mail->Password         = 'xiol ucoo ozfq uikr';  // App password
        $mail->SMTPSecure       = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port             = 587;
        $mail->SMTPOptions      = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Headers for better deliverability
        $mail->setFrom('masmayala23@bpsu.edu.ph', 'FloraFit 🌸');
        $mail->addAddress($to);
        $mail->addReplyTo('masmayala23@bpsu.edu.ph', 'FloraFit');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'FloraFit Verification: ' . $code;
        $mail->Body = getEmailTemplate($code);
        $mail->AltBody = "FloraFit Code: $code";
        
        // Anti-spam settings
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Send with timeout
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        
        $result = $mail->send();
        
        // Log success
        error_log("✅ EMAIL SENT: $to | Code: $code | Type: $type");
        
        return true;
        
    } catch (Exception $e) {
        // Detailed error logging
        $error = $mail->ErrorInfo;
        error_log("❌ EMAIL FAILED: $to | Error: $error");
        error_log("SMTP Debug: " . print_r($mail->Debugoutput, true));
        
        return false;
    }
}

function getEmailTemplate($code) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>FloraFit Verification</title>
    </head>
    <body style='margin:0;padding:40px 20px;font-family:Arial,sans-serif;background:#f5f5f5'>
        <div style='max-width:500px;margin:0 auto;background:white;border-radius:15px;padding:30px;box-shadow:0 10px 30px rgba(0,0,0,0.1)'>
            <h1 style='color:#4CAF50;text-align:center;font-size:28px;margin-bottom:20px'>🌸 FloraFit</h1>
            
            <div style='
                background:linear-gradient(135deg,#4CAF50,#45a049);
                color:white;
                padding:40px 20px;
                text-align:center;
                border-radius:15px;
                margin:30px 0;
            '>
                <div style='
                    font-size:48px;
                    font-family:monospace;
                    font-weight:bold;
                    letter-spacing:15px;
                    margin-bottom:10px;
                    text-shadow:0 2px 10px rgba(0,0,0,0.3);
                '>$code</div>
                <p style='margin:0;font-size:16px;'>Verification Code</p>
            </div>
            
            <div style='padding:20px;background:#f8f9fa;border-radius:10px;border-left:5px solid #4CAF50;'>
                <p><strong>Instructions:</strong></p>
                <ul style='text-align:left;color:#555;'>
                    <li>Enter this code on FloraFit verification page</li>
                    <li>Code expires in <strong>1 hour</strong></li>
                    <li>Didn't signup? Ignore this email</li>
                </ul>
            </div>
            
            <p style='text-align:center;color:#999;font-size:14px;margin-top:30px'>
                FloraFit Team<br>
                <a href='#' style='color:#4CAF50'>www.florafit.com</a>
            </p>
        </div>
    </body>
    </html>
    ";
}

function sendFloristCredentials($to, $name, $tempPassword) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'masmayala23@bpsu.edu.ph';
        $mail->Password   = 'xiol ucoo ozfq uikr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom('masmayala23@bpsu.edu.ph', 'FloraFit 🌸');
        $mail->addAddress($to, $name);
        $mail->addReplyTo('masmayala23@bpsu.edu.ph', 'FloraFit');
        $mail->isHTML(true);
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';
        $mail->Timeout    = 30;
        $mail->Subject    = 'Your FloraFit Florist Account';
        $mail->Body       = "
        <!DOCTYPE html>
        <html>
        <head><meta charset='UTF-8'></head>
        <body style='margin:0;padding:40px 20px;font-family:Arial,sans-serif;background:#f5f5f5'>
            <div style='max-width:520px;margin:0 auto;background:white;border-radius:15px;padding:30px;box-shadow:0 10px 30px rgba(0,0,0,0.1)'>
                <h1 style='color:#4CAF50;text-align:center;font-size:28px;margin-bottom:20px'>🌸 FloraFit</h1>
                <p style='color:#444;'>Hi <strong>{$name}</strong>,</p>
                <p style='color:#444;'>An admin has created a florist account for you on <strong>FloraFit</strong>. Use the credentials below to log in. You will be asked to change your password on first login.</p>
                <div style='background:#f0f8f0;border:1px solid #c8e6c9;border-radius:10px;padding:20px 24px;margin:24px 0;'>
                    <p style='margin:0 0 8px;color:#333;'>📧 <strong style='color:#2e7d32;'>Email:</strong> {$to}</p>
                    <p style='margin:0;color:#333;'>🔑 <strong style='color:#2e7d32;'>Temporary Password:</strong> {$tempPassword}</p>
                </div>
                <p style='color:#444;'>Please keep these credentials safe and do not share them with anyone.</p>
                <p style='font-size:13px;color:#888;border-top:1px solid #eee;margin-top:24px;padding-top:16px;'>If you did not expect this email, you can safely ignore it.</p>
                <p style='text-align:center;color:#999;font-size:14px;margin-top:30px'>FloraFit Team</p>
            </div>
        </body>
        </html>
        ";
        $mail->AltBody = "Email: {$to}\nTemporary Password: {$tempPassword}";

        $mail->send();
        error_log("✅ FLORIST EMAIL SENT: $to");
        return true;

    } catch (Exception $e) {
        error_log("❌ FLORIST EMAIL FAILED: $to | Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>