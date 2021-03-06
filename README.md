# integracao_yapay_php
Integrar pagamento online do Yapay com sua aplicação PHP

Ao fazer a integração de pagamento online da Yapay através de boleto bancário com uma aplicação PHP, tive dúvidas em relação a utilizar a biblioteca disponibilizada no github da Yapay.

Para ajudar outros desenvolvedores PHP na integração deste tipo de pagamento da Yapay, estou disponibilizando 2 arquivos:
- integracao.php - Arquivo que vai receber alguns dados da página de finalização de compra da aplicação, buscar o restante na base de dados, utilizar os métodos das classes da biblioteca e enviar tudo para o Yapay.

- receber_status.php - Arquivo que vai ficar "escutando" a Yapay para receber a informação de mudança de status do boleto gerado e, logo em seguida, consultar qual status é esse (pago, cancelado, aguardando...) e gravar na base de dados.

A biblioteca da Yapay pra PHP pode ser baixada em https://github.com/YapayPagamentos/integracao_gateway/tree/master/php/yapay-gw-lib

Basta adicionar essa biblioteca no seu projeto e fazer o include das classes nos arquivos que irão usar os métodos específicos para cada tipo de tarefa.
Se tiver dificuldade de dar o include por conta da estrutura de pastas/subpastas da biblioteca, a dica é deixar todos os arquivos numa única pasta.

Procurei deixar esses arquivos o mais simples possível para facilitar o entendimento.

Se o seu contrato com a Yapay for do tipo Gateway, você pode encontrar mais informações em https://gateway.dev.yapay.com.br/#/

Já, se for do tipo Intermediador, acesse https://intermediador.dev.yapay.com.br/#/ para obter mais informações.
