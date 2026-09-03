<?php

declare(strict_types=1);

namespace CaveTrip\Services;

final class SmtpMailer
{
    /** @param array<string,mixed> $settings */
    public function send(array $settings, string $password, string $to, string $subject, string $textBody): void
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Test recipient is not a valid email address.');
        $host = (string)$settings['smtp_host'];
        $port = (int)$settings['smtp_port'];
        $encryption = (string)$settings['smtp_encryption'];
        $transport = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
        $context = stream_context_create(['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'allow_self_signed'=>false]]);
        $socket = @stream_socket_client($transport.$host.':'.$port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
        if (!is_resource($socket)) throw new \RuntimeException("SMTP connection failed: {$errstr} ({$errno})");
        stream_set_timeout($socket, 20);
        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO ' . $this->hostname(), [250]);
            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new \RuntimeException('Unable to enable TLS for SMTP.');
                $this->command($socket, 'EHLO ' . $this->hostname(), [250]);
            }
            $username = (string)$settings['smtp_username'];
            if ($username !== '') {
                $this->command($socket, 'AUTH LOGIN', [334]);
                $this->command($socket, base64_encode($username), [334]);
                $this->command($socket, base64_encode($password), [235]);
            }
            $from = (string)$settings['from_email'];
            $this->command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $to . '>', [250,251]);
            $this->command($socket, 'DATA', [354]);
            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $this->headerText((string)$settings['from_name']) . ' <' . $from . '>',
                'To: <' . $to . '>',
                'Subject: ' . $this->headerText($subject),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $this->hostname() . '>',
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];
            if (!empty($settings['reply_to_email'])) $headers[] = 'Reply-To: <' . $settings['reply_to_email'] . '>';
            $body = str_replace(["\r\n","\r"], "\n", $textBody);
            $body = str_replace("\n.", "\n..", $body);
            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $body) . "\r\n.\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket @param int[] $expected */
    private function command($socket, string $command, array $expected): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->expect($socket, $expected);
    }

    /** @param resource $socket @param int[] $expected */
    private function expect($socket, array $expected): string
    {
        $response = '';
        while (($line = fgets($socket, 8192)) !== false) {
            $response .= $line;
            if (preg_match('/^(\d{3})([ -])/', $line, $m) && $m[2] === ' ') {
                $code = (int)$m[1];
                if (!in_array($code, $expected, true)) throw new \RuntimeException('SMTP server error: ' . trim($response));
                return $response;
            }
        }
        throw new \RuntimeException('SMTP server closed the connection unexpectedly.');
    }

    private function hostname(): string
    {
        $host = gethostname();
        return is_string($host) && $host !== '' ? preg_replace('/[^A-Za-z0-9.-]/', '', $host) ?: 'localhost' : 'localhost';
    }

    private function headerText(string $value): string
    {
        return str_replace(["\r","\n"], '', trim($value));
    }
}
