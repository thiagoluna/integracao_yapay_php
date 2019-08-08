<?php
/* Este retorno não funciona para boletos gerados no ambiente Sandbox de testes, 
pois a Yapay só manda retorno de mudança de status de transação de boleto no ambiente de produção */

/* Receber POST da Yapay com id da transacao que teve status alterado */
$nummeroTransacao = $_REQUEST['numeroTransacao'];

//Pegar o numero da transacao recebido acima e buscar o novo status
require_once "credential.php";
require_once "rest_v3.php";

$transation_number 	= $nummeroTransacao;
$StoreCode  		= codigo_do_estabelecimento;
$usuario    		= "usuario_fornecido_pela_yapay";
$senha				= "senha_fornecida_pela_yapay";
$link_ambiente		= "https://gateway.yapay.com.br/checkout";

/* credencias de acesso */
$credential = new Credential();
$credential->user = $usuario;
$credential->password = $senha;

//Método para pegar o status
$communication = new RestV3($link_ambiente);

//Metodo para consulta status pedido
$retorno = $communication->transactionQuery($credential, $StoreCode, $transation_number);

/* Guarda retorno que vem em json, armazena em uma variavel e depois decodifica pra pegar as informações vindas
Você pode encontrar como vem estruturadas as informações json no site da Yapay*/
$json_str = $retorno;
$obj = json_decode($json_str);

//Gravar na base o status recebido
try{
	$sql = "UPDATE transacao SET status_pagamento='$obj->statusTransacao'";
	$sql.= ", dt_conciliacao='".date('Y-m-d H:i:s')."' WHERE id_transacao = ".$nummeroTransacao;
	$stmt = $pdo->prepare($sql);
	$stmt->execute();		
}catch(PDOException $e){
	$erro = $e->getMessage();
}

//Escrever em disco caso prefira dessa forma
$name = 'retorno.txt';
$text = "Num_Transacao: ".$nummeroTransacao." - status: ".$obj->statusTransacao." - data: ".date('d-m-Y H:i:s')."\n";
$file = fopen($name, 'a');
fwrite($file, $text);
fclose($file);
?>