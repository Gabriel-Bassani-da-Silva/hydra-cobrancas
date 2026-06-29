<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ContatoRepository {
    private $pdo;

    public function __construct() {
        $this->pdo = DB::connection()->getPdo();
    }

    private function limparTelefone($num): string {
        return preg_replace('/\D/', '', trim($num ?? ''));
    }

    private function isPhoneOwnedByRep($idTel): bool {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM CONTATO_TEL ct
            JOIN REPRESENTANTE r ON ct.ID_CONTATO_BLING = r.ID_CONTATO_BLING
            WHERE ct.ID_TEL = :tel
        ");
        $stmt->execute(['tel' => $idTel]);
        return (bool)$stmt->fetch();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // IMPORTAÇÃO (Bling → banco local)
    // ═══════════════════════════════════════════════════════════════════════════

    public function importarContatos(array $contatos): array {
        $novos = [];
        $stmt = $this->pdo->prepare(
            "INSERT INTO CONTATO_EXTERNO (ID_CONTATO_BLING, NOME_CONTATO, NUMERO_DOCUMENTO) 
             VALUES (:id, :nome, :doc)
             ON DUPLICATE KEY UPDATE NOME_CONTATO = VALUES(NOME_CONTATO), NUMERO_DOCUMENTO = IF(VALUES(NUMERO_DOCUMENTO) != '', VALUES(NUMERO_DOCUMENTO), NUMERO_DOCUMENTO)"
        );

        foreach ($contatos as $c) {
            $id = $c['id'] ?? null;
            $nome = trim($c['nome'] ?? '');
            $doc = trim($c['numeroDocumento'] ?? '');
            if (!$id || empty($nome) || $nome === '<') continue;

            $stmt->execute(['id' => $id, 'nome' => $nome, 'doc' => $doc]);
            if ($stmt->rowCount() > 0) {
                $novos[] = ['id' => $id, 'nome' => $nome, 'doc' => $doc];
            }
        }
        return $novos;
    }

    public function importarTelefones(array $contatos): array {
        $novos = [];
        $stmtTel = $this->pdo->prepare("INSERT IGNORE INTO TEL (NUM_TEL, CONFIRMADO, ORIGEM) VALUES (:num, :conf, 'bling')");
        $stmtBusca = $this->pdo->prepare("SELECT ID_TEL FROM TEL WHERE NUM_TEL = :num");
        $stmtVinculo = $this->pdo->prepare("INSERT IGNORE INTO CONTATO_TEL (ID_CONTATO_BLING, ID_TEL) VALUES (:id_contato, :id_tel)");
        $stmtIsRep = $this->pdo->prepare("SELECT 1 FROM REPRESENTANTE WHERE ID_CONTATO_BLING = :id");

        foreach ($contatos as $c) {
            $id = $c['id'] ?? null;
            if (!$id) continue;

            $stmtCheckContato = $this->pdo->prepare("SELECT 1 FROM CONTATO_EXTERNO WHERE ID_CONTATO_BLING = :id");
            $stmtCheckContato->execute(['id' => $id]);
            if (!$stmtCheckContato->fetch()) continue;

            $stmtIsRep->execute(['id' => $id]);
            $isRep = (bool)$stmtIsRep->fetch();

            $telefones = [];
            if (!empty($c['telefone'])) $telefones[] = $this->limparTelefone($c['telefone']);
            if (!empty($c['celular']))  $telefones[] = $this->limparTelefone($c['celular']);

            foreach ($telefones as $num) {
                if (empty($num)) continue;
                $stmtTel->execute(['num' => $num, 'conf' => $isRep ? 1 : 0]);
                $isNovo = $stmtTel->rowCount() > 0;

                $stmtBusca->execute(['num' => $num]);
                $row = $stmtBusca->fetch();
                if ($row) {
                    // Regra: Cliente não pode ter telefone de representante
                    if (!$isRep && $this->isPhoneOwnedByRep($row['ID_TEL'])) {
                        continue;
                    }

                    $stmtVinculo->execute(['id_contato' => $id, 'id_tel' => $row['ID_TEL']]);
                    if ($isNovo) {
                        $novos[] = ['num' => $num, 'id_tel' => $row['ID_TEL'], 'id_contato' => $id];
                    }
                }
            }
        }
        return $novos;
    }

    public function importarRepresentantes(array $vendedores): array {
        $novos = [];
        $stmtInsertContato = $this->pdo->prepare("INSERT IGNORE INTO CONTATO_EXTERNO (ID_CONTATO_BLING, NOME_CONTATO) VALUES (:id, :nome)");
        $stmtInsert = $this->pdo->prepare("INSERT IGNORE INTO REPRESENTANTE (ID_CONTATO_BLING, ID_VENDEDOR) VALUES (:id_contato, :id_vendedor)");

        foreach ($vendedores as $v) {
            $idVendedor = $v['id'] ?? null;
            $idContato  = $v['contato']['id'] ?? null;
            $nome = $v['contato']['nome'] ?? '';
            if (!$idVendedor || !$idContato) continue;

            $stmtInsertContato->execute(['id' => $idContato, 'nome' => $nome]);
            $stmtInsert->execute(['id_contato' => $idContato, 'id_vendedor' => $idVendedor]);
            if ($stmtInsert->rowCount() > 0) {
                $novos[] = ['id_vendedor' => $idVendedor, 'id_contato' => $idContato, 'nome' => $nome];
            }
        }
        return $novos;
    }

    public function importarClientes(): int {
        return (int)$this->pdo->exec(
            "INSERT IGNORE INTO CLIENTE (ID_CONTATO_BLING)
             SELECT ce.ID_CONTATO_BLING FROM CONTATO_EXTERNO ce
             LEFT JOIN REPRESENTANTE r ON r.ID_CONTATO_BLING = ce.ID_CONTATO_BLING
             WHERE r.ID_CONTATO_BLING IS NULL"
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CONSULTAS
    // ═══════════════════════════════════════════════════════════════════════════

    public function getClientesComTelefones(bool $somenteComTelefone = false, bool $somenteConfirmados = false, bool $somenteTentativas = false, bool $inadimplentes = false): array {
        $joinType = ($somenteComTelefone || $somenteConfirmados || $somenteTentativas) ? "JOIN" : "LEFT JOIN";
        $whereConfirmado = "";
        if ($somenteConfirmados && !$somenteTentativas) $whereConfirmado = " AND t.CONFIRMADO = 1";
        if (!$somenteConfirmados && $somenteTentativas) $whereConfirmado = " AND t.CONFIRMADO = 0";
        $whereInadimplente = "";
        if ($inadimplentes) {
            $pedidoRepo = new \App\Repositories\PedidoRepository();
            $resumo = $pedidoRepo->getResumoClientes('inadimplentes');
            $ids = array_filter(array_column($resumo, 'ID_CONTATO_BLING'));
            if (empty($ids)) {
                $whereInadimplente = " AND 1=0";
            } else {
                $idsList = implode(',', array_map(function($id) { return "'" . addslashes($id) . "'"; }, $ids));
                $whereInadimplente = " AND ce.ID_CONTATO_BLING IN ($idsList)";
            }
        }
        $stmt = $this->pdo->query(
            "SELECT ce.ID_CONTATO_BLING, ce.NOME_CONTATO, ce.NUMERO_DOCUMENTO,
                    GROUP_CONCAT(DISTINCT CONCAT(t.ID_TEL, ':', t.NUM_TEL, ':', t.CONFIRMADO, ':', t.ORIGEM, ':', IFNULL(cc.NOME_COLABORADOR, ''), ':', IFNULL(ca.NOME_COLABORADOR, '')) SEPARATOR '|') AS telefones
             FROM CLIENTE c
             JOIN CONTATO_EXTERNO ce ON ce.ID_CONTATO_BLING = c.ID_CONTATO_BLING
             $joinType (
                 SELECT ct.ID_CONTATO_BLING, t.ID_TEL, t.NUM_TEL, t.CONFIRMADO, t.ORIGEM, t.ID_COLAB_CRIACAO, t.ID_COLAB_ALTERACAO 
                 FROM CONTATO_TEL ct JOIN TEL t ON t.ID_TEL = ct.ID_TEL
             ) t ON t.ID_CONTATO_BLING = ce.ID_CONTATO_BLING $whereConfirmado
             LEFT JOIN COLABORADOR cc ON cc.ID_COLABORADOR = t.ID_COLAB_CRIACAO
             LEFT JOIN COLABORADOR ca ON ca.ID_COLABORADOR = t.ID_COLAB_ALTERACAO
             WHERE c.EXIBIR = 1 AND c.PEDRAS = 0 $whereInadimplente
             GROUP BY ce.ID_CONTATO_BLING, ce.NOME_CONTATO, ce.NUMERO_DOCUMENTO
             ORDER BY ce.NOME_CONTATO"
        );
        return $this->parseTelefones($stmt->fetchAll());
    }

    public function getRepresentantesComTelefones(bool $somenteComTelefone = false, bool $somenteConfirmados = false, bool $somenteTentativas = false, bool $inadimplentes = false): array {
        $joinType = ($somenteComTelefone || $somenteConfirmados || $somenteTentativas) ? "JOIN" : "LEFT JOIN";
        $whereConfirmado = "";
        if ($somenteConfirmados && !$somenteTentativas) $whereConfirmado = " AND t.CONFIRMADO = 1";
        if (!$somenteConfirmados && $somenteTentativas) $whereConfirmado = " AND t.CONFIRMADO = 0";
        $whereInadimplente = "";
        if ($inadimplentes) {
            $pedidoRepo = new \App\Repositories\PedidoRepository();
            $resumo = $pedidoRepo->getResumoRepresentantes();
            $ids = array_filter(array_column($resumo, 'ID_CONTATO_BLING'));
            if (empty($ids)) {
                $whereInadimplente = " AND 1=0";
            } else {
                $idsList = implode(',', array_map(function($id) { return "'" . addslashes($id) . "'"; }, $ids));
                $whereInadimplente = " AND ce.ID_CONTATO_BLING IN ($idsList)";
            }
        }
        $stmt = $this->pdo->query(
            "SELECT ce.ID_CONTATO_BLING, ce.NOME_CONTATO, ce.NUMERO_DOCUMENTO, r.ID_VENDEDOR,
                    GROUP_CONCAT(DISTINCT CONCAT(t.ID_TEL, ':', t.NUM_TEL, ':', t.CONFIRMADO, ':', t.ORIGEM, ':', IFNULL(cc.NOME_COLABORADOR, ''), ':', IFNULL(ca.NOME_COLABORADOR, '')) SEPARATOR '|') AS telefones
             FROM REPRESENTANTE r
             JOIN CONTATO_EXTERNO ce ON ce.ID_CONTATO_BLING = r.ID_CONTATO_BLING
             $joinType (
                 SELECT ct.ID_CONTATO_BLING, t.ID_TEL, t.NUM_TEL, t.CONFIRMADO, t.ORIGEM, t.ID_COLAB_CRIACAO, t.ID_COLAB_ALTERACAO 
                 FROM CONTATO_TEL ct JOIN TEL t ON t.ID_TEL = ct.ID_TEL
             ) t ON t.ID_CONTATO_BLING = ce.ID_CONTATO_BLING $whereConfirmado
             LEFT JOIN COLABORADOR cc ON cc.ID_COLABORADOR = t.ID_COLAB_CRIACAO
             LEFT JOIN COLABORADOR ca ON ca.ID_COLABORADOR = t.ID_COLAB_ALTERACAO
             WHERE r.EXIBIR = 1 $whereInadimplente
             GROUP BY ce.ID_CONTATO_BLING, ce.NOME_CONTATO, ce.NUMERO_DOCUMENTO, r.ID_VENDEDOR
             ORDER BY ce.NOME_CONTATO"
        );
        return $this->parseTelefones($stmt->fetchAll());
    }

    public function getTelefonesPorContato($idContatoBling): array {
        $stmt = $this->pdo->prepare(
            "SELECT t.ID_TEL as id, t.NUM_TEL as num, t.CONFIRMADO as confirmado, t.ORIGEM as origem, 
                    cc.NOME_COLABORADOR as criado_por, ca.NOME_COLABORADOR as alterado_por
             FROM CONTATO_TEL ct
             JOIN TEL t ON t.ID_TEL = ct.ID_TEL
             LEFT JOIN COLABORADOR cc ON cc.ID_COLABORADOR = t.ID_COLAB_CRIACAO
             LEFT JOIN COLABORADOR ca ON ca.ID_COLABORADOR = t.ID_COLAB_ALTERACAO
             WHERE ct.ID_CONTATO_BLING = :id
             ORDER BY t.CONFIRMADO DESC, t.ID_TEL DESC"
        );
        $stmt->execute(['id' => $idContatoBling]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getIdContatoByTel($idTel) {
        $stmt = $this->pdo->prepare("SELECT ID_CONTATO_BLING FROM CONTATO_TEL WHERE ID_TEL = :id LIMIT 1");
        $stmt->execute(['id' => $idTel]);
        return $stmt->fetchColumn();
    }

    public function getClientesSemTelefone(bool $inadimplentes = false): array {
        $whereInadimplente = "";
        if ($inadimplentes) {
            $pedidoRepo = new \App\Repositories\PedidoRepository();
            $resumo = $pedidoRepo->getResumoClientes('inadimplentes');
            $ids = array_filter(array_column($resumo, 'ID_CONTATO_BLING'));
            if (empty($ids)) {
                $whereInadimplente = " AND 1=0";
            } else {
                $idsList = implode(',', array_map(function($id) { return "'" . addslashes($id) . "'"; }, $ids));
                $whereInadimplente = " AND ce.ID_CONTATO_BLING IN ($idsList)";
            }
        }
        $stmt = $this->pdo->query(
            "SELECT ce.ID_CONTATO_BLING, ce.NOME_CONTATO, ce.NUMERO_DOCUMENTO
             FROM CLIENTE c
             JOIN CONTATO_EXTERNO ce ON ce.ID_CONTATO_BLING = c.ID_CONTATO_BLING
             LEFT JOIN CONTATO_TEL ct ON ct.ID_CONTATO_BLING = ce.ID_CONTATO_BLING
             WHERE c.EXIBIR = 1 AND c.PEDRAS = 0 AND ct.ID_TEL IS NULL $whereInadimplente
             ORDER BY ce.NOME_CONTATO"
        );
        return $stmt->fetchAll();
    }

    public function getClientesPedras(): array {
        $stmt = $this->pdo->query(
            "SELECT ce.ID_CONTATO_BLING, ce.NOME_CONTATO, ce.NUMERO_DOCUMENTO,
                    GROUP_CONCAT(DISTINCT CONCAT(t.ID_TEL, ':', t.NUM_TEL, ':', t.CONFIRMADO, ':', t.ORIGEM, ':', IFNULL(cc.NOME_COLABORADOR, ''), ':', IFNULL(ca.NOME_COLABORADOR, '')) SEPARATOR '|') AS telefones
             FROM CLIENTE c
             JOIN CONTATO_EXTERNO ce ON ce.ID_CONTATO_BLING = c.ID_CONTATO_BLING
             LEFT JOIN (
                 SELECT ct.ID_CONTATO_BLING, t.ID_TEL, t.NUM_TEL, t.CONFIRMADO, t.ORIGEM, t.ID_COLAB_CRIACAO, t.ID_COLAB_ALTERACAO 
                 FROM CONTATO_TEL ct JOIN TEL t ON t.ID_TEL = ct.ID_TEL
             ) t ON t.ID_CONTATO_BLING = ce.ID_CONTATO_BLING
             LEFT JOIN COLABORADOR cc ON cc.ID_COLABORADOR = t.ID_COLAB_CRIACAO
             LEFT JOIN COLABORADOR ca ON ca.ID_COLABORADOR = t.ID_COLAB_ALTERACAO
             WHERE c.EXIBIR = 1 AND c.PEDRAS = 1
             GROUP BY ce.ID_CONTATO_BLING, ce.NOME_CONTATO, ce.NUMERO_DOCUMENTO
             ORDER BY ce.NOME_CONTATO"
        );
        return $this->parseTelefones($stmt->fetchAll());
    }

    public function togglePedra($idContatoBling): void {
        $stmt = $this->pdo->prepare("UPDATE CLIENTE SET PEDRAS = IF(IFNULL(PEDRAS, 0) = 1, 0, 1) WHERE ID_CONTATO_BLING = :id");
        $stmt->execute(['id' => $idContatoBling]);
    }

    public function getContatosFinanceiros($idContatoBling): array {
        $stmt = $this->pdo->prepare(
            "SELECT cf.ID_CONTATO, cf.NOME_CONTATO, t.NUM_TEL, t.ID_TEL
             FROM CONTATO_FINANCEIRO cf
             JOIN TEL t ON t.ID_TEL = cf.ID_TEL
             LEFT JOIN VINCULO_CONTATO_CLIENTE vc ON vc.ID_CONTATO = cf.ID_CONTATO
             LEFT JOIN VINCULO_CONTATO_REPRESENTANTE vr ON vr.ID_CONTATO = cf.ID_CONTATO
             WHERE vc.ID_CLIENTE = :id1 OR vr.ID_REPRESENTANTE = :id2
             ORDER BY cf.NOME_CONTATO"
        );
        $stmt->execute(['id1' => $idContatoBling, 'id2' => $idContatoBling]);
        return $stmt->fetchAll();
    }

    /**
     * Lista TODOS os contatos financeiros com seus vínculos.
     */
    public function getAllContatosFinanceiros(bool $somenteConfirmados = false, bool $somenteTentativas = false): array {
        $whereConfirmado = "";
        if ($somenteConfirmados && !$somenteTentativas) $whereConfirmado = " AND t.CONFIRMADO = 1";
        if (!$somenteConfirmados && $somenteTentativas) $whereConfirmado = " AND t.CONFIRMADO = 0";
        $stmt = $this->pdo->query(
            "SELECT cf.ID_CONTATO, cf.NOME_CONTATO AS NOME_CF, t.NUM_TEL, t.ID_TEL,
                    GROUP_CONCAT(
                        DISTINCT CONCAT(
                            COALESCE(ce_c.NOME_CONTATO, ce_r.NOME_CONTATO),
                            ' [', IF(vc.ID_CLIENTE IS NOT NULL, 'Cliente', 'Representante'), ']'
                        ) SEPARATOR ', '
                    ) AS VINCULOS,
                    GROUP_CONCAT(
                        DISTINCT CONCAT(
                            COALESCE(ce_c.ID_CONTATO_BLING, ce_r.ID_CONTATO_BLING),
                            ':',
                            COALESCE(ce_c.NOME_CONTATO, ce_r.NOME_CONTATO),
                            ' [', IF(vc.ID_CLIENTE IS NOT NULL, 'Cliente', 'Representante'), ']'
                        ) SEPARATOR '|'
                    ) AS VINCULOS_RAW
             FROM CONTATO_FINANCEIRO cf
             JOIN TEL t ON t.ID_TEL = cf.ID_TEL $whereConfirmado
             LEFT JOIN VINCULO_CONTATO_CLIENTE vc ON vc.ID_CONTATO = cf.ID_CONTATO
             LEFT JOIN CONTATO_EXTERNO ce_c ON ce_c.ID_CONTATO_BLING = vc.ID_CLIENTE
             LEFT JOIN VINCULO_CONTATO_REPRESENTANTE vr ON vr.ID_CONTATO = cf.ID_CONTATO
             LEFT JOIN CONTATO_EXTERNO ce_r ON ce_r.ID_CONTATO_BLING = vr.ID_REPRESENTANTE
             GROUP BY cf.ID_CONTATO, cf.NOME_CONTATO, t.NUM_TEL, t.ID_TEL
             ORDER BY cf.NOME_CONTATO"
        );
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // AUTOCOMPLETE / BUSCA
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Busca contatos (clientes + representantes) por nome ou documento.
     */
    public function buscarContatos(string $termo, string $tipo = ''): array {
        $like = '%' . $termo . '%';
        $params = ['t1' => $like, 't2' => $like];
        $whereTipo = "";

        if ($tipo === 'cliente') {
            $whereTipo = " AND r.ID_CONTATO_BLING IS NULL";
        } elseif ($tipo === 'representante') {
            $whereTipo = " AND r.ID_CONTATO_BLING IS NOT NULL";
        }

        $stmt = $this->pdo->prepare(
            "SELECT ce.ID_CONTATO_BLING, ce.NOME_CONTATO, ce.NUMERO_DOCUMENTO,
                    IF(r.ID_CONTATO_BLING IS NOT NULL, 'Representante', 'Cliente') AS TIPO
             FROM CONTATO_EXTERNO ce
             LEFT JOIN REPRESENTANTE r ON r.ID_CONTATO_BLING = ce.ID_CONTATO_BLING
             WHERE (ce.NOME_CONTATO LIKE :t1 OR ce.NUMERO_DOCUMENTO LIKE :t2)
             $whereTipo
             ORDER BY ce.NOME_CONTATO
             LIMIT 20"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Busca telefones existentes por número.
     */
    public function buscarTelefones(string $termo): array {
        $like = '%' . $termo . '%';
        $stmt = $this->pdo->prepare(
            "SELECT t.ID_TEL, t.NUM_TEL
             FROM TEL t
             WHERE t.NUM_TEL LIKE :t
             ORDER BY t.NUM_TEL
             LIMIT 15"
        );
        $stmt->execute(['t' => $like]);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRUD TELEFONE
    // ═══════════════════════════════════════════════════════════════════════════

    public function adicionarTelefone($idContatoBling, $numTel): array {
        $numTel = $this->limparTelefone($numTel);
        if (empty($numTel)) return ['ok' => false, 'msg' => 'Número inválido ou vazio'];

        $stmtRep = $this->pdo->prepare("SELECT 1 FROM REPRESENTANTE WHERE ID_CONTATO_BLING = :id");
        $stmtRep->execute(['id' => $idContatoBling]);
        $confirmado = $stmtRep->fetch() ? 1 : 0;

        $this->pdo->prepare("INSERT IGNORE INTO TEL (NUM_TEL, CONFIRMADO, ORIGEM, ID_COLAB_CRIACAO) VALUES (:num, :conf, 'manual', :id_colab)")
            ->execute(['num' => $numTel, 'conf' => $confirmado, 'id_colab' => auth()->id()]);

        $stmtBusca = $this->pdo->prepare("SELECT ID_TEL FROM TEL WHERE NUM_TEL = :num");
        $stmtBusca->execute(['num' => $numTel]);
        $row = $stmtBusca->fetch();
        if (!$row) return ['ok' => false, 'msg' => 'Erro ao buscar telefone'];

        // Regra: Cliente não pode ter telefone de representante
        if ($confirmado === 0 && $this->isPhoneOwnedByRep($row['ID_TEL'])) {
            return ['ok' => false, 'msg' => 'Este número pertence a um representante e não pode ser vinculado a um cliente.'];
        }

        $this->pdo->prepare("INSERT IGNORE INTO CONTATO_TEL (ID_CONTATO_BLING, ID_TEL) VALUES (:id, :tel)")
            ->execute(['id' => $idContatoBling, 'tel' => $row['ID_TEL']]);

        return ['ok' => true, 'id_tel' => $row['ID_TEL']];
    }

    public function editarTelefone($idTel, $numTel): array {
        $numTel = $this->limparTelefone($numTel);
        if (empty($numTel)) return ['ok' => false, 'msg' => 'Número inválido ou vazio'];

        // Se estivermos editando um telefone que pertence a um cliente,
        // não podemos mudar para um número que já pertence a um representante
        $stmtIsRepTel = $this->pdo->prepare("
            SELECT 1 FROM CONTATO_TEL ct
            JOIN CLIENTE c ON ct.ID_CONTATO_BLING = c.ID_CONTATO_BLING
            WHERE ct.ID_TEL = :idTel
        ");
        $stmtIsRepTel->execute(['idTel' => $idTel]);
        $isLinkedToClient = (bool)$stmtIsRepTel->fetch();

        if ($isLinkedToClient) {
            // Verificar se o novo número já existe no banco e pertence a rep
            $stmtBusca = $this->pdo->prepare("SELECT ID_TEL FROM TEL WHERE NUM_TEL = :num");
            $stmtBusca->execute(['num' => $numTel]);
            $existente = $stmtBusca->fetch();
            
            if ($existente && $this->isPhoneOwnedByRep($existente['ID_TEL'])) {
                return ['ok' => false, 'msg' => 'Este número pertence a um representante. Não é possível vinculá-lo a um cliente.'];
            }
        }

        try {
            $this->pdo->prepare("UPDATE TEL SET NUM_TEL = :num, ID_COLAB_ALTERACAO = :id_colab WHERE ID_TEL = :id")
                ->execute(['num' => $numTel, 'id' => $idTel, 'id_colab' => auth()->id()]);
            return ['ok' => true];
        } catch (\PDOException $e) {
            return ['ok' => false, 'msg' => 'Número já existe'];
        }
    }

    public function excluirTelefone($idTel): array {
        try {
            $this->pdo->prepare("DELETE FROM CONTATO_FINANCEIRO WHERE ID_TEL = :id")->execute(['id' => $idTel]);
            $this->pdo->prepare("DELETE FROM CONTATO_TEL WHERE ID_TEL = :id")->execute(['id' => $idTel]);
            $this->pdo->prepare("DELETE FROM TEL WHERE ID_TEL = :id")->execute(['id' => $idTel]);
            return ['ok' => true];
        } catch (\PDOException $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function toggleConfirmado($idTel): array {
        $this->pdo->prepare("UPDATE TEL SET CONFIRMADO = NOT CONFIRMADO, ID_COLAB_ALTERACAO = :id_colab WHERE ID_TEL = :id")
            ->execute(['id' => $idTel, 'id_colab' => auth()->id()]);
        return ['ok' => true];
    }

    public function toggleOrigem($idTel): array {
        $this->pdo->prepare("UPDATE TEL SET ORIGEM = IF(ORIGEM = 'bling', 'manual', 'bling'), ID_COLAB_ALTERACAO = :id_colab WHERE ID_TEL = :id")
            ->execute(['id' => $idTel, 'id_colab' => auth()->id()]);
        return ['ok' => true];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRUD CONTATO FINANCEIRO
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Cria contato financeiro e vincula a múltiplos clientes/representantes.
     * @param string $nome Nome do contato
     * @param string $numTel Número do telefone (novo ou existente)
     * @param array $vinculos Array de IDs de CONTATO_EXTERNO para vincular
     */
    public function adicionarContatoFinanceiro(string $nome, string $numTel, array $vinculos): array {
        $nome = trim($nome);
        $numTel = $this->limparTelefone($numTel);
        if (empty($nome) || empty($numTel) || empty($vinculos)) {
            return ['ok' => false, 'msg' => 'Campos obrigatórios'];
        }

        // Inserir telefone se não existe
        $this->pdo->prepare("INSERT IGNORE INTO TEL (NUM_TEL, ID_COLAB_CRIACAO) VALUES (:num, :id_colab)")->execute(['num' => $numTel, 'id_colab' => auth()->id()]);
        $stmtBusca = $this->pdo->prepare("SELECT ID_TEL FROM TEL WHERE NUM_TEL = :num");
        $stmtBusca->execute(['num' => $numTel]);
        $tel = $stmtBusca->fetch();
        if (!$tel) return ['ok' => false, 'msg' => 'Erro ao inserir telefone'];

        // Força CONFIRMADO = 1 e ORIGEM = 'manual' sempre
        $this->pdo->prepare("UPDATE TEL SET CONFIRMADO = 1, ORIGEM = 'manual', ID_COLAB_ALTERACAO = :id_colab WHERE ID_TEL = :id")->execute(['id' => $tel['ID_TEL'], 'id_colab' => auth()->id()]);

        // Inserir contato financeiro
        $this->pdo->prepare("INSERT INTO CONTATO_FINANCEIRO (NOME_CONTATO, ID_TEL) VALUES (:nome, :tel)")
            ->execute(['nome' => $nome, 'tel' => $tel['ID_TEL']]);
        $idContato = $this->pdo->lastInsertId();

        // Vincular a cada contato externo selecionado
        $stmtRep = $this->pdo->prepare("SELECT 1 FROM REPRESENTANTE WHERE ID_CONTATO_BLING = :id");
        $stmtVincCli = $this->pdo->prepare("INSERT IGNORE INTO VINCULO_CONTATO_CLIENTE (ID_CONTATO, ID_CLIENTE) VALUES (:cf, :cli)");
        $stmtVincRep = $this->pdo->prepare("INSERT IGNORE INTO VINCULO_CONTATO_REPRESENTANTE (ID_CONTATO, ID_REPRESENTANTE) VALUES (:cf, :rep)");

        $stmtVincTel = $this->pdo->prepare("INSERT IGNORE INTO CONTATO_TEL (ID_CONTATO_BLING, ID_TEL) VALUES (:id_contato, :id_tel)");

        foreach ($vinculos as $idExt) {
            $stmtRep->execute(['id' => $idExt]);
            $isRepVinculo = (bool)$stmtRep->fetch();
            
            if ($isRepVinculo) {
                $stmtVincRep->execute(['cf' => $idContato, 'rep' => $idExt]);
            } else {
                $stmtVincCli->execute(['cf' => $idContato, 'cli' => $idExt]);
            }
            
            // Vincular o telefone automaticamente ao cliente/representante
            // Mas APENAS se o vínculo for de representante, ou se for de cliente e o telefone não pertencer a nenhum representante
            if ($isRepVinculo) {
                $stmtVincTel->execute(['id_contato' => $idExt, 'id_tel' => $tel['ID_TEL']]);
            } else {
                if (!$this->isPhoneOwnedByRep($tel['ID_TEL'])) {
                    $stmtVincTel->execute(['id_contato' => $idExt, 'id_tel' => $tel['ID_TEL']]);
                }
            }
        }

        return ['ok' => true, 'id' => $idContato];
    }

    public function editarContatoFinanceiro(int $idContato, string $nome, string $numTel, array $vinculos): array {
        $nome = trim($nome);
        $numTel = preg_replace('/\D/', '', trim($numTel));
        if (empty($nome) || empty($numTel)) {
            return ['ok' => false, 'msg' => 'Campos obrigatórios inválidos'];
        }

        // Lidar com o telefone
        $this->pdo->prepare("INSERT IGNORE INTO TEL (NUM_TEL) VALUES (:num)")->execute(['num' => $numTel]);
        $stmtBusca = $this->pdo->prepare("SELECT ID_TEL FROM TEL WHERE NUM_TEL = :num");
        $stmtBusca->execute(['num' => $numTel]);
        $tel = $stmtBusca->fetch();
        if (!$tel) return ['ok' => false, 'msg' => 'Erro ao inserir telefone'];

        // Força CONFIRMADO = 1 e ORIGEM = 'manual'
        $this->pdo->prepare("UPDATE TEL SET CONFIRMADO = 1, ORIGEM = 'manual' WHERE ID_TEL = :id")->execute(['id' => $tel['ID_TEL']]);

        // Atualizar contato financeiro
        $this->pdo->prepare("UPDATE CONTATO_FINANCEIRO SET NOME_CONTATO = :nome, ID_TEL = :tel WHERE ID_CONTATO = :id")
            ->execute(['nome' => $nome, 'tel' => $tel['ID_TEL'], 'id' => $idContato]);

        // Atualizar vínculos: limpa os antigos e recria
        $this->pdo->prepare("DELETE FROM VINCULO_CONTATO_CLIENTE WHERE ID_CONTATO = :id")->execute(['id' => $idContato]);
        $this->pdo->prepare("DELETE FROM VINCULO_CONTATO_REPRESENTANTE WHERE ID_CONTATO = :id")->execute(['id' => $idContato]);

        if (!empty($vinculos)) {
            $stmtRep = $this->pdo->prepare("SELECT 1 FROM REPRESENTANTE WHERE ID_CONTATO_BLING = :id");
            $stmtVincCli = $this->pdo->prepare("INSERT IGNORE INTO VINCULO_CONTATO_CLIENTE (ID_CONTATO, ID_CLIENTE) VALUES (:cf, :cli)");
            $stmtVincRep = $this->pdo->prepare("INSERT IGNORE INTO VINCULO_CONTATO_REPRESENTANTE (ID_CONTATO, ID_REPRESENTANTE) VALUES (:cf, :rep)");

            $stmtVincTel = $this->pdo->prepare("INSERT IGNORE INTO CONTATO_TEL (ID_CONTATO_BLING, ID_TEL) VALUES (:id_contato, :id_tel)");

            foreach ($vinculos as $idExt) {
                $stmtRep->execute(['id' => $idExt]);
                $isRepVinculo = (bool)$stmtRep->fetch();
                
                if ($isRepVinculo) {
                    $stmtVincRep->execute(['cf' => $idContato, 'rep' => $idExt]);
                } else {
                    $stmtVincCli->execute(['cf' => $idContato, 'cli' => $idExt]);
                }
                
                // Vincular o telefone automaticamente ao cliente/representante
                // Mas APENAS se o vínculo for de representante, ou se for de cliente e o telefone não pertencer a nenhum representante
                if ($isRepVinculo) {
                    $stmtVincTel->execute(['id_contato' => $idExt, 'id_tel' => $tel['ID_TEL']]);
                } else {
                    if (!$this->isPhoneOwnedByRep($tel['ID_TEL'])) {
                        $stmtVincTel->execute(['id_contato' => $idExt, 'id_tel' => $tel['ID_TEL']]);
                    }
                }
            }
        }

        return ['ok' => true];
    }

    public function excluirContatoFinanceiro($idContato): array {
        try {
            $this->pdo->prepare("DELETE FROM VINCULO_CONTATO_CLIENTE WHERE ID_CONTATO = :id")->execute(['id' => $idContato]);
            $this->pdo->prepare("DELETE FROM VINCULO_CONTATO_REPRESENTANTE WHERE ID_CONTATO = :id")->execute(['id' => $idContato]);
            $this->pdo->prepare("DELETE FROM CONTATO_FINANCEIRO WHERE ID_CONTATO = :id")->execute(['id' => $idContato]);
            return ['ok' => true];
        } catch (\PDOException $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    private function parseTelefones(array $rows): array {
        foreach ($rows as &$row) {
            $tels = [];
            if (!empty($row['telefones'])) {
                foreach (explode('|', $row['telefones']) as $item) {
                    $parts = explode(':', $item, 6);
                    if (count($parts) >= 4) {
                        $tels[] = [
                            'id' => $parts[0], 
                            'num' => $parts[1], 
                            'confirmado' => $parts[2], 
                            'origem' => $parts[3],
                            'criado_por' => $parts[4] ?? '',
                            'alterado_por' => $parts[5] ?? ''
                        ];
                    }
                }
            }
            $row['telefones_arr'] = $tels;
            unset($row['telefones']);
        }
        return $rows;
    }

    public function validarLinhaImportacao($linha) {
        $nome = trim($linha['nome']);
        $doc = preg_replace('/\D/', '', $linha['cpf_cnpj']);
        $telefone = $this->limparTelefone($linha['telefone']);
        $status = strtolower(trim($linha['status']));
        $confirmado = (strpos($status, 'confirmado') !== false) ? 1 : 0;
        
        $acao_contato = '';
        $acao_telefone = '';
        $erro = null;
        $idContatoEncontrado = null;

        if (empty($nome) && empty($doc)) {
            return ['status' => 'erro', 'mensagem' => 'Nome e Documento vazios'];
        }

        // Verifica contato
        $termoBusca = !empty($doc) ? $doc : $nome;
        $contatosEncontrados = $this->buscarContatos($termoBusca);
        if (!empty($contatosEncontrados)) {
            $idContatoEncontrado = $contatosEncontrados[0]['ID_CONTATO_BLING'];
            $acao_contato = 'Contato encontrado (' . ($contatosEncontrados[0]['NUMERO_DOCUMENTO'] ?: 'Sem doc') . ')';
        } else {
            return ['status' => 'erro', 'mensagem' => 'Cliente não encontrado no sistema. Só é possível vincular telefones a clientes vindos do Bling.'];
        }

        // Verifica telefone
        if (empty($telefone)) {
            return ['status' => 'erro', 'mensagem' => 'Nenhum telefone informado'];
        } else {
            $stmtBusca = $this->pdo->prepare("SELECT ID_TEL, CONFIRMADO FROM TEL WHERE NUM_TEL = :num");
            $stmtBusca->execute(['num' => $telefone]);
            $rowTel = $stmtBusca->fetch();

            if ($rowTel) {
                // Telefone existe
                $idTel = $rowTel['ID_TEL'];
                $statusAtual = (int)$rowTel['CONFIRMADO'];
                
                // Se contato existe, verifica se já está vinculado
                $jaVinculado = false;
                if ($idContatoEncontrado) {
                    $stmtLink = $this->pdo->prepare("SELECT 1 FROM CONTATO_TEL WHERE ID_CONTATO_BLING = :id AND ID_TEL = :tel");
                    $stmtLink->execute(['id' => $idContatoEncontrado, 'tel' => $idTel]);
                    if ($stmtLink->fetch()) $jaVinculado = true;
                }

                if ($this->isPhoneOwnedByRep($idTel) && !$jaVinculado) {
                    // Se tentar vincular a um novo cliente um tel de representante (a menos que a busca tenha retornado o próprio representante)
                    $isBuscadoRep = false;
                    if ($idContatoEncontrado) {
                        $stmtCheckRep = $this->pdo->prepare("SELECT 1 FROM REPRESENTANTE WHERE ID_CONTATO_BLING = :id");
                        $stmtCheckRep->execute(['id' => $idContatoEncontrado]);
                        if ($stmtCheckRep->fetch()) $isBuscadoRep = true;
                    }
                    if (!$isBuscadoRep) {
                        return ['status' => 'erro', 'mensagem' => 'Telefone pertence a um Representante e não pode ser vinculado'];
                    }
                }

                $msgsTel = [];
                if (!$jaVinculado) {
                    $msgsTel[] = "Vincular telefone existente";
                }
                
                if ($statusAtual !== $confirmado) {
                    $txtNovo = $confirmado ? 'Confirmado' : 'Tentativa';
                    $msgsTel[] = "Atualizar status para $txtNovo";
                }

                if (empty($msgsTel)) {
                    return ['status' => 'erro', 'mensagem' => 'Telefone já vinculado ao cliente com o mesmo status'];
                } else {
                    $acao_telefone = implode(' + ', $msgsTel);
                }
                
            } else {
                $acao_telefone = 'Criar e vincular novo telefone';
            }
        }

        return [
            'status' => 'ok',
            'nome' => $nome,
            'cpf_cnpj' => $doc,
            'telefone' => $telefone,
            'confirmado' => $confirmado,
            'acao_contato' => $acao_contato,
            'acao_telefone' => $acao_telefone
        ];
    }

    public function executarImportacaoValidada(array $linhas): array {
        $sucesso = 0;
        $erros = 0;
        $log = [];

        foreach ($linhas as $i => $linha) {
            $entry = [
                'linha' => $i + 1,
                'nome' => $linha['nome'] ?? '',
                'cpf_cnpj' => $linha['cpf_cnpj'] ?? '',
                'telefone' => $linha['telefone'] ?? '',
                'resultado' => '',
                'detalhes' => []
            ];

            if ($linha['status'] !== 'ok') {
                $erros++;
                $entry['resultado'] = 'erro';
                $entry['detalhes'][] = 'Ignorado: ' . ($linha['mensagem'] ?? 'Erro de validação');
                $log[] = $entry;
                continue;
            }

            $nome = $linha['nome'];
            $doc = $linha['cpf_cnpj'];
            $telefone = $linha['telefone'];
            $confirmado = (int)$linha['confirmado'];

            // Tenta buscar se já existe
            $termoBusca = !empty($doc) ? $doc : $nome;
            $contatosEncontrados = $this->buscarContatos($termoBusca);
            $idContatoBling = null;

            if (!empty($contatosEncontrados)) {
                $idContatoBling = $contatosEncontrados[0]['ID_CONTATO_BLING'];
                $entry['detalhes'][] = '✔ Contato encontrado no banco (ID: ' . $idContatoBling . ')';
            } else {
                $erros++;
                $entry['resultado'] = 'erro';
                $entry['detalhes'][] = '✘ Erro: Cliente não existe no sistema.';
                $log[] = $entry;
                continue;
            }

            // Adiciona/Atualiza o telefone
            if (!empty($telefone)) {
                $res = $this->adicionarTelefone($idContatoBling, $telefone);
                if ($res['ok']) {
                    $entry['detalhes'][] = '✔ Telefone vinculado (ID: ' . $res['id_tel'] . ')';
                    $this->pdo->prepare("UPDATE TEL SET CONFIRMADO = :conf WHERE ID_TEL = :idTel")
                              ->execute(['conf' => $confirmado, 'idTel' => $res['id_tel']]);
                    $statusTxt = $confirmado ? 'Confirmado' : 'Tentativa';
                    $entry['detalhes'][] = '✔ Status definido como: ' . $statusTxt;
                    $sucesso++;
                } else {
                    // Telefone pode já estar vinculado, mas precisamos atualizar status
                    $stmtTel = $this->pdo->prepare("SELECT ID_TEL, CONFIRMADO FROM TEL WHERE NUM_TEL = :num");
                    $stmtTel->execute(['num' => $telefone]);
                    $rowT = $stmtTel->fetch();
                    
                    if ($rowT) {
                        $statusAnterior = (int)$rowT['CONFIRMADO'];
                        if ($statusAnterior !== $confirmado) {
                            $this->pdo->prepare("UPDATE TEL SET CONFIRMADO = :conf WHERE ID_TEL = :idTel")
                                      ->execute(['conf' => $confirmado, 'idTel' => $rowT['ID_TEL']]);
                            $de = $statusAnterior ? 'Confirmado' : 'Tentativa';
                            $para = $confirmado ? 'Confirmado' : 'Tentativa';
                            $entry['detalhes'][] = "✔ Status atualizado: $de → $para";
                        } else {
                            $entry['detalhes'][] = '— Telefone já existia com mesmo status';
                        }
                        $entry['detalhes'][] = '— Telefone já vinculado ao contato';
                    } else {
                        $entry['detalhes'][] = '⚠ Telefone não pôde ser vinculado: ' . ($res['msg'] ?? '');
                    }
                    $sucesso++;
                }
            } else {
                $entry['detalhes'][] = '— Nenhum telefone informado';
                $sucesso++;
            }

            $entry['resultado'] = 'sucesso';
            $log[] = $entry;
        }

        return ['sucesso' => $sucesso, 'erros' => $erros, 'log' => $log];
    }
}
