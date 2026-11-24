<?php

require __DIR__.'/lib/nginx-security.php';

function nginxCommand(array $arguments): string
{
    $process = proc_open($arguments, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($process)) {
        throw new RuntimeException('Could not start an NGINX verification command.');
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $result = '';
    $deadline = microtime(true) + 15;
    do {
        $result .= stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        $status = proc_get_status($process);
        if (strlen($result) > 1024 * 1024 || microtime(true) > $deadline) {
            proc_terminate($process, 9);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            throw new RuntimeException('NGINX verification exceeded its output or time limit.');
        }
        if ($status['running']) {
            usleep(10000);
        }
    } while ($status['running']);
    $result .= stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
    if ($status['exitcode'] !== 0) {
        // Command output may include configuration details. Do not echo it.
        throw new RuntimeException('NGINX verification command failed; inspect the host privately.');
    }

    return trim($result);
}

try {
    $arguments = array_slice($argv, 1);
    if (count($arguments) === 2 && $arguments[0] === '--version') {
        $fixed = projectNginxVersionIsFixed($arguments[1]);
        echo json_encode(['upstream_fixed' => $fixed], JSON_THROW_ON_ERROR).PHP_EOL;
        exit($fixed ? 0 : 1);
    }
    if ($arguments !== [] && $arguments !== ['--installed-only']) {
        throw new RuntimeException('Usage: php scripts/check-nginx-security.php [--installed-only | --version "nginx version: nginx/x.y.z"]');
    }
    if (PHP_OS_FAMILY !== 'Linux') {
        throw new RuntimeException('Host verification requires the Linux NGINX server.');
    }
    $binary = '/usr/sbin/nginx';
    if (! is_executable($binary)) {
        throw new RuntimeException('Expected packaged NGINX at /usr/sbin/nginx; custom installations need operator review.');
    }
    $version = nginxCommand([$binary, '-v']);
    $hash = hash_file('sha256', $binary);
    if ($hash === false) {
        throw new RuntimeException('Cannot hash the installed NGINX executable.');
    }
    $fixed = projectNginxVersionIsFixed($version);
    $source = 'upstream';
    if (! $fixed) {
        $evidencePath = '/etc/bantay/nginx-security.json';
        if (! is_file($evidencePath) || is_link($evidencePath)
            || fileowner($evidencePath) !== 0 || (fileperms($evidencePath) & 0022) !== 0
            || filesize($evidencePath) > 16384) {
            throw new RuntimeException('NGINX is not a verified fixed upstream release. Update it, or provide root-owned vendor backport evidence for all five CVEs. See documentation/07-deployment-and-maintenance.md (NGINX security gate).');
        }
        $packageVersion = nginxCommand(['/usr/bin/dpkg-query', '-W', '-f=${Version}', 'nginx']);
        $evidence = json_decode(file_get_contents($evidencePath), true, 16, JSON_THROW_ON_ERROR);
        if (! is_array($evidence) || ! projectNginxBackportIsFixed($evidence, $hash, $packageVersion)) {
            throw new RuntimeException('Vendor backport evidence does not match this binary/package or lacks required vendor advisories.');
        }
        $source = 'operator-verified-vendor-backport';
    }
    // Test syntax without disclosing the full configuration or certificate paths.
    nginxCommand([$binary, '-t']);
    $checkedProcesses = 0;
    if ($arguments !== ['--installed-only']) {
        $masterPid = nginxCommand(['/usr/bin/systemctl', 'show', '--property=MainPID', '--value', 'nginx.service']);
        if (! ctype_digit($masterPid) || (int) $masterPid < 1
            || trim((string) @file_get_contents('/proc/'.$masterPid.'/comm')) !== 'nginx') {
            throw new RuntimeException('The NGINX service has no verifiable running master process.');
        }
        foreach (glob('/proc/[0-9]*/comm') ?: [] as $commPath) {
            if (trim((string) @file_get_contents($commPath)) !== 'nginx') {
                continue;
            }
            $runningHash = @hash_file('sha256', dirname($commPath).'/exe');
            if ($runningHash === false || ! hash_equals($hash, $runningHash)) {
                throw new RuntimeException('An NGINX process still uses a different or unreadable executable. Restart the service and recheck as root.');
            }
            $checkedProcesses++;
        }
        if ($checkedProcesses < 1) {
            throw new RuntimeException('No running NGINX executable could be verified.');
        }
    }
    echo json_encode([
        'nginx2root_fixed' => true,
        'evidence' => $source,
        'binary_sha256' => $hash,
        'configuration_valid' => true,
        'running_processes_verified' => $checkedProcesses,
        'running_binary_verified' => $checkedProcesses > 0,
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $error) {
    // JSON parser and operating-system errors may contain sensitive details.
    $message = get_class($error) === RuntimeException::class
        ? $error->getMessage() : 'NGINX security verification failed; inspect the host privately.';
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}
