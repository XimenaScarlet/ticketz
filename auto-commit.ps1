while ($true) {
    git add .
    $msg = "Auto commit: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
    git commit -m "$msg" --quiet
    git push origin main --quiet
    Start-Sleep -Seconds 60 # cada 2 minutos
}
