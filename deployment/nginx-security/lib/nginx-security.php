<?php

// Standalone: deployment preflight must not boot Laravel or read .env.
function projectNginxVersionIsFixed(string $versionOutput): bool
{
    if (! preg_match('/^nginx version: nginx\/(\d+\.\d+\.\d+)(?:\s|$)/D', trim($versionOutput), $match)) {
        return false;
    }

    $version = $match[1];

    return version_compare($version, '1.31.9', '>=')
        || (version_compare($version, '1.30.7', '>=') && version_compare($version, '1.31.0', '<'));
}

function projectNginxBackportIsFixed(array $evidence, string $binaryHash, string $packageVersion): bool
{
    if (! preg_match('/^[a-f0-9]{64}$/D', $binaryHash)
        || ! hash_equals($binaryHash, (string) ($evidence['binary_sha256'] ?? ''))
        || $packageVersion === '' || ($evidence['package_version'] ?? null) !== $packageVersion) {
        return false;
    }

    // An operator must verify vendor notices for ALL five flaws, then bind
    // that decision to the exact package and binary. This is not an override
    // based solely on a version number, changelog keyword or disabled HTTP/2.
    foreach (['CVE-2026-123007', 'CVE-2026-123633', 'CVE-2026-123634', 'CVE-2026-121810', 'CVE-2026-113425'] as $cve) {
        $url = $evidence['advisories'][$cve] ?? null;
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https'
            || ! in_array($parts['host'] ?? '', ['ubuntu.com', 'security-tracker.debian.org', 'nginx.org', 'my.f5.com'], true)
            || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
    }

    return true;
}
