<?php
/**
 * Configuration SMTP centralisée pour StageMaroc
 * 
 * Fichier conservé pour une future réactivation.
 * Actuellement, l'application fonctionne en mode démo (code affiché à l'écran).
 * 
 * Configuration SMTP :
 * - Variables d'environnement (Clever Cloud) : MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS, MAIL_FROM, etc.
 * - Variables locales (XAMPP) : modifiez directement les valeurs par défaut ci-dessous.
 */

// --- Autoload PHPMailer ---
require_once __DIR__ . '/../vendor/PHPMailer/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Crée et configure une instance de PHPMailer avec les paramètres SMTP.
 * Supporte les variables d'environnement (Clever Cloud) et les valeurs par défaut (local).
 *
 * @return PHPMailer
 */
function getMailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    // --- Configuration SMTP : priorité aux variables d'environnement (Clever Cloud) ---
    $smtp_host       = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
    $smtp_port       = getenv('MAIL_PORT') ? (int)getenv('MAIL_PORT') : 587;
    $smtp_encryption = getenv('MAIL_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS;
    $smtp_username   = getenv('MAIL_USER') ?: 'votre.email@gmail.com';
    $smtp_password   = getenv('MAIL_PASS') ?: 'votre-mot-de-passe-application';
    $from_email      = getenv('MAIL_FROM') ?: 'noreply@stagemaroc.ma';
    $from_name       = getenv('MAIL_FROM_NAME') ?: 'StageMaroc';

    // Mode debug : activer via SMTP_DEBUG=1 dans l'environnement (Clever Cloud) ou en local
    $debugMode = getenv('SMTP_DEBUG') ?: false;
    $mail->SMTPDebug = $debugMode ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;

    // --- Paramètres du serveur ---
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_username;
    $mail->Password   = $smtp_password;
    $mail->SMTPSecure = $smtp_encryption;
    $mail->Port       = $smtp_port;

    // --- Paramètres de l'e-mail ---
    $mail->setFrom($from_email, $from_name);
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->WordWrap = 78;

    return $mail;
}

/**
 * Envoie un e-mail contenant un code de vérification à 6 chiffres (mot de passe oublié).
 *
 * @param string $toEmail Destinataire
 * @param string $toName  Nom du destinataire
 * @param string $code    Code à 6 chiffres
 * @return bool           True si envoyé, sinon false
 */
function sendResetCodeEmail(string $toEmail, string $toName, string $code): bool
{
    try {
        $mail = getMailer();
        $mail->addAddress($toEmail, $toName);

        $mail->Subject = 'Réinitialisation de votre mot de passe StageMaroc';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e1e6e0;'>
                <div style='background: #0B6E4F; padding: 24px 32px; text-align: center;'>
                    <h1 style='color: #E3A73B; margin: 0; font-size: 24px;'>StageMaroc</h1>
                    <p style='color: #ffffff; margin: 4px 0 0; font-size: 13px; opacity: 0.85;'>Plateforme de stages · Maroc</p>
                </div>
                <div style='padding: 32px;'>
                    <h2 style='color: #12211D; margin: 0 0 16px; font-size: 20px;'>Bonjour $toName,</h2>
                    <p style='color: #5B6D67; line-height: 1.6; margin: 0 0 20px;'>
                        Vous avez demandé la réinitialisation de votre mot de passe. Veuillez utiliser le code suivant pour définir un nouveau mot de passe :
                    </p>
                    <div style='text-align: center; margin: 28px 0;'>
                        <span style='display: inline-block; background: #F5F6F2; color: #0B6E4F; font-size: 36px; font-weight: 700; letter-spacing: 8px; padding: 16px 32px; border-radius: 12px; border: 2px dashed #E3A73B; font-family: monospace;'>
                            $code
                        </span>
                    </div>
                    <p style='color: #5B6D67; font-size: 13px; line-height: 1.5; margin: 0 0 8px;'>
                        Ce code est valable <strong>15 minutes</strong>. Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet e-mail.
                    </p>
                    <p style='color: #C0392B; font-size: 13px; margin: 0;'>
                        ⚠️ Ne partagez jamais ce code avec personne.
                    </p>
                </div>
                <div style='background: #082E28; padding: 16px 32px; text-align: center;'>
                    <p style='color: rgba(255,255,255,0.6); font-size: 12px; margin: 0;'>© " . date('Y') . " StageMaroc — Tous droits réservés</p>
                </div>
            </div>
        ";
        $mail->AltBody = "Bonjour $toName,\n\nVous avez demandé la réinitialisation de votre mot de passe. Voici votre code de vérification :\n\n$code\n\nCe code est valable 15 minutes.\n\nSi vous n'avez pas demandé cette réinitialisation, ignorez cet e-mail.\n\nNe partagez jamais ce code avec personne.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erreur envoi e-mail code réinitialisation : ' . $mail->ErrorInfo);
        return false;
    }
}

