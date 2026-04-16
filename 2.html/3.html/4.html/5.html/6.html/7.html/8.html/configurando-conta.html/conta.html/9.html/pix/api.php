<?php
// api.php
header('Content-Type: application/json');

// --- CONFIGURAÇÃO DE LOG ---
function salvarLog($titulo, $dados) {
    $arquivo = 'log_api.txt';
    $dataHora = date('d/m/Y H:i:s');
    $mensagem = "[$dataHora] --- $titulo ---\n";
    $mensagem .= is_array($dados) ? json_encode($dados, JSON_PRETTY_PRINT) : $dados;
    $mensagem .= "\n\n";
    file_put_contents($arquivo, $mensagem, FILE_APPEND);
}

$apiKey = "sk_08757193106659fa5abf2e5a3cc5c6bb5e241e60a1a7556df01a02004180e202";
$baseUrl = "https://multi.paradisepags.com/api/v1";

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Salvar o que o seu formulário está enviando
    salvarLog("DADOS RECEBIDOS DO JS", $input);

    $data = [
        "amount" => (int)$input['amount'],
        "description" => $input['description'],
        "reference" => "REF_" . uniqid() . "_STEP_" . ($input['step'] ?? '1'),
        "source" => "api_externa",
        "customer" => [
            "name" => $input['name'] ?: "Cliente Sicoob",
            "email" => (!empty($input['email']) && $input['email'] !== 'null') ? $input['email'] : "cliente@email.com",
            "phone" => preg_replace('/\D/', '', $input['phone'] ?: "11999999999"),
            "document" => preg_replace('/\D/', '', $input['document'] ?: "00000000000")
        ]
    ];

    salvarLog("PAYLOAD ENVIADO PARA PARADISE", $data);

    $ch = curl_init("$baseUrl/transaction.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-Key: $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if(curl_errno($ch)) {
        salvarLog("ERRO CURL", curl_error($ch));
    }

    curl_close($ch);

    salvarLog("RESPOSTA DA API PARADISE (HTTP $httpCode)", $response);
    
    echo $response;

} elseif ($action === 'check') {
    $id = $_GET['id'];
    $ch = curl_init("$baseUrl/query.php?action=get_transaction&id=$id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: $apiKey"]);
    $response = curl_exec($ch);
    curl_close($ch);
    echo $response;
}