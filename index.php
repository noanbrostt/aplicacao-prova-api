<?php
// Author: Noan Caliel Brostt (123036)
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=utf-8');

// Variável que diz qual ação será feita pela API
if (isset($_REQUEST['acao'])) {
    $acao = $_REQUEST['acao'];
} else {
    echo json_encode("Erro nos parâmetros!", JSON_UNESCAPED_UNICODE);
    exit;
}


$host = '172.10.20.47'; // Endereço do servidor PostgreSQL
$dbname = 'bd_rh_rs'; // Nome do banco de dados PostgreSQL
$user = 'usr_portal'; // Nome de usuário do PostgreSQL
$password = 'ps_PortalPlansul'; // Senha do PostgreSQL

try {
    // Cria uma conexão PDO com o banco de dados PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    
    // Configura para lançar exceções em caso de erros
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Define o charset para UTF-8 (opcional)
    $pdo->exec("SET CLIENT_ENCODING TO 'UTF8'");
    
    // echo "Conexão bem-sucedida!";
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

try {
    // Separa as diferentes coisas que a API faz
    switch ($acao) {

        case 1: // Usuário logando no site

            // Parâmetros esperados:
            $matricula = $_REQUEST['matricula'];
            $cpf = $_REQUEST['cpf'];

            // Select no banco de dados
            $sql = "SELECT 	EM.nome
                            ,EM.filial
                            ,EM.co_funcao
                            ,AD.ic_status
                        FROM public.tb_empregados EM
                        LEFT JOIN sc_psi_prova.tb_admins AD
                            ON CAST(coalesce(AD.co_matricula, '0') AS integer) = EM.matricula
                        WHERE dtdemissao IS NULL
                            AND matricula = '$matricula'
                            AND cpf = '$cpf'";

            $stmt = $pdo->query($sql);

            // Usuário não encontrado
            if ($stmt->rowCount() == 0) {
                // Select no banco de dados
                $sql = "SELECT 	nome
                                ,filial
                                ,co_funcao
                            FROM public.tb_empregados
                            WHERE dtdemissao IS NULL
                                AND matricula = '$matricula'";

                $stmt = $pdo->query($sql);

                if ($stmt->rowCount() == 0) {
                    echo json_encode(["Erro", "Matrícula não encontrada!"], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(["Erro", "O CPF não condiz com a matrícula!"], JSON_UNESCAPED_UNICODE);
                }
                return;
            }

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($results, JSON_UNESCAPED_UNICODE);
            return;

            break;

        
        case 2: // Carregar tela "lista_provas"

            // Parâmetros esperados:
            $matricula = $_REQUEST['matricula'];

            // Select no banco de dados
            $sql = "SELECT  PR.co_prova
                            ,PR.co_processo
                            ,PR.co_fila
                            ,PR.no_prova
                            ,PR.ic_status
                            ,(CASE
                                WHEN RE.dh_fim IS NULL THEN 'Prova Pendente'
                                ELSE 'Prova Finalizada'
                            END) AS status_prova
                        FROM sc_psi_prova.tb_prova PR
                        LEFT JOIN sc_psi_prova.tb_prova_respondida RE
                            ON RE.co_prova = PR.co_prova
                            AND RE.matricula = '$matricula'
                        WHERE PR.co_fila IN (
                            SELECT CASE WHEN sac_geral = 1 THEN 1 END FROM sc_psi.tb_regua_oficial WHERE mat_PLANSUL = '$matricula' UNION ALL
                            SELECT CASE WHEN hab = 1 THEN 2  END FROM sc_psi.tb_regua_oficial WHERE mat_PLANSUL = '$matricula' UNION ALL
                            SELECT CASE WHEN cxa_geral = 1 THEN 3 END FROM sc_psi.tb_regua_oficial WHERE mat_PLANSUL = '$matricula' UNION ALL
                            SELECT CASE WHEN reneg = 1 THEN 4 end FROM sc_psi.tb_regua_oficial WHERE mat_PLANSUL = '$matricula' UNION ALL
                            SELECT CASE WHEN cid_fgts = 1 THEN 5 END FROM sc_psi.tb_regua_oficial WHERE mat_PLANSUL = '$matricula' UNION ALL
                            SELECT CASE WHEN ste_canais = 1 THEN 6 END FROM sc_psi.tb_regua_oficial WHERE mat_PLANSUL = '$matricula' UNION ALL
                            SELECT CASE WHEN ste_ibc = 1 THEN 7 END FROM sc_psi.tb_regua_oficial WHERE mat_PLANSUL = '$matricula' UNION ALL
                            SELECT CASE WHEN cid_bolsas = 1 THEN 8 END FROM sc_psi.tb_regua_oficial WHERE mat_PLANSUL = '$matricula' UNION ALL
                            SELECT CASE WHEN comercial = 1 THEN 9 END FROM sc_psi.tb_regua_oficial WHERE mat_PLANSUL = '$matricula' UNION ALL
                            SELECT CASE WHEN retaguarda = 1 THEN 10 END FROM sc_psi.tb_regua_oficial WHERE mat_PLANSUL = '$matricula'	
                        )
                        AND PR.ic_status = 1";

            $stmt = $pdo->query($sql);

            if ($stmt->rowCount() == 0) {
                echo json_encode("Sem provas", JSON_UNESCAPED_UNICODE);
                return;
            }

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($results, JSON_UNESCAPED_UNICODE);
            return;

            break;

        
        case 3: // Começar prova (se ainda não foi iniciada) e carregar questôes da prova

            // Parâmetros esperados:
            $prova = $_REQUEST['prova'];

            // Select das questôes da prova
            $sql = "SELECT  PR.co_prova
                            ,PR.no_prova
                            ,PE.co_pergunta
                            ,PE.de_pergunta
                            ,STRING_AGG(AL.co_alternativa||':-D'||AL.de_alternativa, ';.;') AS alternativas
                        FROM sc_psi_prova.tb_prova PR
                        INNER JOIN sc_psi_prova.tb_pergunta PE
                            ON PE.co_prova = PR.co_prova
                            AND PE.ic_status = '1'
                        INNER JOIN sc_psi_prova.tb_alternativa AL
                            ON AL.co_pergunta = PE.co_pergunta
                            AND AL.ic_status = '1'
                        
                        WHERE PR.co_prova = '$prova'
                            AND PR.ic_status = '1'

                        GROUP BY PR.co_prova
                                ,PR.no_prova
                                ,PE.co_pergunta
                                ,PE.de_pergunta";

                $stmt = $pdo->query($sql);

                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode($results, JSON_UNESCAPED_UNICODE);
                return;

                break;

        
        case 4: // Finalizar prova

            // Parâmetros esperados:
            $co_prova = $_REQUEST['co_prova'];
            $co_pergunta = $_REQUEST['co_pergunta'];
            $co_alternativa = $_REQUEST['co_alternativa'];
            $co_matricula = $_REQUEST['co_matricula'];
            $dh_resposta = $_REQUEST['dh_resposta'];

            // Prepara a consulta SQL com placeholders para evitar SQL injection
            $sql = "INSERT INTO sc_psi_prova.tb_resposta
                        (co_prova, co_pergunta, co_alternativa, co_matricula, dh_resposta)
                    VALUES
                        (:co_prova, :co_pergunta, :co_alternativa, :co_matricula, :dh_resposta)";

            // Prepara a declaração
            $stmt = $pdo->prepare($sql);

            // Vincula os valores
            $stmt->bindParam(':co_prova', $co_prova);
            $stmt->bindParam(':co_pergunta', $co_pergunta);
            $stmt->bindParam(':co_alternativa', $co_alternativa);
            $stmt->bindParam(':co_matricula', $co_matricula);
            $stmt->bindParam(':dh_resposta', $dh_resposta);

            // Executa a declaração
            if (!$stmt->execute()) {
                echo json_encode("Erro na inserção ".pg_last_error($pdo), JSON_UNESCAPED_UNICODE);
            }

            break;


        case 5: // Chamar a procedure para atualizar as provas pendentes/finalizadas 

            // Parâmetros esperados:
            $matricula = $_REQUEST['matricula'];

            // Prepara a chamada da procedure
            $stmt = $pdo->prepare("CALL sc_psi_prova.sp_alimenta_prova_resposta(:matriculausuario)");

            // Vincula o parâmetro
            $stmt->bindParam(':matriculausuario', $matricula, PDO::PARAM_STR);
        
            // Executa a procedure
            $stmt->execute();
        
            echo json_encode("Procedure chamada com sucesso!", JSON_UNESCAPED_UNICODE);

            break;

        case 6: // Consulta tela de Relatório Geral 

            // Parâmetros esperados:
            $data_inicio = $_REQUEST['data_inicio'];
            $data_fim = $_REQUEST['data_fim'];
            // $prova = $_REQUEST['prova'];

            // Select das questôes da prova
            $sql = "SELECT 	RE.matricula
                            ,EM.nome
                            ,PR.no_prova AS nome_da_prova
                            ,RE.nu_acertos + RE.nu_erros AS qtde_questoes
                            ,RE.nu_acertos
                            ,RE.nu_erros
                            ,CONCAT(CEIL((RE.nu_acertos::FLOAT / (RE.nu_acertos + RE.nu_erros) * 100)), '%') AS acertos
                            , EXTRACT(HOUR FROM RE.dh_fim - RE.dh_inicio) || 'h ' ||
                                EXTRACT(MINUTE FROM RE.dh_fim - RE.dh_inicio) || 'm ' ||
                                CEIL(EXTRACT(SECOND FROM RE.dh_fim - RE.dh_inicio)) || 's' AS tempo_de_prova
                            ,TO_CHAR(RE.dh_fim, 'DD/MM/YYYY') AS dia_da_prova

                        FROM sc_psi_prova.tb_prova_respondida RE
                        INNER JOIN sc_psi_prova.tb_prova PR
                            ON PR.co_prova = RE.co_prova
                        INNER JOIN public.tb_empregados EM
                            ON EM.matricula = CAST(RE.matricula AS INTEGER)
                        WHERE RE.dh_fim BETWEEN '$data_inicio' AND '$data_fim'";

                $stmt = $pdo->query($sql);

                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode($results, JSON_UNESCAPED_UNICODE);

                break;


        case 7: // Consulta tela de Habilitar Admin 

            $sql = "SELECT  co_admin
                            ,co_matricula
                            ,nome
                            ,TO_CHAR(dt_cadastro, 'DD/MM/YYYY') AS dt_cadastro
                    FROM sc_psi_prova.tb_admins
                    WHERE ic_status = 1";

            $stmt = $pdo->query($sql);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($results, JSON_UNESCAPED_UNICODE);

            break;


        case 8: // Deleta um Admin 

            // Parâmetros esperados:
            $co_matricula = $_REQUEST['co_matricula'];

            // Prepara a consulta SQL com placeholders para evitar SQL injection
            $sql = "DELETE FROM sc_psi_prova.tb_admins
                    WHERE co_matricula = :co_matricula";

            // Prepara a declaração
            $stmt = $pdo->prepare($sql);

            // Vincula os valores
            $stmt->bindParam(':co_matricula', $co_matricula);

            // Executa a declaração
            if (!$stmt->execute()) {
                echo json_encode("Erro no delete ".pg_last_error($pdo), JSON_UNESCAPED_UNICODE);
            }
            
            break;


        case 9: // Busca o nome da matricula fornecida

            // Parâmetros esperados:
            $matricula = $_REQUEST['matricula'];

            $sql = "SELECT nome
                    FROM public.tb_empregados
                    WHERE matricula = '$matricula'";

            $stmt = $pdo->query($sql);

            if ($stmt->rowCount() == 0) {
                echo json_encode("Matrícula não encontrada", JSON_UNESCAPED_UNICODE);
                return;
            }

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($results, JSON_UNESCAPED_UNICODE);
            
            
            break;


        case 10: // Adiciona um novo Admin

            // Parâmetros esperados:
            $co_matricula = $_REQUEST['co_matricula'];
            $nome = strtoupper($_REQUEST['nome']);

            // Prepara a consulta SQL com placeholders para evitar SQL injection
            $sql = "INSERT INTO sc_psi_prova.tb_admins
                        (co_matricula, nome, dt_cadastro, ic_status)
                    VALUES
                        (:co_matricula, :nome, now(), 1)";

            // Prepara a declaração
            $stmt = $pdo->prepare($sql);

            // Vincula os valores
            $stmt->bindParam(':co_matricula', $co_matricula);
            $stmt->bindParam(':nome', $nome);

            // Executa a declaração
            if (!$stmt->execute()) {
                echo json_encode("Erro na inserção ".pg_last_error($pdo), JSON_UNESCAPED_UNICODE);
            }
            
            
            break;

        case 11: // Traz a prova respondida do usuário

            // Parâmetros esperados:
            $co_matricula = $_REQUEST['co_matricula'];
            $nome_da_prova = strtoupper($_REQUEST['nome_da_prova']);

            // Select das questôes da prova
            $sql = "SELECT 	
                    RE.matricula as 'Matricula Plansul'
                    ,EM.nome as 'Nome Empregado'
                    ,PR.no_prova AS 'Nome da Prova' 
                    ,pgt.de_pergunta as 'Pergunta'
                    ,(SELECT STRING_AGG(AL.co_alternativa||':-D'||AL.de_alternativa, ';.;') FROM sc_psi_prova.tb_alternativa AL WHERE AL.co_pergunta = pgt.co_pergunta AND ic_status = 1) AS alternativas
                    ,UPPER(alt.no_alternativa) as 'Resposta Escolhida'
                    ,(select STRING_AGG(UPPER(no_alternativa), ', ') from sc_psi_prova.tb_alternativa alt2 WHERE alt2.co_pergunta = pgt.co_pergunta and alt2.ic_correto = 1 limit 1) as 'Resposta Correta'
                    ,CASE 
                        WHEN 
                            (select STRING_AGG(UPPER(no_alternativa), ', ') from sc_psi_prova.tb_alternativa alt2 WHERE alt2.co_pergunta = pgt.co_pergunta and alt2.ic_correto = 1 limit 1) LIKE CONCAT('%',UPPER(alt.no_alternativa),'%')
                            THEN 1
                            ELSE 0
                        END as 'Acertou'
                        
                FROM sc_psi_prova.tb_prova_respondida RE

                INNER JOIN sc_psi_prova.tb_prova PR
                    ON PR.co_prova = RE.co_prova
                    AND PR.ic_status = 1

                INNER JOIN public.tb_empregados EM
                    ON EM.matricula = RE.matricula::INTEGER
                    
                INNER JOIN sc_psi_prova.tb_resposta resp
                    ON resp.co_prova = PR.co_prova
                    AND resp.co_matricula = RE.matricula 

                INNER JOIN sc_psi_prova.tb_pergunta pgt
                    ON pgt.co_prova = RE.co_prova
                    AND pgt.co_pergunta = resp.co_pergunta
                    AND pgt.ic_status = PR.ic_status

                INNER JOIN sc_psi_prova.tb_alternativa alt
                    ON alt.co_pergunta = pgt.co_pergunta
                    AND alt.ic_status = pgt.ic_status 
                    AND alt.co_alternativa = resp.co_alternativa

                WHERE RE.matricula = '$co_matricula'
                    AND PR.no_prova = '$nome_da_prova'

                ORDER BY pgt.co_pergunta ASC";

            $stmt = $pdo->query($sql);

            if ($stmt->rowCount() == 0) {
                echo json_encode("Prova não encontrada", JSON_UNESCAPED_UNICODE);
                return;
            }

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($results, JSON_UNESCAPED_UNICODE);
            
            break;
                
    }

} catch (PDOException $e) {
    echo json_encode('Erro : ' . $e->getMessage(), JSON_UNESCAPED_UNICODE);
}

?>