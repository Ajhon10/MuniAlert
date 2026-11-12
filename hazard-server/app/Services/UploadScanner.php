<?php

namespace App\Services;

class UploadScanner
{
    public function scan(string $bytes): string
    {
        if (strlen($bytes) > 1024 * 1024) {
            return 'unavailable';
        }
        try {
            $reply = trim($this->exchange($bytes), "\0\r\n");
        } catch (\Throwable) {
            return 'unavailable';
        }
        if ($reply === 'stream: OK') {
            return 'clean';
        }

        return preg_match('/^stream: .+ FOUND$/D', $reply) ? 'infected' : 'unavailable';
    }

    protected function exchange(string $bytes): string
    {
        $host = config('security.clamav_host');
        $port = (int) config('security.clamav_port');
        if (! is_string($host) || ! preg_match('/^[A-Za-z0-9.:-]+$/D', $host) || $port < 1 || $port > 65535) {
            throw new \RuntimeException('Invalid scanner endpoint.');
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $host = '['.$host.']';
        }
        $timeout = config('security.scanner_timeout');
        $deadline = microtime(true) + $timeout;
        $socket = @stream_socket_client('tcp://'.$host.':'.$port, $errno, $error, $timeout);
        if (! $socket) {
            throw new \RuntimeException('Scanner unavailable.');
        }
        stream_set_timeout($socket, $timeout);
        try {
            $write = function (string $part) use ($socket, $deadline) {
                while ($part !== '') {
                    if (microtime(true) > $deadline) {
                        throw new \RuntimeException('Scanner timed out.');
                    }
                    $sent = @fwrite($socket, $part);
                    if (! $sent) {
                        throw new \RuntimeException('Scanner write failed.');
                    }
                    $part = substr($part, $sent);
                }
            };
            $write("zINSTREAM\0");
            foreach (str_split($bytes, 16384) as $chunk) {
                $write(pack('N', strlen($chunk)).$chunk);
            }
            $write(pack('N', 0));
            $reply = '';
            while (strlen($reply) < 4096 && ! str_contains($reply, "\0")) {
                if (microtime(true) > $deadline) {
                    throw new \RuntimeException('Scanner timed out.');
                }
                $part = @fread($socket, 512);
                if ($part === false || $part === '') {
                    throw new \RuntimeException('Scanner response incomplete.');
                }
                $reply .= $part;
            }
            if (! str_ends_with($reply, "\0")) {
                throw new \RuntimeException('Scanner response invalid.');
            }

            return $reply;
        } finally {
            fclose($socket);
        }
    }
}
