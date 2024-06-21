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

// Separa as diferentes coisas que a API faz
switch ($acao) {

    case 1: // Usuário logando no site

        try {
            // Parâmetros esperados:
            $matricula = $_REQUEST['matricula'];
            $cpf = $_REQUEST['cpf'];

            // Select no banco de dados
            $sql = "SELECT 	nome
                            ,filial
                            ,co_funcao
                        FROM public.tb_empregados
                        WHERE dtdemissao IS NULL
                            AND matricula = '$matricula'
                            AND cpf = '$cpf'";

            $stmt = $pdo->query($sql);

            if ($stmt->rowCount() == 0) {
                echo json_encode("Usuário não encontrado", JSON_UNESCAPED_UNICODE);
                return;
            }

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($results, JSON_UNESCAPED_UNICODE);
            return;

        } catch (PDOException $e) {
            die("Erro 1: " . $e->getMessage());
        }

        break;

    
    case 2: // Carregar tela "lista_provas"

        try {
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

        } catch (PDOException $e) {
            die("Erro 2: " . $e->getMessage());
        }

        break;

    
    case 3: // Começar prova (se ainda não foi iniciada) e carregar questôes da prova

        try {
            // Parâmetros esperados:
            // $matricula = $_REQUEST['matricula'];
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

            } catch (PDOException $e) {
                die("Erro 3: " . $e->getMessage());
            }

            break;

    
    case 4: // Finalizar prova

            // // Select para checar se a prova ja foi iniciada
            // $sql = "SELECT co_matricula
            //             FROM sc_psi_prova.tb_resposta
            //             WHERE co_matricula = '$matricula'
            //                 AND co_prova = '$prova'";
            // $stmt = $pdo->query($sql);
            // if ($stmt->rowCount() == 0) {
            // }

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
                echo "Erro na inserção ".pg_last_error($pdo);
            }

        break;


    case 5: // Chamar a procedure para atualizar as provas pendentes/finalizadas 

          // Parâmetros esperados:
          $matricula = $_REQUEST['matricula'];

          try {
            // Prepara a chamada da procedure
            $stmt = $pdo->prepare("CALL sc_psi_prova.sp_alimenta_prova_resposta(:matriculausuario)");

            // Vincula o parâmetro
            $stmt->bindParam(':matriculausuario', $matricula, PDO::PARAM_STR);
        
            // Executa a procedure
            $stmt->execute();
        
            echo "Procedure chamada com sucesso!";
        } catch (PDOException $e) {
            echo 'Erro ao chamar a procedure: ' . $e->getMessage();
        }

        break;

}

?>