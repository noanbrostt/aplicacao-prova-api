<?php
// Author: Noan Caliel Brostt (123036)

// Variável que diz qual ação será feita pela API
if (isset($_REQUEST['acao'])) {
    $acao = $_REQUEST['acao'];
} else {
    echo "Erro nos parâmetros!";
    exit;
}


// Conexão no banco de dados
$conexao = pg_connect("host=172.10.20.47 dbname=bd_rh_rs user=usr_portal password=ps_PortalPlansul");

// Validação de conexão
if(!$conexao){
    echo json_encode("Erro na conexão");
    exit;
}


// Separa as diferentes coisas que a API faz
switch ($acao) {

    case 1: // Usuário logando no site

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

        $resultado = pg_query($conexao, $sql);

        echo var_dump($resultado);

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

        $resultado = pg_query($conexao, $sql);

        echo var_dump($resultado);

        break;

    
    case 3: // Começar prova e carregar questôes da prova

        // Parâmetros esperados:
        $matricula = $_REQUEST['matricula'];
        $prova = $_REQUEST['prova'];

        // Select para checar se a prova ja foi iniciada
        $sql = "SELECT dh_fim
                    FROM sc_psi_prova.tb_prova_respondida
                    WHERE matricula = '$matricula'
                        AND co_prova = '$prova'";

        $resultado = pg_query($conexao, $sql);

        if (!$resultado) {
            $sql = "INSERT INTO sc_psi_prova.tb_prova_respondida
                        (co_prova
                        ,matricula
                        ,dh_inicio)
                    VALUES 
                        ('$prova'
                        ,'$matricula'
                        ,'".date('Y-m-d H:i:s')."')";

            // Validação do resultado do insert 
            $resultado = pg_query($conexao, $sql);

            if (!$resultado) {
                echo "Erro na inserção: ".pg_last_error($conexao);
                exit;
            }
        }

        // Select das questôes da prova
        $questoes = "SELECT  PR.co_prova
                            ,PR.no_prova
                            ,PE.de_pergunta
                            ,STRING_AGG(AL.de_alternativa, ';.;') AS alternativas
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
                                ,PE.de_pergunta";

            // Validação do resultado
            $resultado = pg_query($conexao, $questoes);

            break;

    
    case 4: // Finalizar prova
        
        break;

    
    case 5:

        break;

}

// Desconexão do banco de dados
pg_close($conexao);

?>