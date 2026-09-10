<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Central mail sender for the whole system.
 *
 * @param array $toAddresses list of email addresses
 * @param string $subject email subject
 * @param string $message plain text message (will be converted to HTML paragraphs)
 * @param array $attachments list of absolute file paths OR associative:
 *   [
 *     'paths' => ['/abs/a.pdf', '/abs/b.png'],
 *     'named' => [['path'=>..., 'name'=>...], ...]
 *   ]
 * @return true|string true on success, or error message string
 */
function sendEmail(array $toAddresses, string $subject, string $message, array $attachments = [])
{
    // IMPORTANT:
    // Put credentials in environment variables or config.php
    // Update these lookups to match your config style.
    $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'mail.wttzap.co.za';
    $smtpUser = defined('SMTP_USER') ? SMTP_USER : 'info@wttzap.co.za';
    $smtpPass = defined('SMTP_PASS') ? SMTP_PASS : '';   // set this in config, NOT here
    $smtpPort = defined('SMTP_PORT') ? (int)SMTP_PORT : 465;

    if ($smtpPass === '') {
        return 'SMTP password not configured (SMTP_PASS empty)';
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $smtpPort;

        $mail->Timeout = 15;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($smtpUser, 'Workplace Reports');

        foreach ($toAddresses as $address) {
            $address = trim((string)$address);
            if ($address !== '') {
                $mail->addAddress($address);
            }
        }

        if (count($mail->getToAddresses()) === 0) {
            return 'No valid recipient addresses provided';
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;

        // Escape message, keep line breaks as paragraphs
        $escapedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $htmlMessage    = '<p>' . str_replace("\n", '</p><p>', $escapedMessage) . '</p>';

        $mail->Body = "<html>
          <head>
            <style>
              body { font-family: Arial, sans-serif; color:#111; }
              .wrap { padding: 18px; }
              .h { font-size: 16px; font-weight: 700; margin-bottom: 10px; }
              .c p { margin: 0 0 8px; }
              .f { margin-top: 16px; font-size: 12px; color: #777; }
            </style>
          </head>
          <body>
            <div class='wrap'>
              <div class='h'>" . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . "</div>
              <div class='c'>{$htmlMessage}</div>
              <div class='f'>Workplace Reports</div>
            </div>
          </body>
        </html>";

        // Plain text alt body
        $mail->AltBody = $message;

        // Attachments support
        if (isset($attachments['paths']) && is_array($attachments['paths'])) {
            foreach ($attachments['paths'] as $path) {
                $path = (string)$path;
                if ($path !== '' && is_file($path)) {
                    $mail->addAttachment($path);
                }
            }
        } elseif (isset($attachments['named']) && is_array($attachments['named'])) {
            foreach ($attachments['named'] as $att) {
                $path = (string)($att['path'] ?? '');
                $name = (string)($att['name'] ?? '');
                if ($path !== '' && is_file($path)) {
                    $name !== '' ? $mail->addAttachment($path, $name) : $mail->addAttachment($path);
                }
            }
        } else {
            // Backward compatible: allow ['report'=>path, 'image'=>path]
            foreach (['report','image'] as $k) {
                if (!empty($attachments[$k]) && is_file($attachments[$k])) {
                    $mail->addAttachment($attachments[$k]);
                }
            }
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        return "Mailer Error: {$mail->ErrorInfo}";
    }
}