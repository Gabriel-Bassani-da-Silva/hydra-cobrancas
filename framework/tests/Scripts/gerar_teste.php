<?php
/**
 * Gera uma planilha XLSX de teste com dados reais do banco + dados fictícios.
 * Acesse via navegador: http://localhost/hydraRemake/gerar_teste.php
 */

// Conexão com o banco
$pdo = new PDO('mysql:host=127.0.0.1;dbname=hydraRemake;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Carrega a biblioteca XLSX
$libPath = __DIR__ . '/app/Services/SimpleXLSXGen.php';
if (!file_exists($libPath)) {
    // Tenta baixar
    $url = 'https://raw.githubusercontent.com/shuchkin/simplexlsxgen/master/src/SimpleXLSXGen.php';
    $content = @file_get_contents($url);
    if ($content) {
        @mkdir(__DIR__ . '/app/Services/', 0777, true);
        file_put_contents($libPath, $content);
    } else {
        die('Não foi possível baixar SimpleXLSXGen.');
    }
}
require_once $libPath;

// ── Buscar dados reais do banco ─────────────────────────────────────────────

// Contatos existentes
$contatos = $pdo->query("
    SELECT ce.NOME_CONTATO, ce.NUMERO_DOCUMENTO 
    FROM CONTATO_EXTERNO ce 
    WHERE ce.NOME_CONTATO IS NOT NULL AND ce.NOME_CONTATO != ''
    ORDER BY RAND() LIMIT 10
")->fetchAll();

// Telefones existentes com vínculos
$telefones = $pdo->query("
    SELECT t.NUM_TEL, t.CONFIRMADO, ce.NOME_CONTATO, ce.NUMERO_DOCUMENTO
    FROM TEL t
    JOIN CONTATO_TEL ct ON ct.ID_TEL = t.ID_TEL
    JOIN CONTATO_EXTERNO ce ON ce.ID_CONTATO_BLING = ct.ID_CONTATO_BLING
    ORDER BY RAND() LIMIT 8
")->fetchAll();

// ── Montar linhas da planilha ───────────────────────────────────────────────

$linhas = [];

// Cabeçalho
$linhas[] = ['NOME', 'CPF_CNPJ', 'TELEFONE', 'STATUS'];

// GRUPO 1: Contato existente + telefone NOVO
foreach (array_slice($contatos, 0, 4) as $c) {
    $linhas[] = [
        $c['NOME_CONTATO'],
        $c['NUMERO_DOCUMENTO'] ?: '',
        gerarTelefone(),
        ['Confirmado', 'Tentativa'][rand(0, 1)]
    ];
}

// GRUPO 2: Telefone existente → INVERTER status (testar atualização)
foreach (array_slice($telefones, 0, 4) as $t) {
    $statusAtual = $t['CONFIRMADO'] ? 'Confirmado' : 'Tentativa';
    $novoStatus = $statusAtual === 'Confirmado' ? 'Tentativa' : 'Confirmado';
    $linhas[] = [
        $t['NOME_CONTATO'],
        $t['NUMERO_DOCUMENTO'] ?: '',
        $t['NUM_TEL'],
        $novoStatus
    ];
}

// GRUPO 3: Telefone existente → MESMO status (nada muda)
foreach (array_slice($telefones, 4, 2) as $t) {
    $statusAtual = $t['CONFIRMADO'] ? 'Confirmado' : 'Tentativa';
    $linhas[] = [
        $t['NOME_CONTATO'],
        $t['NUMERO_DOCUMENTO'] ?: '',
        $t['NUM_TEL'],
        $statusAtual
    ];
}

// GRUPO 4: Contatos NOVOS (não existem no banco)
$nomesFicticios = [
    ['Helena Nascimento', gerarCpf()],
    ['Thiago Barros', gerarCpf()],
    ['Isabela Pinto', gerarCpf()],
    ['Nova Distribuidora Norte LTDA', gerarCnpj()],
    ['Comércio Digital Express ME', gerarCnpj()],
];
foreach ($nomesFicticios as $nf) {
    $linhas[] = [
        $nf[0],
        $nf[1],
        gerarTelefone(),
        ['Confirmado', 'Tentativa'][rand(0, 1)]
    ];
}

// GRUPO 5: Contato existente busca por NOME (sem doc)
if (!empty($contatos)) {
    $c = end($contatos);
    $linhas[] = [
        $c['NOME_CONTATO'],
        '',
        gerarTelefone(),
        'Tentativa'
    ];
}

// GRUPO 6: Contato sem telefone
$linhas[] = [
    'Empresa Sem Telefone LTDA',
    gerarCnpj(),
    '',
    ''
];

// GRUPO 7: Linha com ERRO (nome e doc vazios)
$linhas[] = [
    '',
    '',
    gerarTelefone(),
    'Confirmado'
];

// ── Gerar e salvar o arquivo ────────────────────────────────────────────────
$xlsx = \Shuchkin\SimpleXLSXGen::fromArray($linhas);
$caminho = __DIR__ . '/planilha_teste_importacao.xlsx';
$xlsx->saveAs($caminho);

echo "<h2>✅ Planilha gerada com sucesso!</h2>";
echo "<p>Arquivo salvo em: <code>$caminho</code></p>";
echo "<p>Total de linhas: <strong>" . (count($linhas) - 1) . "</strong> (sem contar o cabeçalho)</p>";
echo "<hr>";
echo "<h3>Preview:</h3>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; font-family:Arial;'>";
foreach ($linhas as $i => $row) {
    $tag = $i === 0 ? 'th' : 'td';
    $bg = $i === 0 ? ' style="background:#0d6efd;color:#fff;"' : '';
    if ($i > 0 && empty($row[0]) && empty($row[1])) $bg = ' style="background:#f8d7da;"';
    echo "<tr$bg>";
    foreach ($row as $cell) {
        echo "<$tag style='padding:6px 12px;'>".htmlspecialchars($cell)."</$tag>";
    }
    echo "<td style='padding:6px 12px; color:#888; font-style:italic; font-size:12px;'>";
    if ($i === 0) echo "CENÁRIO";
    elseif ($i <= 4) echo "Contato existente + tel novo";
    elseif ($i <= 8) echo "Tel existente → inverter status";
    elseif ($i <= 10) echo "Tel existente → mesmo status";
    elseif ($i <= 15) echo "Contato novo";
    elseif ($i <= 16) echo "Busca por nome (sem doc)";
    elseif ($i <= 17) echo "Sem telefone";
    else echo "ERRO: nome e doc vazios";
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

// ── Funções auxiliares ──────────────────────────────────────────────────────
function gerarTelefone() {
    $ddds = ['11','21','31','41','47','48','51','61','71','85'];
    $ddd = $ddds[array_rand($ddds)];
    $num = '9';
    for ($i = 0; $i < 8; $i++) $num .= rand(0, 9);
    return $ddd . $num;
}

function gerarCpf() {
    $nums = [];
    for ($i = 0; $i < 9; $i++) $nums[] = rand(0, 9);
    for ($j = 0; $j < 2; $j++) {
        $sum = 0;
        for ($i = 0; $i < count($nums); $i++) {
            $sum += (count($nums) + 1 - $i) * $nums[$i];
        }
        $val = $sum % 11;
        $nums[] = $val < 2 ? 0 : 11 - $val;
    }
    return implode('', $nums);
}

function gerarCnpj() {
    $nums = [];
    for ($i = 0; $i < 8; $i++) $nums[] = rand(0, 9);
    $nums = array_merge($nums, [0, 0, 0, 1]);
    $pesos1 = [5,4,3,2,9,8,7,6,5,4,3,2];
    $sum = 0;
    for ($i = 0; $i < 12; $i++) $sum += $nums[$i] * $pesos1[$i];
    $val = $sum % 11;
    $nums[] = $val < 2 ? 0 : 11 - $val;
    $pesos2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
    $sum = 0;
    for ($i = 0; $i < 13; $i++) $sum += $nums[$i] * $pesos2[$i];
    $val = $sum % 11;
    $nums[] = $val < 2 ? 0 : 11 - $val;
    return implode('', $nums);
}
