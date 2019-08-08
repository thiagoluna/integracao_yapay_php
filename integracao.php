<?php
//Como nesse caso é pagamento por boleto, quando o usuário finaliza a compra, envia pra esse arquivo 
//o valor da compra e o nome do produto 
$valor = number_format($_REQUEST['valor'], 2, '', '');
$nome_produto = $_REQUEST['nome_produto'];
$numero_transacao = $_REQUEST['numero_transacao'];
$codigo_estabelecimento = colocar_aqui_codigo_do_estabelecimento;

//Pegar dados do usuário pra gerar boleto - o id está armazenado numa variável de sessão
try{
	$stmt = $pdo->prepare("SELECT nome, cpf, email, endereco, bairro, numero, cidade, estado, cep FROM usuario WHERE id_usuario = :id");
	$stmt->execute(array(':id' => $_SESSION['id_usuario']));
	$dados = $stmt->fetch(PDO::FETCH_ASSOC);
}catch(PDOException $e){
	echo $e->getMessage();
}


/* Dados para enviar para o ambiente de PRODUÇÂO */
$transation_number 	= $numero_transacao;
$StoreCode  		= $codigo_estabelecimento;
$forma_pgto 		= 17; // porque é boleto no ambiente de produção; 
$usuario    		= "usuario_fornecido_pela_yapay";
$senha				= "senha_fornecida_pela_yapay";
$link_ambiente		= "https://gateway.yapay.com.br/checkout";

/* Dados para enviar para ambiente de testes - Sandbox 
$transation_number 	= $numero_transacao;
$StoreCode 			= $codigo_estabelecimento;
$forma_pgto			= 29; // porque é boleto no ambiente de testes Sandbox
$usuario    		= "usuario_fornecido_pela_yapay";
$senha				= "senha_fornecida_pela_yapay";
$link_ambiente		= "https://sandbox.gateway.yapay.com.br/checkout"; */


/* include das classes - cuidado com o path desses arquivos.
Na biblioteca, esses arquivos estrão dentro de subpastas da pasta lib */
require_once "transaction_builder.php";
require_once "rest_v3.php";
require_once "credential.php";
require_once "transaction_json_builder.php";

//Aqui começa tudo
$builder = new TransactionBuilder();

/* código estabelecimento, código forma pagamento, número pedido, valor do pedido */
$builder->newTransaction($StoreCode, $forma_pgto, $transation_number, $valor);

/* url de retorno - arquivo que vai receber a informação da Yapay sobre mudança de status de uma transação
esse arquivo receber.php está aqui no github */
$builder->withNotificationUrl("http://dominio_da_sua_aplicacao/receber_status.php");

/* vencimento boleto */
$builder->withBillDueDate("07/10/2019");  /* Informar a data que deseja */

/* dados de cobrança que vem do select feito lá em cima */
$chargingDta = new TransactionChargingData();
$chargingDta->clientName = $dados['nome'];
$chargingDta->clientDocument = $dados['cpf'];
$chargingDta->clientEmail = $dados['email'];
$chargingDta->clientType = 1; // 1 - pessoa física
$builder->withChargingData($chargingDta);

$address1 = new TransactionAddressData();
$address1->street = $dados['endereco'];
$address1->number = $dados['numero'];
$address1->zipCode = $dados['cep'];
$address1->city = $dados['cidade'];
$address1->district = $dados['bairro'];
$address1->state = $dados['estado'];
$chargingDta->clientChargingAdressData = $address1;

$builder->withChargingData($chargingDta);

/* itens do pedido - no meu caso é apenas 1 item sempre com o mesmo valor */
$item = new TransactionItemData();
$item->productCode = 1;
$item->productCategory = 1;
$item->productName = $nome_produto;
$item->productAmount = 1;
$item->productUnitaryValue = $valor;
$item->categoryName = 1;
$builder->withItems(array($item));

/* credencias de acesso */
$credential = new Credential();
$credential->user = $usuario;
$credential->password = $senha;

/* Se conecta na Yapay passando credenciais */
$communication = new RestV3($link_ambiente);

$json_builder = new TransactionJsonBuilder;
$transaction = $json_builder->newTransaction($builder->build());
$result = $communication->transactionAuthorize($credential, $transaction);
$communication->transactionQuery($credential, $StoreCode, $transation_number);
/* Você pode ver o retorno da transação 
echo "retorno: " . $result.'<br/>';
echo "transaction: "; print_r($transaction).'<br/>'; */

/* Pega o retorno em json, armazena numa variável e depois decodifica */
$json_str = $result;
$obj = json_decode($json_str);
/* Extraio desse retorno o número da transação, e a url do boleto gerado para o usuário conseguir baixar o boleto */
try{
	$sql = "UPDATE transacao SET ";			
	$sql.= "status_pagamento='$obj->statusTransacao'";
	$sql.= ", url_boleto='$obj->urlPagamento'";
	$sql.= " WHERE id_transacao = ".$obj->numeroTransacao;
	$stmt = $pdo->prepare($sql);
	$stmt->execute();		
}catch(PDOException $e){
	echo $e->getMessage();
}

/* No final de tudo redireciono o usuário para uma página principal, passando o link do boleto
Nessa página, pego o link e uso javascript pra abrir automaticamento a url que mostrará o boleto */
header ("location: comprar.php?boleto=".$obj->urlPagamento);
?>