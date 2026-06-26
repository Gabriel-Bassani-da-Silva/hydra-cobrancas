<?php
require __DIR__ . '/dev/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/dev/backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Repositories\PedidoRepository;

$repo = new PedidoRepository();

// Let's get the latest pedidios that are NOT paid, to find an ID to test
$pendentes = $repo->getAllPedidosPendentes();
if (empty($pendentes)) {
    echo "Nenhum pedido pendente.\n";
    exit;
}

$testId = $pendentes[0]['ID_PEDIDO'];
echo "Testando ID: $testId\n";

try {
    $res = $repo->getPedidosByIds([$testId]);
    echo json_encode($res, JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
