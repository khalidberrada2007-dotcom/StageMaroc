<?php
/**
 * Configuration SMTP centralisée pour StageMaroc
 * 
 * Utilise PHPMailer pour l'envoi d'e-mails (code vérification, activation, etc.)
 * 
 * Configuration SMTP :
 * - Variables d'environnement (Clever Cloud) : MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS, MAIL_FROM, etc.
 * - Variables locales (XAMPP) : modifiez directement les valeurs par défaut ci-dessous.
 *   Pour Gmail : utilisez un mot de passe d'application (https://myaccount.google.com/apppasswords)
 * 
 * Hébergement courant :
 * - Gmail : host=smtp.gmail.com, port=587, SMTPSecure=tls
 * - Outlook : host=smtp.office365.com, port=587, SMTPSecure=tls
 * - Yandex : host=smtp.yandex.com, port=465, SMTPSecure=ssl
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
 * Construit l'URL de base du site de manière fiable.
 * Compatible avec les sous-dossiers (ex: /StageMaroc/) et la racine (Clever Cloud).
 *
 * @return string URL de base (ex: "https://stagemaroc.cleverapps.io" ou "http://localhost/StageMaroc")
 */
function getBaseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);

    // Si on est à la racine, scriptDir = "/"
    // Si on est dans un sous-dossier, scriptDir = "/StageMaroc"
    // On nettoie les "/.." parasites
    $baseUrl = $protocol . '://' . $host;
    if ($scriptDir !== '/' && $scriptDir !== '\\') {
        $baseUrl .= rtrim($scriptDir, '/\\');
    }

    return $baseUrl;
}

/**
 * Envoie un e-mail de vérification d'adresse e-mail (lien d'activation).
 *
 * @param string $toEmail Destinataire
 * @param string $toName  Nom du destinataire
 * @param string $token   Token de vérification unique
 * @return bool           True si envoyé, sinon false
 */
function sendVerificationEmail(string $toEmail, string $toName, string $token): bool
{
    try {
        $mail = getMailer();
        $mail->addAddress($toEmail, $toName);

        // Lien d'activation construit proprement
        $baseUrl = getBaseUrl();
        $activationLink = $baseUrl . '/verification.php?token=' . urlencode($token);

        $mail->Subject = 'Activez votre compte StageMaroc';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e1e6e0;'>
                <div style='background: #0B6E4F; padding: 24px 32px; text-align: center;'>
                    <h1 style='color: #E3A73B; margin: 0; font-size: 24px;'>StageMaroc</h1>
                    <p style='color: #ffffff; margin: 4px 0 0; font-size: 13px; opacity: 0.85;'>Plateforme de stages · Maroc</p>
                </div>
                <div style='padding: 32px;'>
                    <h2 style='color: #12211D; margin: 0 0 16px; font-size: 20px;'>Bonjour $toName,</h2>
                    <p style='color: #5B6D67; line-height: 1.6; margin: 0 0 20px;'>
                        Merci de vous être inscrit sur <strong>StageMaroc</strong>. Pour activer votre compte et commencer à utiliser la plateforme, veuillez cliquer sur le lien ci-dessous :
                    </p>
                    <div style='text-align: center; margin: 28px 0;'>
                        <a href='$activationLink' style='display: inline-block; background: #0B6E4F; color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-size: 16px; font-weight: 600;'>
                            Activer mon compte
                        </a>
                    </div>
                    <p style='color: #5B6D67; font-size: 13px; line-height: 1.5; margin: 0 0 8px;'>
                        Ce lien est valable <strong>24 heures</strong>. Si vous n'avez pas créé de compte sur StageMaroc, ignorez simplement cet e-mail.
                    </p>
                    <p style='color: #5B6D67; font-size: 13px; line-height: 1.5; margin: 0;'>
                        Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
                        <span style='color: #0B6E4F; word-break: break-all;'>$activationLink</span>
                    </p>
                </div>
                <div style='background: #082E28; padding: 16px 32px; text-align: center;'>
                    <p style='color: rgba(255,255,255,0.6); font-size: 12px; margin: 0;'>© " . date('Y') . " StageMaroc — Tous droits réservés</p>
                </div>
            </div>
        ";
        $mail->AltBody = "Bonjour $toName,\n\nMerci de vous être inscrit sur StageMaroc. Pour activer votre compte, copiez et collez ce lien dans votre navigateur :\n\n$activationLink\n\nCe lien est valable 24 heures.\n\nSi vous n'avez pas créé de compte, ignorez cet e-mail.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erreur envoi e-mail vérification : ' . $mail->ErrorInfo);
        return false;
    }
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

