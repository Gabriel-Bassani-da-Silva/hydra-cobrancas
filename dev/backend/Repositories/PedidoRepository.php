<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoRepository {

    /**
     * Retorna os IDs das formas de pagamento com tratamento especial
     * (antecipado e cheque), lidos de config/hydra.php.
     *
     * @return array{antecipado:int, cheque:int}
     */
    private function formasEspeciais(): array {
        return [
            'antecipado' => (int) config('hydra.bling.formas_pagamento.antecipado'),
            'cheque'     => (int) config('hydra.bling.formas_pagamento.cheque'),
        ];
    }

    /**
     * Retorna a cláusula SQL que exclui as formas de pagamento especiais
     * (antecipado e cheque) do conjunto de inadimplência padrão.
     */
    private function sqlExcluirFormasEspeciais(string $coluna = 'p.ID_FORMA_PAGAMENTO'): string {
        $f = $this->formasEspeciais();
        return "({$coluna} IS NULL OR {$coluna} NOT IN ({$f['antecipado']}, {$f['cheque']}))";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // IMPORTAÇÃO (Bling → banco local)
    // ═══════════════════════════════════════════════════════════════════════════

    public function importarPedidos(array $contas, $blingService = null, string $mode = 'upsert'): array {
        $importService = new \App\Services\PedidoImportService();
        return $importService->importarPedidos($contas, $blingService, $mode);
    }

    public function getUltimaSincronizacao(): string {
        try {
            $stmt = DB::connection()->getPdo()->query("SELECT ULTIMA_SINC_CONTAS FROM BLING_CONFIG LIMIT 1");
            $res = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($res && !empty($res['ULTIMA_SINC_CONTAS'])) {
                return date('d/m/Y H:i:s', strtotime($res['ULTIMA_SINC_CONTAS']));
            }
        } catch (\PDOException $e) {
        }
        return 'Nunca';
    }

    public function getExibirAte(): ?string {
        try {
            $stmt = DB::connection()->getPdo()->query("SELECT EXIBIR_ATE FROM BLING_CONFIG LIMIT 1");
            $res = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($res && !empty($res['EXIBIR_ATE'])) {
                return $res['EXIBIR_ATE'];
            }
        } catch (\PDOException $e) {
            // Coluna garantida pela migration; falha silenciosa apenas por segurança.
        }
        return null;
    }

    public function setExibirAte(?string $data): void {
        if ($data) {
            DB::connection()->getPdo()->prepare("UPDATE BLING_CONFIG SET EXIBIR_ATE = :data")->execute(['data' => $data]);
        } else {
            DB::connection()->getPdo()->exec("UPDATE BLING_CONFIG SET EXIBIR_ATE = NULL");
        }
    }

    public function getExibirAPartirDe(): ?string {
        try {
            $stmt = DB::connection()->getPdo()->query("SELECT EXIBIR_A_PARTIR_DE FROM BLING_CONFIG LIMIT 1");
            $res = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($res && !empty($res['EXIBIR_A_PARTIR_DE'])) {
                return $res['EXIBIR_A_PARTIR_DE'];
            }
        } catch (\PDOException $e) {
            // Coluna garantida pela migration; falha silenciosa apenas por segurança.
        }
        return null;
    }

    public function setExibirAPartirDe(?string $data): void {
        if ($data) {
            DB::connection()->getPdo()->prepare("UPDATE BLING_CONFIG SET EXIBIR_A_PARTIR_DE = :data")->execute(['data' => $data]);
        } else {
            DB::connection()->getPdo()->exec("UPDATE BLING_CONFIG SET EXIBIR_A_PARTIR_DE = NULL");
        }
    }

    /**
     * Retorna a condição SQL para filtrar por data de vencimento.
     * Se exibirAte estiver definido, filtra até essa data. Senão, usa CURDATE().
     * Se exibirAPartirDe estiver definido, filtra também por essa data inicial.
     */
    public function getDateFilter(?string $exibirAte = null, ?string $exibirAPartirDe = null): string {
        $conds = [];
        if ($exibirAPartirDe) {
            $conds[] = "p.DATA_VENCIMENTO >= '$exibirAPartirDe'";
        }
        
        if ($exibirAte) {
            $conds[] = "p.DATA_VENCIMENTO <= '$exibirAte'";
        } else {
            $conds[] = "p.DATA_VENCIMENTO < CURDATE()";
        }
        
        return "(" . implode(" AND ", $conds) . ")";
    }

    /**
     * Retorna os detalhes de um ou múltiplos pedidos específicos
     * Atualizado para incluir ID_CLIENTE e FANTASIA_CONTATO para o modal de baixa agrupado.
     */
    public function getPedidosByIds(array $ids): array {
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $sql = "
            SELECT 
                p.ID_PEDIDO, 
                p.NUM_PEDIDO, 
                p.TOTAL_PEDIDO, 
                p.VALOR_PAGO_BLING,
                p.VALOR_PAGO_EFETIVO AS VALOR_PAGO,
                p.DATA_VENCIMENTO, 
                p.SITUACAO_EFETIVA AS SITUACAO_PEDIDO,
                p.ID_CLIENTE,
                c_ext.NOME_CONTATO AS NOME_CLIENTE,
                r_ext.NOME_CONTATO AS NOME_REPRESENTANTE
            FROM " . $this->getPedidoBaseSql() . " p
            LEFT JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_CLIENTE
            LEFT JOIN CONTATO_EXTERNO r_ext ON r_ext.ID_CONTATO_BLING = p.ID_REPRESENTANTE
            WHERE p.ID_PEDIDO IN ($placeholders)
            ORDER BY p.DATA_VENCIMENTO ASC
        ";

        $stmt = DB::connection()->getPdo()->prepare($sql);
        $stmt->execute(array_values($ids));
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function registrarBaixaManual(array $baixas, int $idColaborador, bool $ignorarLimite = false): bool {
        if (empty($baixas)) return false;

        try {
            DB::connection()->getPdo()->beginTransaction();

            $totalBaixa = 0;
            
            foreach ($baixas as $b) {
                if (!$ignorarLimite && (float)$b['valor'] > 0) {
                    $pedido = DB::table('PEDIDO')->where('ID_PEDIDO', (int)$b['id'])->first();
                    if ($pedido) {
                        $localSum = DB::table('DETALHE_PAGAMENTO')->where('ID_PEDIDO', $pedido->ID_PEDIDO)->sum('VALOR_PAGO_PEDIDO');
                        $pagoAtual = max((float)$pedido->VALOR_PAGO_BLING, (float)$localSum);
                        if ($pagoAtual >= (float)$pedido->TOTAL_PEDIDO && (float)$pedido->TOTAL_PEDIDO > 0) {
                            $num = $pedido->NUM_PEDIDO ?: $pedido->ID_PEDIDO;
                            throw new \Exception("O pedido $num já está totalmente baixado (100% ou mais). Não é possível adicionar novas baixas. Para ajustar o valor, edite a baixa existente no Meu Perfil > Minhas Baixas.");
                        }
                    }
                }
                $totalBaixa += (float)$b['valor'];
            }

            // Cria o registro principal
            $stmtRegistro = DB::connection()->getPdo()->prepare("INSERT INTO REGISTRO_PAGAMENTO (VALOR_REGISTRO, ID_COLABORADOR) VALUES (:valor, :colab)");
            $stmtRegistro->execute([
                'valor' => $totalBaixa,
                'colab' => $idColaborador
            ]);
            $idRegistro = DB::connection()->getPdo()->lastInsertId();

            // Insere os detalhes
            $stmtDetalhe = DB::connection()->getPdo()->prepare("INSERT INTO DETALHE_PAGAMENTO (VALOR_PAGO_PEDIDO, ID_PEDIDO, ID_REGISTRO) VALUES (:valor, :idPedido, :idRegistro)");

            foreach ($baixas as $b) {
                $stmtDetalhe->execute([
                    'valor' => (float)$b['valor'],
                    'idPedido' => (int)$b['id'],
                    'idRegistro' => $idRegistro
                ]);
            }

            DB::connection()->getPdo()->commit();
            return true;
        } catch (\Exception $e) {
            DB::connection()->getPdo()->rollBack();
            \Illuminate\Support\Facades\Log::error('Erro em registrarBaixaManual.', ['exception' => $e->getMessage()]);
            throw $e; // Bubble up para o controller tratar a mensagem
        }
    }

    public function getDivergencias(): array {
        $stmt = DB::connection()->getPdo()->query("
            SELECT v.*, p.DATA_VENCIMENTO, p.ID_CLIENTE, c_ext.NUMERO_DOCUMENTO AS CPF_CNPJ
            FROM vw_divergencias_pagamento v
            JOIN PEDIDO p ON p.ID_PEDIDO = v.ID_PEDIDO
            LEFT JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_CLIENTE
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getPedidoBaseSql(): string { return "(             SELECT                  p.ID_PEDIDO, p.NUM_PEDIDO, p.TOTAL_PEDIDO, p.DATA_VENCIMENTO, p.VALOR_PAGO_BLING,                  p.SITUACAO_PEDIDO, p.ID_REPRESENTANTE, p.ID_CLIENTE, p.ID_FORMA_PAGAMENTO, p.EXIBIR,                 COALESCE(dp.PAGO_LOCAL, 0) AS PAGO_LOCAL,                 GREATEST(p.VALOR_PAGO_BLING, COALESCE(dp.PAGO_LOCAL, 0)) AS VALOR_PAGO_EFETIVO,                 CASE                      WHEN p.SITUACAO_PEDIDO IN (4,5) THEN p.SITUACAO_PEDIDO                      WHEN GREATEST(p.VALOR_PAGO_BLING, COALESCE(dp.PAGO_LOCAL, 0)) >= p.TOTAL_PEDIDO THEN 2                      WHEN GREATEST(p.VALOR_PAGO_BLING, COALESCE(dp.PAGO_LOCAL, 0)) > 0 THEN 3                      ELSE 1                  END AS SITUACAO_EFETIVA              FROM PEDIDO p              LEFT JOIN (                 SELECT ID_PEDIDO, SUM(VALOR_PAGO_PEDIDO) AS PAGO_LOCAL                  FROM DETALHE_PAGAMENTO                  GROUP BY ID_PEDIDO             ) dp ON dp.ID_PEDIDO = p.ID_PEDIDO         )"; }

    // ═══════════════════════════════════════════════════════════════════════════
    // CONSULTAS — CLIENTES
    // ═══════════════════════════════════════════════════════════════════════════

    public function getResumoClientes(string $filtro = 'inadimplentes', bool $somenteLivres = false): array {
        $sql = "SELECT 
                p.ID_CLIENTE AS ID_CONTATO_BLING,
                COALESCE(c_ext.NOME_CONTATO, 'Não Identificado (Sem Cadastro)') AS NOME_CONTATO,
                COUNT(DISTINCT COALESCE(NULLIF(p.NUM_PEDIDO, ''), p.ID_PEDIDO)) AS QTD_CONTAS,
                COUNT(p.ID_PEDIDO) AS QTD_PARCELAS,
                SUM(p.TOTAL_PEDIDO - p.VALOR_PAGO_EFETIVO) AS TOTAL_VALOR,
                MIN(p.DATA_VENCIMENTO) AS VENCIMENTO_MAIS_ANTIGO,
                MAX(p.DATA_VENCIMENTO) AS VENCIMENTO_MAIS_RECENTE,
                GROUP_CONCAT(p.ID_PEDIDO) AS IDS_PEDIDOS,
                GROUP_CONCAT(p.NUM_PEDIDO) AS NUMEROS_PEDIDOS,
                MAX(CASE WHEN tel.ID_TEL IS NOT NULL THEN 1 ELSE 0 END) AS TEM_TELEFONE,
                MAX(CASE WHEN tel.CONFIRMADO = 1 THEN 1 ELSE 0 END) AS TELEFONE_CONFIRMADO,
                MAX(c.PEDRAS) AS PEDRAS
             FROM " . $this->getPedidoBaseSql() . " p
             LEFT JOIN CLIENTE c ON c.ID_CONTATO_BLING = p.ID_CLIENTE
             LEFT JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_CLIENTE
             LEFT JOIN CONTATO_TEL ct ON ct.ID_CONTATO_BLING = p.ID_CLIENTE
             LEFT JOIN TEL tel ON tel.ID_TEL = ct.ID_TEL
             WHERE p.SITUACAO_EFETIVA IN (1, 3)
               AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . "
               AND p.EXIBIR = 1";
               
        if ($filtro !== 'pedras') {
            $sql .= " AND (c.PEDRAS IS NULL OR c.PEDRAS = 0)";
        }

        $formas = $this->formasEspeciais();
        if ($filtro === 'inadimplentes') {
            $sql .= " AND (c.ID_CONTATO_BLING IS NULL OR c.EXIBIR = 1)";
            $sql .= " AND " . $this->sqlExcluirFormasEspeciais();
        } elseif ($filtro === 'cheques') {
            $sql .= " AND p.ID_FORMA_PAGAMENTO = {$formas['cheque']}";
        } elseif ($filtro === 'antecipados') {
            $sql .= " AND p.ID_FORMA_PAGAMENTO = {$formas['antecipado']}";
        } elseif ($filtro === 'pedras') {
            // Check for PEDRAS column in CLIENTE
            $sql .= " AND c.PEDRAS = 1";
        }
               
        if ($somenteLivres) {
            $sql .= " AND p.ID_CLIENTE NOT IN (
                          SELECT vcc.ID_CONTATO_BLING 
                          FROM VINCULO_COBRANCA_CLIENTE vcc 
                          JOIN COBRANCA cob ON cob.ID_COBRANCA = vcc.ID_COBRANCA 
                          WHERE cob.DATA_FIM IS NULL
                      )";
        }
        
        $sql .= " GROUP BY p.ID_CLIENTE, c_ext.NOME_CONTATO
                  ORDER BY TOTAL_VALOR DESC";
                  
        // A coluna PEDRAS é garantida pela migration 04_tabelas, portanto
        // não é mais necessário alterar o schema em tempo de execução.
        $stmt = DB::connection()->getPdo()->query($sql);
        return $stmt->fetchAll();
    }

    public function getContasCliente(int $idContatoBling): array {
        $stmt = DB::connection()->getPdo()->prepare(
            "SELECT 
                p.ID_PEDIDO AS ID_CONTA_RECEBER, 
                p.TOTAL_PEDIDO AS VALOR, 
                p.DATA_VENCIMENTO AS VENCIMENTO,
                p.NUM_PEDIDO AS NUMERO_DOCUMENTO, 
                p.SITUACAO_EFETIVA AS SITUACAO,
                p.VALOR_PAGO_EFETIVO AS VALOR_PAGO
             FROM " . $this->getPedidoBaseSql() . " p
             WHERE p.ID_CLIENTE = :id
               AND p.SITUACAO_EFETIVA IN (1, 3)
               AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . "
               AND p.EXIBIR = 1
             ORDER BY p.DATA_VENCIMENTO ASC"
        );
        $stmt->execute(['id' => $idContatoBling]);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CONSULTAS — REPRESENTANTES
    // ═══════════════════════════════════════════════════════════════════════════

    public function getResumoRepresentantes(bool $somenteLivres = false): array {
        $sql = "SELECT 
                p.ID_REPRESENTANTE AS ID_CONTATO_BLING,
                c_ext.NOME_CONTATO AS NOME_REPRESENTANTE,
                COUNT(DISTINCT p.ID_CLIENTE) AS QTD_CLIENTES,
                GROUP_CONCAT(DISTINCT cli_ext.NOME_CONTATO SEPARATOR ', ') AS NOMES_CLIENTES,
                GROUP_CONCAT(DISTINCT p.ID_CLIENTE) AS IDS_CLIENTES,
                COUNT(DISTINCT COALESCE(NULLIF(p.NUM_PEDIDO, ''), p.ID_PEDIDO)) AS QTD_CONTAS,
                COUNT(p.ID_PEDIDO) AS QTD_PARCELAS,
                SUM(p.TOTAL_PEDIDO - p.VALOR_PAGO_EFETIVO) AS TOTAL_VALOR,
                MIN(p.DATA_VENCIMENTO) AS VENCIMENTO_MAIS_ANTIGO,
                MAX(p.DATA_VENCIMENTO) AS VENCIMENTO_MAIS_RECENTE,
                GROUP_CONCAT(p.ID_PEDIDO) AS IDS_PEDIDOS,
                GROUP_CONCAT(p.NUM_PEDIDO) AS NUMEROS_PEDIDOS,
                MAX(CASE WHEN tel.ID_TEL IS NOT NULL THEN 1 ELSE 0 END) AS TEM_TELEFONE,
                MAX(CASE WHEN tel.CONFIRMADO = 1 THEN 1 ELSE 0 END) AS TELEFONE_CONFIRMADO
             FROM " . $this->getPedidoBaseSql() . " p
             INNER JOIN REPRESENTANTE r ON r.ID_CONTATO_BLING = p.ID_REPRESENTANTE
             INNER JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_REPRESENTANTE
             INNER JOIN CONTATO_EXTERNO cli_ext ON cli_ext.ID_CONTATO_BLING = p.ID_CLIENTE
             LEFT JOIN CONTATO_TEL ct ON ct.ID_CONTATO_BLING = p.ID_REPRESENTANTE
             LEFT JOIN TEL tel ON tel.ID_TEL = ct.ID_TEL
             WHERE p.SITUACAO_EFETIVA IN (1, 3)
               AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . "
               AND r.EXIBIR = 1
               AND p.EXIBIR = 1";
               
        // $somenteLivres desativado aqui para que os representantes sempre apareçam
        
        
        $sql .= " GROUP BY p.ID_REPRESENTANTE, c_ext.NOME_CONTATO
                  ORDER BY TOTAL_VALOR DESC";
                  
        $stmt = DB::connection()->getPdo()->query($sql);
        return $stmt->fetchAll();
    }

    public function getContasRepresentante(int $idVendedor): array {
        $stmt = DB::connection()->getPdo()->prepare(
            "SELECT 
                p.ID_PEDIDO AS ID_CONTA_RECEBER, 
                p.TOTAL_PEDIDO AS VALOR, 
                p.DATA_VENCIMENTO AS VENCIMENTO,
                p.NUM_PEDIDO AS NUMERO_DOCUMENTO, 
                p.SITUACAO_EFETIVA AS SITUACAO,
                p.VALOR_PAGO_EFETIVO AS VALOR_PAGO,
                p.ID_CLIENTE, 
                c_ext.NOME_CONTATO AS NOME_CLIENTE,
                c_ext.NUMERO_DOCUMENTO AS CPF_CNPJ
             FROM " . $this->getPedidoBaseSql() . " p
             LEFT JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_CLIENTE
             WHERE p.ID_REPRESENTANTE = :id_vendedor
               AND p.SITUACAO_EFETIVA IN (1, 3)
               AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . "
               AND p.EXIBIR = 1
             ORDER BY p.DATA_VENCIMENTO ASC"
        );
        $stmt->execute(['id_vendedor' => $idVendedor]);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CONSULTAS — CONTATOS FINANCEIROS
    // ═══════════════════════════════════════════════════════════════════════════

    public function getResumoContatosFinanceirosClientes(string $filtro = 'todos', bool $somenteLivres = false): array {
        $sql = "SELECT 
                cf.ID_CONTATO AS ID_CF,
                cf.NOME_CONTATO AS NOME_CF,
                t.NUM_TEL,
                GROUP_CONCAT(DISTINCT p.ID_CLIENTE) AS IDS_CLIENTES,
                COUNT(DISTINCT COALESCE(NULLIF(p.NUM_PEDIDO, ''), p.ID_PEDIDO)) AS QTD_CONTAS,
                COUNT(p.ID_PEDIDO) AS QTD_PARCELAS,
                SUM(p.TOTAL_PEDIDO - p.VALOR_PAGO_EFETIVO) AS TOTAL_VALOR,
                MIN(p.DATA_VENCIMENTO) AS VENCIMENTO_MAIS_ANTIGO,
                MAX(p.DATA_VENCIMENTO) AS VENCIMENTO_MAIS_RECENTE,
                GROUP_CONCAT(p.ID_PEDIDO) AS IDS_PEDIDOS,
                GROUP_CONCAT(p.NUM_PEDIDO) AS NUMEROS_PEDIDOS,
                CASE WHEN t.NUM_TEL IS NOT NULL THEN 1 ELSE 0 END AS TEM_TELEFONE,
                t.CONFIRMADO AS TELEFONE_CONFIRMADO
             FROM CONTATO_FINANCEIRO cf
             JOIN TEL t ON t.ID_TEL = cf.ID_TEL
             JOIN VINCULO_CONTATO_CLIENTE vcc ON vcc.ID_CONTATO = cf.ID_CONTATO
             JOIN " . $this->getPedidoBaseSql() . " p ON p.ID_CLIENTE = vcc.ID_CLIENTE
             WHERE p.SITUACAO_EFETIVA IN (1, 3)
               AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . "
               AND p.EXIBIR = 1";
               
        if ($filtro === 'pedras') {
            $sql .= " AND cf.PEDRAS = 1";
        } else {
            $sql .= " AND (cf.PEDRAS IS NULL OR cf.PEDRAS = 0)";
        }
        
        $sql .= " GROUP BY cf.ID_CONTATO, cf.NOME_CONTATO, t.NUM_TEL, t.CONFIRMADO
                  ORDER BY TOTAL_VALOR DESC";
                  
        try {
            $stmt = DB::connection()->getPdo()->query($sql);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Unknown column') !== false && strpos($e->getMessage(), 'PEDRAS') !== false) {
                DB::connection()->getPdo()->exec("ALTER TABLE CONTATO_FINANCEIRO ADD COLUMN PEDRAS TINYINT(1) DEFAULT 0");
                $stmt = DB::connection()->getPdo()->query($sql);
                return $stmt->fetchAll();
            }
            throw $e;
        }
    }

    public function getResumoContatosFinanceirosRepresentantes(string $filtro = 'todos', bool $somenteLivres = false): array {
        $sql = "SELECT 
                cf.ID_CONTATO AS ID_CF,
                cf.NOME_CONTATO AS NOME_CF,
                t.NUM_TEL,
                GROUP_CONCAT(DISTINCT p.ID_REPRESENTANTE) AS IDS_REPRESENTANTES,
                COUNT(DISTINCT COALESCE(NULLIF(p.NUM_PEDIDO, ''), p.ID_PEDIDO)) AS QTD_CONTAS,
                COUNT(p.ID_PEDIDO) AS QTD_PARCELAS,
                SUM(p.TOTAL_PEDIDO - p.VALOR_PAGO_EFETIVO) AS TOTAL_VALOR,
                MIN(p.DATA_VENCIMENTO) AS VENCIMENTO_MAIS_ANTIGO,
                MAX(p.DATA_VENCIMENTO) AS VENCIMENTO_MAIS_RECENTE,
                GROUP_CONCAT(p.ID_PEDIDO) AS IDS_PEDIDOS,
                GROUP_CONCAT(p.NUM_PEDIDO) AS NUMEROS_PEDIDOS,
                CASE WHEN t.NUM_TEL IS NOT NULL THEN 1 ELSE 0 END AS TEM_TELEFONE,
                t.CONFIRMADO AS TELEFONE_CONFIRMADO
             FROM CONTATO_FINANCEIRO cf
             JOIN TEL t ON t.ID_TEL = cf.ID_TEL
             JOIN VINCULO_CONTATO_REPRESENTANTE vcr ON vcr.ID_CONTATO = cf.ID_CONTATO
             JOIN " . $this->getPedidoBaseSql() . " p ON p.ID_REPRESENTANTE = vcr.ID_REPRESENTANTE
             WHERE p.SITUACAO_EFETIVA IN (1, 3)
               AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . "
               AND p.EXIBIR = 1";
               
        if ($filtro === 'pedras') {
            $sql .= " AND cf.PEDRAS = 1";
        }
        
        $sql .= " GROUP BY cf.ID_CONTATO, cf.NOME_CONTATO, t.NUM_TEL, t.CONFIRMADO
                  ORDER BY TOTAL_VALOR DESC";
                  
        try {
            $stmt = DB::connection()->getPdo()->query($sql);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Unknown column') !== false && strpos($e->getMessage(), 'PEDRAS') !== false) {
                DB::connection()->getPdo()->exec("ALTER TABLE CONTATO_FINANCEIRO ADD COLUMN PEDRAS TINYINT(1) DEFAULT 0");
                $stmt = DB::connection()->getPdo()->query($sql);
                return $stmt->fetchAll();
            }
            throw $e;
        }
    }

    public function getContasContatoFinanceiro(int $idCf): array {
        $stmt = DB::connection()->getPdo()->prepare(
            "SELECT 
                p.ID_PEDIDO AS ID_CONTA_RECEBER, 
                p.TOTAL_PEDIDO AS VALOR, 
                p.DATA_VENCIMENTO AS VENCIMENTO,
                p.NUM_PEDIDO AS NUMERO_DOCUMENTO, 
                p.SITUACAO_EFETIVA AS SITUACAO,
                p.VALOR_PAGO_EFETIVO AS VALOR_PAGO,
                c_ext.NOME_CONTATO AS NOME_CLIENTE,
                c_ext.NUMERO_DOCUMENTO AS CPF_CNPJ,
                p.ID_CLIENTE
             FROM " . $this->getPedidoBaseSql() . " p
             LEFT JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_CLIENTE
             LEFT JOIN VINCULO_CONTATO_CLIENTE vcc ON vcc.ID_CLIENTE = p.ID_CLIENTE
             LEFT JOIN VINCULO_CONTATO_REPRESENTANTE vcr ON vcr.ID_REPRESENTANTE = p.ID_REPRESENTANTE
             WHERE (vcc.ID_CONTATO = :id1 OR vcr.ID_CONTATO = :id2)
               AND p.SITUACAO_EFETIVA IN (1, 3)
               AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . "
               AND p.EXIBIR = 1
             ORDER BY p.DATA_VENCIMENTO ASC"
        );
        $stmt->execute(['id1' => $idCf, 'id2' => $idCf]);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CONSULTAS — TOTAIS / DASHBOARD
    // ═══════════════════════════════════════════════════════════════════════════

    public function getTotalEmAberto(): array {
        $stmt = DB::connection()->getPdo()->query(
            "SELECT 
                COUNT(*) AS QTD_CONTAS,
                COALESCE(SUM(p.TOTAL_PEDIDO - p.VALOR_PAGO_EFETIVO), 0) AS TOTAL_VALOR,
                MIN(p.DATA_VENCIMENTO) AS VENCIMENTO_MAIS_ANTIGO
             FROM " . $this->getPedidoBaseSql() . " p 
             WHERE p.SITUACAO_EFETIVA IN (1, 3)
               AND p.DATA_VENCIMENTO < CURDATE()
               AND p.EXIBIR = 1"
        );
        return $stmt->fetch();
    }


    public function getContagensAbas(): array {
        $stmtClientes = DB::connection()->getPdo()->query("SELECT COUNT(DISTINCT p.ID_CLIENTE) AS total FROM " . $this->getPedidoBaseSql() . " p INNER JOIN CLIENTE c ON c.ID_CONTATO_BLING = p.ID_CLIENTE WHERE p.SITUACAO_EFETIVA IN (1, 3) AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . " AND c.EXIBIR = 1 AND p.EXIBIR = 1");
        $countClientes = $stmtClientes->fetchColumn();

        $stmtRepresentantes = DB::connection()->getPdo()->query("SELECT COUNT(DISTINCT p.ID_REPRESENTANTE) AS total FROM " . $this->getPedidoBaseSql() . " p INNER JOIN REPRESENTANTE r ON r.ID_CONTATO_BLING = p.ID_REPRESENTANTE WHERE p.SITUACAO_EFETIVA IN (1, 3) AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . " AND r.EXIBIR = 1 AND p.EXIBIR = 1");
        $countRepresentantes = $stmtRepresentantes->fetchColumn();

        $stmtPedidos = DB::connection()->getPdo()->query("SELECT COUNT(DISTINCT COALESCE(NULLIF(p.NUM_PEDIDO, ''), p.ID_PEDIDO)) AS total FROM " . $this->getPedidoBaseSql() . " p WHERE p.SITUACAO_EFETIVA IN (1, 3) AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . " AND p.EXIBIR = 1 AND " . $this->sqlExcluirFormasEspeciais());
        $countPedidos = $stmtPedidos->fetchColumn();

        $stmtPedras = DB::connection()->getPdo()->query("SELECT COUNT(DISTINCT p.ID_CLIENTE) AS total FROM " . $this->getPedidoBaseSql() . " p INNER JOIN CLIENTE c ON c.ID_CONTATO_BLING = p.ID_CLIENTE WHERE p.SITUACAO_EFETIVA IN (1, 3) AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . " AND c.EXIBIR = 1 AND p.EXIBIR = 1 AND c.PEDRAS = 1");
        $countPedras = $stmtPedras->fetchColumn();

        return [
            'clientes' => $countClientes,
            'representantes' => $countRepresentantes,
            'pedidos' => $countPedidos,
            'pedras' => $countPedras
        ];
    }

    public function buscarContas(string $q): array {
        $like = '%' . $q . '%';
        $stmt = DB::connection()->getPdo()->prepare(
            "SELECT 
                p.ID_PEDIDO AS ID_CONTA_RECEBER, 
                p.TOTAL_PEDIDO AS VALOR, 
                p.DATA_VENCIMENTO AS VENCIMENTO,
                p.NUM_PEDIDO AS NUMERO_DOCUMENTO, 
                p.SITUACAO_EFETIVA AS SITUACAO,
                p.VALOR_PAGO_EFETIVO AS VALOR_PAGO,
                c_ext.NOME_CONTATO,
                p.ID_CLIENTE AS ID_CONTATO_BLING
             FROM " . $this->getPedidoBaseSql() . " p
             LEFT JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_CLIENTE
             WHERE (c_ext.NOME_CONTATO LIKE :q1 OR p.NUM_PEDIDO LIKE :q2)
               AND p.SITUACAO_EFETIVA IN (1, 3)
               AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . "
               AND p.EXIBIR = 1
             ORDER BY p.DATA_VENCIMENTO ASC
             LIMIT 50"
        );
        $stmt->execute(['q1' => $like, 'q2' => $like]);
        return $stmt->fetchAll();
    }

    public function getAllPedidosPendentes(): array {
        $stmt = DB::connection()->getPdo()->query(
            "SELECT 
                p.ID_PEDIDO, 
                p.NUM_PEDIDO, 
                p.TOTAL_PEDIDO, 
                p.VALOR_PAGO_EFETIVO AS VALOR_PAGO,
                p.DATA_VENCIMENTO, 
                p.SITUACAO_EFETIVA AS SITUACAO_PEDIDO,
                c_ext.NOME_CONTATO AS NOME_CLIENTE,
                r_ext.NOME_CONTATO AS NOME_REPRESENTANTE
             FROM " . $this->getPedidoBaseSql() . " p
             LEFT JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_CLIENTE
             LEFT JOIN CONTATO_EXTERNO r_ext ON r_ext.ID_CONTATO_BLING = p.ID_REPRESENTANTE
             WHERE p.SITUACAO_EFETIVA IN (1, 3)
               AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . "
               AND p.EXIBIR = 1
               AND " . $this->sqlExcluirFormasEspeciais() . "
             ORDER BY p.DATA_VENCIMENTO ASC"
        );
        return $stmt->fetchAll();
    }

    public function getAllPedidosPendentesGerais(): array {
        $stmt = DB::connection()->getPdo()->query(
            "SELECT 
                p.ID_PEDIDO, 
                p.NUM_PEDIDO, 
                p.TOTAL_PEDIDO, 
                p.VALOR_PAGO_EFETIVO AS VALOR_PAGO,
                p.DATA_VENCIMENTO, 
                p.SITUACAO_EFETIVA AS SITUACAO_PEDIDO,
                c_ext.NOME_CONTATO AS NOME_CLIENTE,
                r_ext.NOME_CONTATO AS NOME_REPRESENTANTE
             FROM " . $this->getPedidoBaseSql() . " p
             LEFT JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_CLIENTE
             LEFT JOIN CONTATO_EXTERNO r_ext ON r_ext.ID_CONTATO_BLING = p.ID_REPRESENTANTE
             WHERE p.SITUACAO_EFETIVA IN (1, 3)
               AND " . $this->getDateFilter($this->getExibirAte(), $this->getExibirAPartirDe()) . "
               AND p.EXIBIR = 1
             ORDER BY p.DATA_VENCIMENTO ASC"
        );
        return $stmt->fetchAll();
    }

    public function getAllPedidosPorFormaPagamento(int $idFormaPagamento): array {
        $stmt = DB::connection()->getPdo()->prepare(
            "SELECT 
                p.ID_PEDIDO, 
                p.NUM_PEDIDO, 
                p.TOTAL_PEDIDO, 
                p.VALOR_PAGO_EFETIVO AS VALOR_PAGO,
                p.DATA_VENCIMENTO, 
                p.SITUACAO_EFETIVA AS SITUACAO_PEDIDO,
                c_ext.NOME_CONTATO AS NOME_CLIENTE,
                r_ext.NOME_CONTATO AS NOME_REPRESENTANTE
             FROM " . $this->getPedidoBaseSql() . " p
             LEFT JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_CLIENTE
             LEFT JOIN CONTATO_EXTERNO r_ext ON r_ext.ID_CONTATO_BLING = p.ID_REPRESENTANTE
             WHERE p.SITUACAO_EFETIVA IN (1, 3)
               AND p.DATA_VENCIMENTO < CURDATE()
               AND p.EXIBIR = 1
               AND p.ID_FORMA_PAGAMENTO = :id
             ORDER BY p.DATA_VENCIMENTO ASC"
        );
        $stmt->execute(['id' => $idFormaPagamento]);
        return $stmt->fetchAll();
    }

    public function getAllPedidosPagos(): array {
        $stmt = DB::connection()->getPdo()->query(
            "SELECT 
                p.ID_PEDIDO, 
                p.NUM_PEDIDO, 
                p.TOTAL_PEDIDO, 
                p.VALOR_PAGO_EFETIVO AS VALOR_PAGO,
                p.DATA_VENCIMENTO, 
                p.SITUACAO_EFETIVA AS SITUACAO_PEDIDO,
                c_ext.NOME_CONTATO AS NOME_CLIENTE,
                r_ext.NOME_CONTATO AS NOME_REPRESENTANTE
             FROM " . $this->getPedidoBaseSql() . " p
             LEFT JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_CLIENTE
             LEFT JOIN CONTATO_EXTERNO r_ext ON r_ext.ID_CONTATO_BLING = p.ID_REPRESENTANTE
             WHERE p.SITUACAO_EFETIVA = 2
               AND p.EXIBIR = 1
             ORDER BY p.DATA_VENCIMENTO DESC
             LIMIT 200"
        );
        return $stmt->fetchAll();
    }

    public function getAllPedidos(): array {
        $stmt = DB::connection()->getPdo()->query(
            "SELECT 
                p.ID_PEDIDO, 
                p.NUM_PEDIDO, 
                p.TOTAL_PEDIDO, 
                p.VALOR_PAGO_EFETIVO AS VALOR_PAGO,
                p.DATA_VENCIMENTO, 
                p.SITUACAO_EFETIVA AS SITUACAO_PEDIDO,
                c_ext.NOME_CONTATO AS NOME_CLIENTE,
                r_ext.NOME_CONTATO AS NOME_REPRESENTANTE
             FROM " . $this->getPedidoBaseSql() . " p
             LEFT JOIN CONTATO_EXTERNO c_ext ON c_ext.ID_CONTATO_BLING = p.ID_CLIENTE
             LEFT JOIN CONTATO_EXTERNO r_ext ON r_ext.ID_CONTATO_BLING = p.ID_REPRESENTANTE
             WHERE p.EXIBIR = 1
             ORDER BY p.DATA_VENCIMENTO DESC
             LIMIT 500"
        );
        return $stmt->fetchAll();
    }

    public function getPedidosSemRepresentante(): array {
        $stmt = DB::connection()->getPdo()->query(
            "SELECT p.ID_PEDIDO FROM " . $this->getPedidoBaseSql() . " p 
             WHERE p.ID_REPRESENTANTE IS NULL 
               AND p.EXIBIR = 1 
               AND p.SITUACAO_EFETIVA IN (1, 3)"
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function atualizarRepresentantePedido(int $idPedido, int $idVendedor): bool {
        // Busca o ID_CONTATO_BLING correspondente ao ID_VENDEDOR
        $stmtRep = DB::connection()->getPdo()->prepare("SELECT ID_CONTATO_BLING FROM REPRESENTANTE WHERE ID_VENDEDOR = :id");
        $stmtRep->execute(['id' => $idVendedor]);
        $row = $stmtRep->fetch(\PDO::FETCH_ASSOC);
        $idContatoBling = $row ? $row['ID_CONTATO_BLING'] : null;

        // Se o vendedor ainda não estiver sincronizado localmente, não podemos vincular a foreign key correta
        if (!$idContatoBling) {
            return false;
        }

        try {
            DB::connection()->getPdo()->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $stmt = DB::connection()->getPdo()->prepare("UPDATE PEDIDO SET ID_REPRESENTANTE = :idRep WHERE ID_PEDIDO = :idPed");
            $result = $stmt->execute([
                'idRep' => $idContatoBling,
                'idPed' => $idPedido
            ]);
            DB::connection()->getPdo()->exec("SET FOREIGN_KEY_CHECKS = 1;");
            return $result;
        } catch (\PDOException $e) {
            DB::connection()->getPdo()->exec("SET FOREIGN_KEY_CHECKS = 1;");
            throw $e;
        }
    }

}
