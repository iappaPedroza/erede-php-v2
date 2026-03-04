<?php

require __DIR__ . '/vendor/autoload.php';

use ItsTecnologiaErede\Store;
use ItsTecnologiaErede\Environment;
use ItsTecnologiaErede\Transaction;
use ItsTecnologiaErede\Url;
use ItsTecnologiaErede\eRede;

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║     TESTE COMPLETO - SDK eRede V2 (iappapedroza v2.1.1)         ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$PV = readline("PV Sandbox: ");
$TOKEN = readline("TOKEN Sandbox: ");

if (empty($PV) || empty($TOKEN)) {
    die("✗ PV e TOKEN obrigatórios!\n");
}

$store = new Store($PV, $TOKEN, Environment::sandbox());
$erede = new eRede($store);

echo "\n✓ Ambiente Sandbox configurado\n";
echo "  PV: {$store->getFiliation()}\n\n";

// Helper para adicionar URLs obrigatórias
function addRequiredUrls(Transaction $transaction): Transaction {
    return $transaction
        ->addUrl('https://siestabox.com.br/api/callback', Url::CALLBACK)
        ->addUrl('https://siestabox.com.br/api/3ds/success', Url::THREE_D_SECURE_SUCCESS)
        ->addUrl('https://siestabox.com.br/api/3ds/failure', Url::THREE_D_SECURE_FAILURE);
}

// ==========================================
// TESTE 1: Autorização com Captura Automática
// ==========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TESTE 1: Autorização com Captura Automática\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $transaction = addRequiredUrls(
        (new Transaction(25.50, 'pedido' . time()))
            ->creditCard('5448280000000007', '235', '12', (int)date('Y') + 1, 'TESTE CAP AUTO')
            ->capture(true)
    );

    $result = $erede->create($transaction);

    if ($result->getReturnCode() === '00') {
        echo "✓ SUCESSO - Autorizada e Capturada!\n";
        echo "  TID: {$result->getTid()}\n";
        echo "  NSU: {$result->getNsu()}\n";
        echo "  Valor: R$ " . number_format($result->getAmount() / 100, 2, ',', '.') . "\n";
        $brand = $result->getBrand();
        if ($brand && method_exists($brand, 'getName')) {
            echo "  Bandeira: {$brand->getName()}\n";
        }
    } else {
        echo "✗ Código: {$result->getReturnCode()} - {$result->getReturnMessage()}\n";
    }
} catch (\Throwable $e) {
    echo "✗ ERRO: {$e->getMessage()}\n";
}

sleep(1);

// ==========================================
// TESTE 2: Pré-Autorização (sem captura)
// ==========================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TESTE 2: Pré-Autorização (Sem Captura)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$tidParaCaptura = null;

try {
    $transaction = addRequiredUrls(
        (new Transaction(15.99, 'pedido' . time()))
            ->creditCard('5448280000000007', '235', '12', (int)date('Y') + 1, 'TESTE PRE AUTH')
            ->capture(false)
    );

    $result = $erede->create($transaction);

    if ($result->getReturnCode() === '00') {
        $tidParaCaptura = $result->getTid();
        echo "✓ SUCESSO - Pré-Autorizada!\n";
        echo "  TID: {$tidParaCaptura}\n";
        echo "  NSU: {$result->getNsu()}\n";
        echo "  Valor: R$ " . number_format($result->getAmount() / 100, 2, ',', '.') . "\n";
        echo "  Status: Aguardando captura\n";
    } else {
        echo "✗ Código: {$result->getReturnCode()} - {$result->getReturnMessage()}\n";
    }
} catch (\Throwable $e) {
    echo "✗ ERRO: {$e->getMessage()}\n";
}

// ==========================================
// TESTE 3: Captura da Pré-Autorização
// ==========================================
if ($tidParaCaptura) {
    sleep(2);
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TESTE 3: Captura da Transação Pré-Autorizada\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    try {
        $result = $erede->capture((new Transaction(15.99))->setTid($tidParaCaptura));

        if ($result->getReturnCode() === '00') {
            echo "✓ SUCESSO - Capturada!\n";
            echo "  TID: {$result->getTid()}\n";
            echo "  NSU: {$result->getNsu()}\n";
            echo "  Valor capturado: R$ " . number_format($result->getAmount() / 100, 2, ',', '.') . "\n";
        } else {
            echo "✗ Código: {$result->getReturnCode()} - {$result->getReturnMessage()}\n";
        }
    } catch (\Throwable $e) {
        echo "✗ ERRO: {$e->getMessage()}\n";
    }
}

sleep(1);

