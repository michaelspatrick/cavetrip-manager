<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use CaveTrip\Core\Application;
use PDO;

final class EmailSettingsService
{
    public function __construct(private readonly Application $app) {}

    /** @return array<string,mixed>|null */
    public function findForGrotto(int $grottoId): ?array
    {
        $stmt = $this->app->db()->prepare('SELECT * FROM email_settings WHERE grotto_id=:grotto_id LIMIT 1');
        $stmt->execute(['grotto_id'=>$grottoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @param array<string,mixed> $data */
    public function save(int $grottoId, array $data): void
    {
        $provider = (string)($data['provider'] ?? 'smtp');
        if (!in_array($provider, ['smtp','ses'], true)) throw new \InvalidArgumentException('Invalid email provider.');
        $fromEmail = strtolower(trim((string)($data['from_email'] ?? '')));
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('A valid From email address is required.');
        $fromName = trim((string)($data['from_name'] ?? ''));
        if ($fromName === '') throw new \InvalidArgumentException('From name is required.');
        $replyTo = strtolower(trim((string)($data['reply_to_email'] ?? '')));
        if ($replyTo !== '' && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Reply-To must be a valid email address.');

        $existing = $this->findForGrotto($grottoId);
        $passwordEncrypted = (string)($existing['smtp_password_encrypted'] ?? '');
        $newPassword = (string)($data['smtp_password'] ?? '');
        if ($newPassword !== '') $passwordEncrypted = (new SecretService($this->app))->encrypt($newPassword);

        $region = trim((string)($data['ses_region'] ?? 'us-east-1')) ?: 'us-east-1';
        $host = $provider === 'ses' ? 'email-smtp.' . $region . '.amazonaws.com' : trim((string)($data['smtp_host'] ?? ''));
        $port = (int)($data['smtp_port'] ?? 587);
        if ($port < 1 || $port > 65535) throw new \InvalidArgumentException('SMTP port must be between 1 and 65535.');
        $encryption = (string)($data['smtp_encryption'] ?? 'tls');
        if (!in_array($encryption, ['none','tls','ssl'], true)) $encryption = 'tls';
        $username = trim((string)($data['smtp_username'] ?? ''));
        if ($host === '') throw new \InvalidArgumentException('SMTP host is required.');
        if ($username === '') throw new \InvalidArgumentException('SMTP username is required.');
        if ($passwordEncrypted === '') throw new \InvalidArgumentException('SMTP password is required.');

        $sql = "INSERT INTO email_settings
            (grotto_id,provider,from_name,from_email,reply_to_email,smtp_host,smtp_port,smtp_encryption,smtp_username,smtp_password_encrypted,ses_region,created_at,updated_at)
            VALUES (:grotto_id,:provider,:from_name,:from_email,:reply_to_email,:smtp_host,:smtp_port,:smtp_encryption,:smtp_username,:smtp_password_encrypted,:ses_region,NOW(),NOW())
            ON DUPLICATE KEY UPDATE provider=VALUES(provider),from_name=VALUES(from_name),from_email=VALUES(from_email),reply_to_email=VALUES(reply_to_email),smtp_host=VALUES(smtp_host),smtp_port=VALUES(smtp_port),smtp_encryption=VALUES(smtp_encryption),smtp_username=VALUES(smtp_username),smtp_password_encrypted=VALUES(smtp_password_encrypted),ses_region=VALUES(ses_region),updated_at=NOW()";
        $stmt = $this->app->db()->prepare($sql);
        $stmt->execute([
            'grotto_id'=>$grottoId,'provider'=>$provider,'from_name'=>$fromName,'from_email'=>$fromEmail,
            'reply_to_email'=>$replyTo !== '' ? $replyTo : null,'smtp_host'=>$host,'smtp_port'=>$port,
            'smtp_encryption'=>$encryption,'smtp_username'=>$username,'smtp_password_encrypted'=>$passwordEncrypted,
            'ses_region'=>$provider === 'ses' ? $region : null,
        ]);
    }

    public function password(array $settings): string
    {
        return (new SecretService($this->app))->decrypt((string)($settings['smtp_password_encrypted'] ?? ''));
    }
}
