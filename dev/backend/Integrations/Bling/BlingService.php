<?php
namespace App\Integrations\Bling;

class BlingService {
    private $pdo;
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    /** @var int Milissegundos entre requests para evitar throttling do Bling */
    private const API_DELAY_US = 200000; // 200ms

    public function __construct() {
        $this->pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
        $config = $this->getBlingConfig();

        $this->clientId = $config ? ($config['CLIENT_ID'] ?? null) : null;
        $this->clientSecret = $config ? ($config['CLIENT_SECRET'] ?? null) : null;
        $this->redirectUri = $config ? ($config['REDIRECT_URI'] ?? null) : null;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CONFIGURAÇÃO & AUTENTICAÇÃO
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Lê a configuração do Bling direto da tabela BLING_CONFIG
     */
    private function getBlingConfig() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM BLING_CONFIG ORDER BY ID DESC LIMIT 1");
            return $stmt->fetch();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Verifica se as credenciais básicas do Bling foram configuradas
     */
    public function isConfigured() {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->redirectUri);
    }

    /**
     * Retorna a URL de autorização do Bling OAuth2
     */
    public function getAuthorizationUrl($state = 'hydra') {
        return "https://www.bling.com.br/Api/v3/oauth/authorize?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state
        ]);
    }

    /**
     * Troca o código de autorização pelo token de acesso e refresh
     */
    public function handleCallback($code) {
        if (!$this->isConfigured()) {
            return false;
        }

        $url = 'https://api.bling.com.br/Api/v3/oauth/token';
        $credentials = base64_encode($this->clientId . ':' . $this->clientSecret);

        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $tokens = json_decode($response, true);
            if (isset($tokens['access_token'])) {
                $expiresAt = time() + ($tokens['expires_in'] ?? 86400) - 60;
                $this->saveTokens($tokens['access_token'], $tokens['refresh_token'] ?? null, $expiresAt);
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna um Access Token válido, renovando se necessário
     */
    public function getAccessToken() {
        if (!$this->isConfigured()) {
            return null;
        }

        $config = $this->getBlingConfig();
        if (!$config || empty($config['ACCESS_TOKEN'])) {
            return null;
        }

        // Se expirou, renova
        if (time() >= ($config['EXPIRES_AT'] ?? 0)) {
            if (!empty($config['REFRESH_TOKEN'])) {
                return $this->refreshToken($config['REFRESH_TOKEN']);
            }
            return null;
        }

        return $config['ACCESS_TOKEN'];
    }

    /**
     * Renova o token de acesso usando o refresh token
     */
    private function refreshToken($refreshToken) {
        $url = 'https://api.bling.com.br/Api/v3/oauth/token';
        $credentials = base64_encode($this->clientId . ':' . $this->clientSecret);

        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $tokens = json_decode($response, true);
            if (isset($tokens['access_token'])) {
                $expiresAt = time() + ($tokens['expires_in'] ?? 86400) - 60;
                $this->saveTokens($tokens['access_token'], $tokens['refresh_token'] ?? $refreshToken, $expiresAt);
                return $tokens['access_token'];
            }
        }

        return null;
    }

    /**
     * Salva os tokens na tabela BLING_CONFIG
     */
    private function saveTokens($accessToken, $refreshToken, $expiresAt) {
        $config = $this->getBlingConfig();
        if ($config) {
            $stmt = $this->pdo->prepare("UPDATE BLING_CONFIG SET 
                ACCESS_TOKEN = :at, REFRESH_TOKEN = :rt, EXPIRES_AT = :ea 
                WHERE ID = :id");
            $stmt->execute([
                'at' => $accessToken,
                'rt' => $refreshToken,
                'ea' => $expiresAt,
                'id' => $config['ID']
            ]);
        }
    }

    /**
     * Salva as credenciais na tabela BLING_CONFIG
     */
    public function saveConfig($clientId, $clientSecret, $redirectUri) {
        $config = $this->getBlingConfig();
        if ($config) {
            $stmt = $this->pdo->prepare("UPDATE BLING_CONFIG SET 
                CLIENT_ID = :cid, CLIENT_SECRET = :cs, REDIRECT_URI = :ru 
                WHERE ID = :id");
            return $stmt->execute([
                'cid' => $clientId, 'cs' => $clientSecret, 'ru' => $redirectUri,
                'id' => $config['ID']
            ]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO BLING_CONFIG 
                (CLIENT_ID, CLIENT_SECRET, REDIRECT_URI) VALUES (:cid, :cs, :ru)");
            return $stmt->execute([
                'cid' => $clientId, 'cs' => $clientSecret, 'ru' => $redirectUri
            ]);
        }
    }

    /**
     * Atualiza a data da última sincronização de contas
     */
    public function updateUltimaSincContas(string $date) {
        $config = $this->getBlingConfig();
        if ($config) {
            $stmt = $this->pdo->prepare("UPDATE BLING_CONFIG SET ULTIMA_SINC_CONTAS = :dt WHERE ID = :id");
            $stmt->execute(['dt' => $date, 'id' => $config['ID']]);
        }
    }

    /**
     * Retorna a data da última sincronização de contas (se houver)
     */
    public function getUltimaSincContas(): ?string {
        $config = $this->getBlingConfig();
        return $config ? ($config['ULTIMA_SINC_CONTAS'] ?? null) : null;
    }

    /**
     * Atualiza a data da última sincronização de contatos/vendedores
     */
    public function updateUltimaSincContatos(string $date) {
        $config = $this->getBlingConfig();
        if ($config) {
            try {
                $stmt = $this->pdo->prepare("UPDATE BLING_CONFIG SET ULTIMA_SINC_CONTATOS = :dt WHERE ID = :id");
                $stmt->execute(['dt' => $date, 'id' => $config['ID']]);
            } catch (\PDOException $e) {
                if (strpos($e->getMessage(), 'Unknown column') !== false && strpos($e->getMessage(), 'ULTIMA_SINC_CONTATOS') !== false) {
                    $this->pdo->exec("ALTER TABLE BLING_CONFIG ADD COLUMN ULTIMA_SINC_CONTATOS DATETIME NULL");
                    $stmt = $this->pdo->prepare("UPDATE BLING_CONFIG SET ULTIMA_SINC_CONTATOS = :dt WHERE ID = :id");
                    $stmt->execute(['dt' => $date, 'id' => $config['ID']]);
                }
            }
        }
    }

    /**
     * Retorna a data da última sincronização de contatos/vendedores (se houver)
     */
    public function getUltimaSincContatos(): ?string {
        try {
            $config = $this->getBlingConfig();
            return $config ? ($config['ULTIMA_SINC_CONTATOS'] ?? null) : null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // API GENÉRICA
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Executa uma requisição GET genérica à API v3 do Bling.
     * Suporta Exponential Backoff / Rate Limiter nativamente (Erro 429).
     */
    public function apiGet($endpoint, $query = [], $retryCount = 0) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['httpCode' => 0, 'data' => null, 'error' => 'Sem token de acesso válido'];
        }

        $url = 'https://api.bling.com.br/Api/v3/' . ltrim($endpoint, '/');
        if (!empty($query)) {
            $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            // Remove os índices numéricos dos arrays mantendo o URL encoding para [] (%5B%5D)
            $queryString = preg_replace('/%5B\d+%5D/i', '%5B%5D', $queryString);
            $url .= '?' . $queryString;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true); // Necessário para pegar o Retry-After
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Tratamento de Rate Limit (429) com Exponential Backoff
        if ($httpCode === 429 && $retryCount < 5) {
            $retryAfter = 1;
            if (preg_match('/Retry-After:\s*(\d+)/i', $headers, $matches)) {
                $retryAfter = (int)$matches[1];
            } else {
                $retryAfter = pow(2, $retryCount); // 1, 2, 4, 8, 16s...
            }
            sleep($retryAfter);
            return $this->apiGet($endpoint, $query, $retryCount + 1);
        }

        $decoded = json_decode($body, true);

        return [
            'httpCode' => $httpCode,
            'data' => $decoded,
            'raw' => $body,
            'url' => $url,
            'curlError' => $curlError ?: null
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CONTATOS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Busca TODOS os contatos do Bling paginando automaticamente.
     * @return array ['contatos' => [...], 'totalPaginas' => int, 'totalContatos' => int]
     */
    public function getAllContatos(string $dataAlteracaoInicial = null) {
        $todosContatos = [];
        $pagina = 1;
        $limite = 100;

        while (true) {
            $query = [
                'pagina'  => $pagina,
                'limite'  => $limite,
                'criterio' => 1
            ];

            if ($dataAlteracaoInicial) {
                // Formato exigido pela API v3: YYYY-MM-DD hh:mm:ss
                $query['dataAlteracaoInicial'] = date('Y-m-d H:i:s', strtotime($dataAlteracaoInicial));
            }

            $result = $this->apiGet('contatos', $query);

            // Se houve erro na requisição ou não veio data, para
            if ($result['httpCode'] !== 200 || !isset($result['data']['data'])) {
                break;
            }

            $contatos = $result['data']['data'];

            // Se a lista veio vazia, acabaram os registros
            if (empty($contatos)) {
                break;
            }

            $todosContatos = array_merge($todosContatos, $contatos);
            $pagina++;

            // Segurança: se retornou menos que o limite, é a última página
            if (count($contatos) < $limite) {
                break;
            }

            usleep(self::API_DELAY_US);
        }

        return [
            'contatos'       => $todosContatos,
            'totalPaginas'   => $pagina - 1,
            'totalContatos'  => count($todosContatos)
        ];
    }

    /**
     * Busca um contato específico pelo ID do Bling.
     *
     * @param int $idContato ID do contato no Bling
     * @return array|null Dados do contato ou null se não encontrado
     */
    public function getContato(int $idContato): ?array {
        $result = $this->apiGet("contatos/{$idContato}");

        if ($result['httpCode'] === 200 && isset($result['data']['data'])) {
            return $result['data']['data'];
        }

        return null;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // VENDEDORES
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Busca TODOS os vendedores ativos do Bling paginando automaticamente.
     * @return array ['vendedores' => [...], 'totalPaginas' => int, 'totalVendedores' => int]
     */
    public function getAllVendedores(string $dataAlteracaoInicial = null) {
        $todosVendedores = [];
        $pagina = 1;
        $limite = 100;

        while (true) {
            $query = [
                'pagina'          => $pagina,
                'limite'          => $limite,
                'situacaoContato' => 'A'
            ];

            if ($dataAlteracaoInicial) {
                // Formato exigido pela API v3: YYYY-MM-DD hh:mm:ss
                $query['dataAlteracaoInicial'] = date('Y-m-d H:i:s', strtotime($dataAlteracaoInicial));
            }

            $result = $this->apiGet('vendedores', $query);

            if ($result['httpCode'] !== 200 || !isset($result['data']['data'])) {
                break;
            }

            $vendedores = $result['data']['data'];

            if (empty($vendedores)) {
                break;
            }

            $todosVendedores = array_merge($todosVendedores, $vendedores);
            $pagina++;

            if (count($vendedores) < $limite) {
                break;
            }

            usleep(self::API_DELAY_US);
        }

        return [
            'vendedores'      => $todosVendedores,
            'totalPaginas'    => $pagina - 1,
            'totalVendedores' => count($todosVendedores)
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CONTAS A RECEBER — Algoritmo de Paginação por Blocos de Data
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Busca TODAS as contas a receber do Bling usando o algoritmo de
     * paginação por blocos de data:
     *
     * 1. Data absoluta inicial: 2025-01-01
     * 2. Cada bloco cobre no máximo 365 dias (dataInicial → dataFinal)
     * 3. Dentro de cada bloco, pagina com limite=100
     * 4. Ao esgotar as páginas do bloco, avança dataInicial para o dia
     *    seguinte ao dataFinal do bloco anterior
     * 5. O dataFinal máximo absoluto é sempre ONTEM (data atual - 1 dia)
     * 6. Filtra situações: 1 (aberto) e 3 (parcialmente recebido)
     * 7. Tipo de filtro de data: V (vencimento)
     */
    public function getAllContasReceber(string $dataAlteracaoInicial = null): array {
        $limite = 100;
        $todasContas = [];
        $log = [];

        // Define a data inicial baseada na última sincronização (se houver) ou no limite fixo
        if ($dataAlteracaoInicial) {
            $dataInicialAbsoluta = date('Y-m-d', strtotime($dataAlteracaoInicial));
            $tipoFiltro = 'V'; // Padrão: Sempre iterar por Vencimento para garantir previsibilidade (ignora o A inexistente)
        } else {
            $dataInicialAbsoluta = config('hydra.bling.data_corte_full_sync', '2025-01-01');
            $tipoFiltro = 'V'; // Padrão: Vencimento para carga completa
        }
        
        $dataFinalAbsoluta = date('Y-m-d'); // Para pegar as alterações até hoje
        $maxIntervaloDias = (int) config('hydra.bling.max_intervalo_dias', 365);
        
        $totalBlocos = 0;
        $dataInicial = $dataInicialAbsoluta;

        while ($dataInicial <= $dataFinalAbsoluta) {
            $totalBlocos++;

            $dataFinalBlocoObj = (new \DateTime($dataInicial))->modify("+{$maxIntervaloDias} days");
            $dataFinalAbsObj = new \DateTime($dataFinalAbsoluta);
            $dataFinalBloco = ($dataFinalBlocoObj > $dataFinalAbsObj) ? $dataFinalAbsoluta : $dataFinalBlocoObj->format('Y-m-d');

            $contasBlocoTotal = 0;
            $pagina = 1;

            $logBloco = [
                'bloco'       => $totalBlocos,
                'dataInicial' => $dataInicial,
                'dataFinal'   => $dataFinalBloco,
                'paginas'     => 0,
                'registros'   => 0
            ];

            while (true) {
                $query = [
                    'pagina'         => $pagina,
                    'limite'         => $limite,
                    'tipoFiltroData' => $tipoFiltro,
                    'dataInicial'    => $dataInicial,
                    'dataFinal'      => $dataFinalBloco,
                ];

                // Em full sync (Vencimento), pega apenas abertas/parciais para economizar requisições.
                // Em delta sync (Alteração), pega TODAS as situações para atualizar as que foram pagas/canceladas.
                if (!$dataAlteracaoInicial) {
                    $query['situacoes'] = [1, 3];
                }

                $result = $this->apiGet('contas/receber', $query);

                if ($result['httpCode'] !== 200 || !isset($result['data']['data'])) {
                    $logBloco['erro'] = 'HTTP ' . $result['httpCode'] . ' na página ' . $pagina;
                    break;
                }

                $contas = $result['data']['data'];
                if (empty($contas)) break;

                $todasContas = array_merge($todasContas, $contas);
                $contasBlocoTotal += count($contas);
                $pagina++;

                // A quebra de paginação deve usar o count() original da API, para não quebrar o loop prematuramente
                if (count($contas) < $limite) break;
                usleep(self::API_DELAY_US);
            }

            $logBloco['paginas'] = $pagina - 1;
            $logBloco['registros'] = $contasBlocoTotal;
            $log[] = $logBloco;

            $dataInicial = (new \DateTime($dataFinalBloco))->modify('+1 day')->format('Y-m-d');
        }

        return [
            'contas'      => $todasContas,
            'totalBlocos' => $totalBlocos,
            'totalContas' => count($todasContas),
            'log'         => $log,
            'mode'        => $dataAlteracaoInicial ? 'delta_sync' : 'full_sync'
        ];
    }

    /**
     * Busca uma conta a receber específica pelo ID do Bling.
     *
     * @param int $idContaReceber ID da conta no Bling
     * @return array|null Dados da conta ou null se não encontrada
     */
    public function getContaReceber(int $idContaReceber): ?array {
        $result = $this->apiGet("contas/receber/{$idContaReceber}");

        if ($result['httpCode'] === 200 && isset($result['data']['data'])) {
            return $result['data']['data'];
        }

        return null;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // UTILIDADES
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Verifica se existe conexão válida
     */
    public function isConnected() {
        return $this->getAccessToken() !== null;
    }
}