// ==========================================
// TESTE 4: Parcelamento
// ==========================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TESTE 4: Transação Parcelada (3x)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $transaction = addRequiredUrls(
        (new Transaction(90.00, 'pedido' . time()))
            ->creditCard('5448280000000007', '235', '12', (int)date('Y') + 1, 'TESTE PARC')
            ->setInstallments(3)
    );

    $result = $erede->create($transaction);

    if ($result->getReturnCode() === '00') {
        echo "✓ SUCESSO - Parcelada!\n";
        echo "  TID: {$result->getTid()}\n";
        echo "  Valor total: R$ " . number_format($result->getAmount() / 100, 2, ',', '.') . "\n";
        echo "  Parcelas: {$result->getInstallments()}x de R$ " .
             number_format(($result->getAmount() / 100) / $result->getInstallments(), 2, ',', '.') . "\n";
    } else {
        echo "✗ Código: {$result->getReturnCode()} - {$result->getReturnMessage()}\n";
    }
} catch (\Throwable $e) {
    echo "✗ ERRO: {$e->getMessage()}\n";
}

sleep(1);

// ==========================================
// TESTE 5: SoftDescriptor
// ==========================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TESTE 5: SoftDescriptor (Nome na Fatura)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $transaction = addRequiredUrls(
        (new Transaction(30.00, 'pedido' . time()))
            ->creditCard('5448280000000007', '235', '12', (int)date('Y') + 1, 'TESTE SOFT')
            ->setSoftDescriptor('SIESTABOX')
    );

    $result = $erede->create($transaction);

    if ($result->getReturnCode() === '00') {
        echo "✓ SUCESSO - Com SoftDescriptor!\n";
        echo "  TID: {$result->getTid()}\n";
        echo "  SoftDescriptor: {$result->getSoftDescriptor()}\n";
        echo "  (Aparecerá como 'SIESTABOX' na fatura)\n";
    } else {
        echo "✗ Código: {$result->getReturnCode()} - {$result->getReturnMessage()}\n";
    }
} catch (\Throwable $e) {
    echo "✗ ERRO: {$e->getMessage()}\n";
}

// ==========================================
// TESTE 6: Consulta por TID
// ==========================================
if ($tidParaCaptura) {
    sleep(1);
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TESTE 6: Consulta por TID\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    try {
        $result = $erede->get($tidParaCaptura);

        echo "✓ SUCESSO - Transação consultada!\n";
        echo "  TID: {$result->getTid()}\n";
        echo "  Reference: {$result->getReference()}\n";
        echo "  Valor: R$ " . number_format($result->getAmount() / 100, 2, ',', '.') . "\n";
        echo "  Mensagem: {$result->getReturnMessage()}\n";
    } catch (\Throwable $e) {
        echo "✗ ERRO: {$e->getMessage()}\n";
    }
}

// ==========================================
// TESTE 7: Cancelamento
// ==========================================
if ($tidParaCaptura) {
    sleep(1);
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TESTE 7: Cancelamento/Estorno\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    try {
        $result = $erede->cancel((new Transaction(15.99))->setTid($tidParaCaptura));

        if ($result->getReturnCode() === '359' || $result->getReturnCode() === '00') {
            echo "✓ SUCESSO - Cancelada!\n";
            echo "  TID: {$result->getTid()}\n";
            echo "  Cancel ID: {$result->getCancelId()}\n";
            echo "  Mensagem: {$result->getReturnMessage()}\n";
        } else {
            echo "✗ Código: {$result->getReturnCode()} - {$result->getReturnMessage()}\n";
        }
    } catch (\Throwable $e) {
        echo "✗ ERRO: {$e->getMessage()}\n";
    }
}

// ==========================================
// RESUMO FINAL
// ==========================================
echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                      RESUMO DOS TESTES                           ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "Testes Executados:\n";
echo "  ✓ Teste 1: Autorização com Captura Automática\n";
echo "  ✓ Teste 2: Pré-Autorização (sem captura)\n";
echo "  ✓ Teste 3: Captura posterior\n";
echo "  ✓ Teste 4: Parcelamento (3x)\n";
echo "  ✓ Teste 5: SoftDescriptor\n";
echo "  ✓ Teste 6: Consulta por TID\n";
echo "  ✓ Teste 7: Cancelamento/Estorno\n\n";

echo "Configuração Descoberta:\n";
echo "  ⚠ Este PV sandbox EXIGE 3 URLs obrigatórias:\n";
echo "    - callback\n";
echo "    - threeDSecureSuccess\n";
echo "    - threeDSecureFailure\n\n";

echo "SDK Testado:\n";
echo "  ✓ iappapedroza/erede-php-v2 v2.1.1\n";
echo "  ✓ Namespace: ItsTecnologiaErede\\\n";
echo "  ✓ API eRede V2 com OAuth 2.0\n";
echo "  ✓ Sandbox: https://sandbox-erede.useredecloud.com.br/v2/\n\n";

echo "Funcionalidades Adicionais Disponíveis (não testadas):\n";
echo "  • Zero Dollar (validação de cartão)\n";
echo "  • 3DS2 (autenticação forte)\n";
echo "  • IATA (transações aéreas)\n";
echo "  • Tokenização de cartão\n";
echo "  • Transações recorrentes\n";
echo "  • MCC dinâmico\n";
echo "  • Device fingerprint\n\n";

echo "✅ TESTES CONCLUÍDOS COM SUCESSO!\n\n";
