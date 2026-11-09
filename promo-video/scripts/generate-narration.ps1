$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Speech
$audioDirectory = Join-Path (Split-Path -Parent $PSScriptRoot) 'public/audio'
New-Item -ItemType Directory -Path $audioDirectory -Force | Out-Null
$promoVoice = New-Object System.Speech.Synthesis.SpeechSynthesizer
try {
    $promoVoice.SelectVoice('Microsoft Zira Desktop')
    $promoVoice.Rate = 1
    $promoVoice.Volume = 100
    $narration = [ordered]@{
        '01-intro' = 'Meet Muni Alert. A clearer way to report municipal emergencies and public hazards.'
        '02-reporting' = 'Choose a category, add safe photos, and describe what happened. Use GPS or a manual pin, then confirm the incident location.'
        '03-privacy' = 'Original photos, exact locations, and sensitive details stay private. Public updates use a reviewed summary.'
        '04-tracking' = 'Municipal teams review reports, verify locations and record action taken. Citizens can follow status updates and resolution photos.'
        '05-records' = 'Group records by week or month, then filter and export. Counts describe reports, not confirmed casualties.'
        '06-guide' = 'New to the system? Start the highlighted feature tour, or search the built in user manual. Clear steps for citizens and administrators.'
        '07-closing' = 'Muni Alert. For immediate danger, call emergency services first.'
    }
    foreach ($entry in $narration.GetEnumerator()) {
        $promoVoice.SetOutputToWaveFile((Join-Path $audioDirectory ($entry.Key + '.wav')))
        $promoVoice.Speak($entry.Value)
        $promoVoice.SetOutputToNull()
    }
    $narration | ConvertTo-Json | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $audioDirectory 'narration-script.json')
    Write-Output 'Generated seven local narration clips using the installed Windows voice.'
} finally {
    $promoVoice.Dispose()
}
