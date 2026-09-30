<?php

declare(strict_types=1);

namespace CaveTrip\Services;

final class SmtpMailer
{
    /** @param array<string,mixed> $settings @return array<string,string|int> */
    public function testConnection(array $settings, string $password): array
    {
        $host = trim((string)($settings['smtp_host'] ?? ''));
        $port = (int)($settings['smtp_port'] ?? 0);
        $encryption = (string)($settings['smtp_encryption'] ?? 'tls');
        $username = trim((string)($settings['smtp_username'] ?? ''));
        if ($host === '' || $port < 1) throw new \RuntimeException('SMTP host and port are required.');

        $transport = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
        $context = stream_context_create(['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'allow_self_signed'=>false]]);
        $started = microtime(true);
        $socket = @stream_socket_client($transport.$host.':'.$port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
        if (!is_resource($socket)) throw new \RuntimeException("SMTP connection failed to {$host}:{$port}: {$errstr} ({$errno})");
        stream_set_timeout($socket, 20);
        try {
            $banner = $this->expect($socket, [220]);
            $ehlo = $this->command($socket, 'EHLO ' . $this->hostname(), [250]);
            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new \RuntimeException('Connected, but STARTTLS negotiation failed.');
                $ehlo = $this->command($socket, 'EHLO ' . $this->hostname(), [250]);
            }
            if ($username !== '') {
                $this->command($socket, 'AUTH LOGIN', [334]);
                $this->command($socket, base64_encode($username), [334]);
                $this->command($socket, base64_encode($password), [235]);
            }
            try { $this->command($socket, 'QUIT', [221]); } catch (\Throwable) {}
            return [
                'host'=>$host, 'port'=>$port, 'encryption'=>$encryption,
                'authenticated'=>$username !== '' ? 'yes' : 'not requested',
                'elapsed_ms'=>(int)round((microtime(true)-$started)*1000),
                'banner'=>trim(strtok($banner, "\r\n") ?: ''),
                'capabilities'=>trim($ehlo),
            ];
        } finally { fclose($socket); }
    }

    /** @param array<string,mixed> $settings */
    public function send(array $settings, string $password, string $to, string $subject, string $textBody): array
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
            $mailFromResponse = $this->command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            $rcptToResponse = $this->command($socket, 'RCPT TO:<' . $to . '>', [250,251]);
            $dataResponse = $this->command($socket, 'DATA', [354]);
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
            // SMTP dot-stuff every body line that begins with a period, including the first line.
            $bodyLines = explode("\n", $body);
            foreach ($bodyLines as &$line) {
                if (str_starts_with($line, '.')) $line = '.' . $line;
            }
            unset($line);
            $payload = implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $bodyLines) . "\r\n.\r\n";
            $this->writeAll($socket, $payload);
            $acceptedResponse = $this->expect($socket, [250]);
            try { $this->command($socket, 'QUIT', [221]); } catch (\Throwable) {}

            $messageId = '';
            if (preg_match('/^250[ -](?:Ok\s+)?<?([^<>\s]+)>?/i', trim($acceptedResponse), $m)) {
                $candidate = trim($m[1]);
                if (strcasecmp($candidate, 'Ok') !== 0) $messageId = $candidate;
            }
            return [
                'host' => $host,
                'port' => $port,
                'from' => $from,
                'to' => $to,
                'mail_from_response' => trim($mailFromResponse),
                'rcpt_to_response' => trim($rcptToResponse),
                'data_response' => trim($dataResponse),
                'accepted_response' => trim($acceptedResponse),
                'message_id' => $messageId,
            ];
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private function writeAll($socket, string $data): void
    {
        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $n = fwrite($socket, substr($data, $written));
            if ($n === false || $n === 0) {
                $meta = stream_get_meta_data($socket);
                $reason = !empty($meta['timed_out']) ? ' (socket timed out)' : '';
                throw new \RuntimeException('SMTP connection failed while transmitting message data' . $reason . '.');
            }
            $written += $n;
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
