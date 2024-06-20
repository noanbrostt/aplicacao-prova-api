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
        $sql = "SELECT 	nome,
                        filial,
                        co_funcao
                    FROM public.tb_empregados
                    WHERE dtdemissao IS NULL
                        AND matricula = '$matricula'
                        AND cpf = '$cpf'";

        // Validação do resultado do insert 
        $resultado = pg_query($conexao, $sql);

        echo var_dump($resultado);

        break;

    
    case 2: // Carregar tela "lista_provas"

        break;

    
    case 3:

        break;

    
    case 4:

        break;

    
    case 5:

        break;

}

// Desconexão do banco de dados
pg_close($conexao);

?>