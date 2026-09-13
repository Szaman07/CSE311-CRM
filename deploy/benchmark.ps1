param([string]$BaseUrl = 'http://127.0.0.1:8080')
$ErrorActionPreference = 'Stop'
if (-not $env:NEXASTOCK_BENCH_PASSWORD) { throw 'Set NEXASTOCK_BENCH_PASSWORD in this process.' }

function New-BenchmarkSession {
    $page = Invoke-WebRequest -Uri "$BaseUrl/login" -SessionVariable session -UseBasicParsing
    if ($page.Content -notmatch '<meta name="csrf-token" content="([^"]+)"') { throw 'CSRF token missing.' }
    $headers = @{'X-CSRF-TOKEN'=$matches[1]; Accept='application/json'}
    $auth = Invoke-RestMethod -Uri "$BaseUrl/api/v1/login" -Method Post -WebSession $session -Headers $headers -ContentType 'application/json' -Body (@{email='benchmark@nexastock.local';password=$env:NEXASTOCK_BENCH_PASSWORD}|ConvertTo-Json)
    return @{Session=$session;Headers=@{Accept='application/json';'X-CSRF-TOKEN'=$auth.data.csrf_token}}
}

$warm = New-BenchmarkSession
1..10 | ForEach-Object { Invoke-WebRequest -Uri "$BaseUrl/api/v1/reports/inventory?per_page=100" -WebSession $warm.Session -Headers $warm.Headers -UseBasicParsing | Out-Null }
$jobs = 1..5 | ForEach-Object {
    Start-Job -ArgumentList $BaseUrl,$env:NEXASTOCK_BENCH_PASSWORD -ScriptBlock {
        param($url,$password)
        $page=Invoke-WebRequest -Uri "$url/login" -SessionVariable s -UseBasicParsing
        $page.Content -match '<meta name="csrf-token" content="([^"]+)"' | Out-Null
        $h=@{'X-CSRF-TOKEN'=$matches[1];Accept='application/json'}
        $auth=Invoke-RestMethod -Uri "$url/api/v1/login" -Method Post -WebSession $s -Headers $h -ContentType 'application/json' -Body (@{email='benchmark@nexastock.local';password=$password}|ConvertTo-Json)
        $h['X-CSRF-TOKEN']=$auth.data.csrf_token
        1..20 | ForEach-Object { $sw=[Diagnostics.Stopwatch]::StartNew(); try{$r=Invoke-WebRequest -Uri "$url/api/v1/reports/inventory?per_page=100" -WebSession $s -Headers $h -UseBasicParsing; $code=$r.StatusCode}catch{$code=$_.Exception.Response.StatusCode.value__}; $sw.Stop(); [pscustomobject]@{Milliseconds=$sw.Elapsed.TotalMilliseconds;Status=$code} }
    }
}
$rows=$jobs|Wait-Job|Receive-Job
$jobs|Remove-Job
$times=@($rows.Milliseconds|Sort-Object)
$p50=$times[[Math]::Ceiling($times.Count*.50)-1]
$p95=$times[[Math]::Ceiling($times.Count*.95)-1]
[pscustomobject]@{Requests=$times.Count;Clients=5;Warmups=10;P50ms=[Math]::Round($p50,2);P95ms=[Math]::Round($p95,2);Errors=@($rows|Where-Object Status -ge 500).Count;MeasuredAtUtc=[DateTime]::UtcNow.ToString('o')}
