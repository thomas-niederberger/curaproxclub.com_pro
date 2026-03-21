<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/../vendor/autoload.php';

function sendMagicLinkEmail($toEmail, $toName, $magicLink) {
	$mail = new PHPMailer(true);
	
	try {
		$mail->isSMTP();
		$mail->Host = $_ENV['SMTP_HOST'];
		$mail->SMTPAuth = true;
		$mail->Username = $_ENV['SMTP_USER'];
		$mail->Password = $_ENV['SMTP_PASS'];
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		$mail->Port = 587;
		
		$mail->setFrom($_ENV['SMTP_USER'], 'CURAPROX');
		$mail->addReplyTo($_ENV['SMTP_REPLYTO'], 'CURAPROX Support');
		$mail->addAddress($toEmail, $toName);
		
		$mail->isHTML(true);
		$mail->Subject = 'The Portal - LOGIN TOKEN';
		$mail->Body = "
			<html>
			<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
				<div style='margin-bottom: 60px;'>
					<h2 style='color: #000000;'><strong>CURAPROX</strong></h2>
				</div>
				<div>
					<h2 style='color: #003da5;font-weight: 300;'>The Portal <strong>WELCOME</strong></h2>
					<p>Hi {$toName},</p>
					<p>Click the button below to securely log in to your account.<br>This link will expire in 15 minutes.</p>
					<div style='margin: 30px 0;'>
						<a href='{$magicLink}' style='background-color: #003da5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Log In to The Portal</a>
					</div>
					<p>Or copy and paste this link into your browser:</p>
					<p style='word-break: break-all; color: #003da5;'>{$magicLink}</p>
					<p style='color: #666; font-size: 12px; margin-top: 30px;'>If you didn't request this login link, you can safely ignore this email.</p>
				</div>
			</body>
			</html>
		";
		$mail->AltBody = "Hi {$toName},\n\nClick this link to log in to The Portal:\n{$magicLink}\n\nThis link will expire in 15 minutes.\n\nIf you didn't request this, you can safely ignore this email.";
		
		$mail->send();
		return true;
	} catch (Exception $e) {
		error_log("Mailer Error: {$mail->ErrorInfo}");
		return false;
	}
}
