<?php
/**
 * Lightweight, dependency-free SMTP mailer (implicit SSL / STARTTLS).
 * Credentials are read from the `settings` table (entered via the admin
 * panel) — never hard-coded, so nothing sensitive lives in the repo.
 */

class SmtpMailer {
    private $host, $port, $user, $pass, $secure, $fromEmail, $fromName;
    private $conn;
    private $timeout = 20;
    private $lastResponse = '';
    public $error = '';

    public function __construct(array $cfg) {
        $this->host      = $cfg['host'];
        $this->port      = (int) $cfg['port'];
        $this->user      = $cfg['user'];
        $this->pass      = $cfg['pass'];
        $this->secure    = $cfg['secure']; // 'ssl' | 'tls' | ''
        $this->fromEmail = $cfg['from_email'];
        $this->fromName  = $cfg['from_name'];
    }

    public function send($toEmail, $toName, $subject, $htmlBody) {
        try {
            if (!$this->connect()) return false;

            if (!$this->cmd('EHLO ' . $this->ehloName(), 250)) {
                $this->cmd('HELO ' . $this->ehloName(), 250);
            }

            if ($this->secure === 'tls') {
                if (!$this->cmd('STARTTLS', 220)) return $this->fail('STARTTLS failed');
                if (!stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    return $this->fail('TLS negotiation failed');
                }
                $this->cmd('EHLO ' . $this->ehloName(), 250);
            }

            if (!$this->cmd('AUTH LOGIN', 334))              return $this->fail('AUTH not accepted');
            if (!$this->cmd(base64_encode($this->user), 334)) return $this->fail('username rejected');
            if (!$this->cmd(base64_encode($this->pass), 235)) return $this->fail('authentication failed');

            if (!$this->cmd('MAIL FROM:<' . $this->fromEmail . '>', 250)) return $this->fail('MAIL FROM rejected');
            if (!$this->cmd('RCPT TO:<' . $toEmail . '>', 250))          return $this->fail('recipient rejected');
            if (!$this->cmd('DATA', 354))                                 return $this->fail('DATA rejected');

            $this->write($this->buildMessage($toEmail, $toName, $subject, $htmlBody) . "\r\n.");
            if (!$this->expect(250)) return $this->fail('message not accepted');

            $this->cmd('QUIT', 221);
            if (is_resource($this->conn)) fclose($this->conn);
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    private function connect() {
        $ctx = stream_context_create(['ssl' => [
            'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true,
        ]]);
        $prefix = ($this->secure === 'ssl') ? 'ssl://' : '';
        $this->conn = @stream_socket_client(
            $prefix . $this->host . ':' . $this->port,
            $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $ctx
        );
        if (!$this->conn) { $this->error = "Connection failed: $errstr ($errno)"; return false; }
        stream_set_timeout($this->conn, $this->timeout);
        return $this->expect(220);
    }

    private function ehloName() {
        $h = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return preg_replace('/[^a-zA-Z0-9.\-]/', '', $h) ?: 'localhost';
    }

    private function write($data) {
        fwrite($this->conn, $data . "\r\n");
    }

    private function readResponse() {
        $data = '';
        while (is_resource($this->conn) && ($line = fgets($this->conn, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break; // last line of a multiline reply
            $meta = stream_get_meta_data($this->conn);
            if (!empty($meta['timed_out'])) break;
        }
        $this->lastResponse = $data;
        return $data;
    }

    private function expect($code) {
        $resp = $this->readResponse();
        return (int) substr($resp, 0, 3) === (int) $code;
    }

    private function cmd($command, $expectCode) {
        $this->write($command);
        return $this->expect($expectCode);
    }

    private function fail($reason) {
        $this->error = $reason . ' | ' . trim($this->lastResponse);
        if (is_resource($this->conn)) @fclose($this->conn);
        return false;
    }

    private function buildMessage($toEmail, $toName, $subject, $html) {
        $domain = substr(strrchr($this->fromEmail, '@'), 1) ?: 'localhost';
        $enc = fn($t) => '=?UTF-8?B?' . base64_encode($t) . '?=';
        $toDisp = $toName ? $enc($toName) . ' ' : '';

        $headers = [
            'Date: ' . date('r'),
            'From: ' . $enc($this->fromName) . ' <' . $this->fromEmail . '>',
            'To: ' . $toDisp . '<' . $toEmail . '>',
            'Subject: ' . $enc($subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        return implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($html));
    }
}

/** Build the SMTP config from settings, or null if not fully configured. */
function getMailerConfig() {
    $host = trim(getSetting('smtp_host', ''));
    $user = trim(getSetting('smtp_user', ''));
    $pass = (string) getSetting('smtp_pass', '');
    if ($host === '' || $user === '' || $pass === '') return null;

    return [
        'host'       => $host,
        'port'       => (int) getSetting('smtp_port', 465),
        'secure'     => getSetting('smtp_secure', 'ssl'),
        'user'       => $user,
        'pass'       => $pass,
        'from_email' => trim(getSetting('smtp_from_email', '')) ?: $user,
        'from_name'  => getSetting('smtp_from_name', 'UAE.GIFT'),
    ];
}

/** True when email sending is switched on and configured. */
function mailerReady() {
    return getSetting('smtp_enabled', '0') === '1' && getMailerConfig() !== null;
}

/**
 * Send an HTML email. Returns ['ok' => bool, 'error' => string].
 * Never throws; safe to call inline from admin actions.
 */
function sendSystemMail($toEmail, $toName, $subject, $html) {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'invalid recipient address'];
    }
    if (getSetting('smtp_enabled', '0') !== '1') {
        return ['ok' => false, 'error' => 'email sending is disabled'];
    }
    $cfg = getMailerConfig();
    if (!$cfg) {
        return ['ok' => false, 'error' => 'email is not configured'];
    }
    $mailer = new SmtpMailer($cfg);
    $ok = $mailer->send($toEmail, $toName, $subject, $html);
    return ['ok' => $ok, 'error' => $ok ? '' : $mailer->error];
}

/** Wrap body content in a branded, responsive HTML email shell. */
function buildBrandedEmail($lang, $heading, $bodyHtml) {
    $dir   = ($lang === 'ar') ? 'rtl' : 'ltr';
    $align = ($lang === 'ar') ? 'right' : 'left';
    $site  = defined('SITE_NAME') ? SITE_NAME : 'UAE.GIFT';
    return '<!DOCTYPE html><html dir="' . $dir . '"><head><meta charset="UTF-8"></head>'
        . '<body style="margin:0;background:#f1f5f9;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;font-family:Arial,Helvetica,sans-serif;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">'
        . '<tr><td style="background:#2563EB;padding:22px 32px;color:#ffffff;font-size:20px;font-weight:bold;letter-spacing:.5px;">UAE.GIFT</td></tr>'
        . '<tr><td style="padding:32px;text-align:' . $align . ';color:#334155;font-size:15px;line-height:1.75;">'
        . '<h2 style="margin:0 0 16px;color:#0f172a;font-size:18px;">' . htmlspecialchars($heading) . '</h2>'
        . $bodyHtml
        . '</td></tr>'
        . '<tr><td style="padding:18px 32px;background:#f8fafc;color:#94a3b8;font-size:12px;text-align:' . $align . ';border-top:1px solid #e2e8f0;">'
        . htmlspecialchars($site) . ' &middot; uae.gift</td></tr>'
        . '</table></td></tr></table></body></html>';
}
