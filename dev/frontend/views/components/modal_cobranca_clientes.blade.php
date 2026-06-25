<?php
/**
 * @var array $clientes
 * @var array $clientesAtivos
 */

if (empty($clientes)) {
    echo '';
    return;
}

foreach ($clientes as $c):
    $id = $c['id'];
    $nome = htmlspecialchars($c['nome']);
    
    // Verifica se este cliente já está sendo cobrado
    $isCharged = false;
    $nomeColaborador = '';
    
    if (isset($clientesAtivos[$id])) {
        $isCharged = true;
        $nomeColaborador = $clientesAtivos[$id]['NOME_COLABORADOR'] ?? '';
    } elseif (isset($clientesAtivos[(string)$id])) {
        $isCharged = true;
        $nomeColaborador = $clientesAtivos[(string)$id]['NOME_COLABORADOR'] ?? '';
    }

    if ($isCharged):
        $primeiroNomeColab = explode(' ', trim($nomeColaborador))[0] ?? '';
?>
        <label style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:6px;cursor:not-allowed;font-size:0.88rem;opacity:0.6;" title="Já sendo cobrado por <?= htmlspecialchars($nomeColaborador) ?>">
            <input type="checkbox" class="check-cliente-cob" value="<?= htmlspecialchars($id) ?>" disabled style="accent-color:#ff833b;">
            <?= $nome ?> <span style="color:#ef4444;font-size:0.75rem;margin-left:6px;">(Em Cobrança: <?= htmlspecialchars($primeiroNomeColab) ?>)</span>
        </label>
<?php else: ?>
        <label style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:6px;cursor:pointer;font-size:0.88rem;">
            <input type="checkbox" class="check-cliente-cob" value="<?= htmlspecialchars($id) ?>" checked style="accent-color:#ff833b;">
            <?= $nome ?>
        </label>
<?php 
    endif;
endforeach; 
?>
