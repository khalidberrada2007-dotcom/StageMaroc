<?php
/**
 * Configuration SMTP centralisée pour StageMaroc
 * 
 * Utilise PHPMailer pour l'envoi d'e-mails (code vérification, activation, etc.)
 * 
 * IMPORTANT : Remplacez les valeurs ci-dessous par vos identifiants SMTP réels.
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
 *
 * @return PHPMailer
 */
function getMailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    // --- Configuration SMTP (à modifier) ---
    $smtp_host       = 'smtp.gmail.com';        // Ex: smtp.gmail.com
    $smtp_port       = 587;                      // 587 (TLS) ou 465 (SSL)
    $smtp_encryption = PHPMailer::ENCRYPTION_STARTTLS; // 'tls' ou 'ssl'
    $smtp_username   = 'votre.email@gmail.com';  // Adresse e-mail complète
    $smtp_password   = 'votre-mot-de-passe-application'; // Mot de passe d'application
    $from_email      = 'noreply@stagemaroc.ma';  // Adresse d'envoi
    $from_name       = 'StageMaroc';              // Nom d'affichage

    // --- Paramètres du serveur ---
    $mail->SMTPDebug  = SMTP::DEBUG_OFF;          // Mettre DEBUG_SERVER pour déboguer
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

        // Lien d'activation
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host . dirname($_SERVER['SCRIPT_NAME']) . '/..';
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

