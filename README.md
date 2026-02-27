# SDK PHP — eRede API v2

SDK oficial de integração eRede para PHP.

> **v2** — Migrado para a API eRede v2 com autenticação **OAuth 2.0** (`client_credentials`).  
> O suporte à autenticação Basic Auth (API v1) foi removido.

# Funcionalidades

Este SDK possui as seguintes funcionalidades:

* Autorização de transações (crédito e débito)
* Captura
* Consultas (por TID e por referência)
* Cancelamento / Estorno
* 3DS2 (autenticação de transações)
* Zero dollar (validação de cartão)
* IATA (transações aéreas)
* MCC dinâmico
* Tokenização de cartão (`cardToken` + `tokenCryptogram`)
* Transações recorrentes e card-on-file (`transactionLinkId`, `brandTid`)
* OAuth 2.0 com renovação automática de token (cache com buffer de 60 s)

# Instalação

## Dependências

* PHP >= 8.1
* Extensão `curl` habilitada
* Extensão `json` habilitada

## Instalando o SDK

O pacote está disponível no [Packagist](https://packagist.org/packages/iappapedroza/erede-php-v2).

Se já possui um arquivo `composer.json`, basta adicionar a seguinte dependência ao seu projeto:

```json
{
"require": {
    "iappapedroza/erede-php-v2": "^2.0"
}
}

```

Com a dependência adicionada ao `composer.json`, basta executar:

```
composer install
```

Alternativamente, você pode executar diretamente em seu terminal:

```
composer require iappapedroza/erede-php-v2:^2.0
```

# Testes

O SDK utiliza PHPUnit com TestDox para os testes. Para executá-los em ambiente local, você precisa exportar
as variáveis de ambiente `REDE_PV` e `REDE_TOKEN` com suas credenciais da API. Feito isso, basta rodar:

```
export REDE_PV=1234
export REDE_TOKEN=5678

./tests
```

Os testes também podem ser executados através de um container com a configuração ideal para o projeto. Para isso, basta
fazer:

```
docker build . -t erede-docker
docker run -e REDE_PV='1234' -e REDE_TOKEN='5678' erede-docker
```
````
Caso necessário, o SDK possui a possibilidade de logs de depuração que podem ser utilizados ao executar os testes. Para isso, 
basta exportar a variável de ambiente `REDE_DEBUG` com o valor 1:

```
export REDE_DEBUG=1
```

# Endpoints da API v2

| Ambiente    | Transações                                                    | OAuth Token                                                          |
|-------------|---------------------------------------------------------------|----------------------------------------------------------------------|
| Produção    | `https://api.userede.com.br/erede/v2/transactions`            | `https://api.userede.com.br/redelabs/oauth2/token`                   |
| Sandbox     | `https://sandbox-erede.useredecloud.com.br/v2/transactions`   | `https://rl7-sandbox-api.useredecloud.com.br/oauth2/token`           |

# Autenticação OAuth 2.0

A API v2 utiliza o fluxo **OAuth 2.0 `client_credentials`**. O SDK gerencia o ciclo de vida do token automaticamente:

1. Antes de cada requisição, verifica se existe um token válido em cache.
2. Se o token estiver ausente ou a menos de **60 segundos do vencimento**, um novo token é requisitado.
3. O token recebido (TTL padrão: **1440 s / 24 min**) é armazenado internamente no objeto `Store`.
4. As requisições de transação são enviadas com o header `Authorization: Bearer {token}`.

Nenhuma configuração adicional é necessária — basta instanciar a `Store` normalmente com seu **PV** e **Token**:

```php
// Produção
$store = new Store('SEU_PV', 'SEU_TOKEN', Environment::production());

// Sandbox
$store = new Store('SEU_PV', 'SEU_TOKEN', Environment::sandbox());
```

## Alternativa com Guzzle

Por padrão, o SDK usa cURL para requisitar o token OAuth. Se o seu projeto já utiliza
[`guzzlehttp/guzzle`](https://docs.guzzlephp.org), você pode usar o `GuzzleOAuthService`
como implementação alternativa:

```bash
composer require guzzlehttp/guzzle
```

```php
use RedeV2\Service\GuzzleOAuthService;

$store   = new Store('SEU_PV', 'SEU_TOKEN', Environment::production());
$eRede   = new eRede($store, logger: null, oauthService: new GuzzleOAuthService($store));

$transaction = (new Transaction(20.99, 'pedido' . time()))
    ->creditCard('5448280000000007', '235', '12', '2030', 'John Snow');

$transaction = $eRede->create($transaction);
```

Ambas as implementações respeitam a interface `OAuthServiceInterface`, portanto qualquer
solução customizada pode ser injetada da mesma forma:

```php
use RedeV2\Service\OAuthServiceInterface;

class MeuOAuthService implements OAuthServiceInterface
{
    public function __construct(private readonly Store $store) {}

    public function getAccessToken(): string
    {
        // sua lógica de obtenção/cache de token
    }
}

$eRede = new eRede($store, oauthService: new MeuOAuthService($store));
```

# Utilizando

> Todos os exemplos assumem os imports do namespace `RedeV2`. Adicione no topo de cada arquivo:
> ```php
> use RedeV2\Store;
> use RedeV2\Environment;
> use RedeV2\Transaction;
> use RedeV2\eRede;
> // demais classes conforme necessário: SubMerchant, Device, Url, etc.
> ```

## Autorizando uma transação

```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

// Transação que será autorizada
$transaction = (new Transaction(20.99, 'pedido' . time()))->creditCard(
    '5448280000000007',
    '235',
    '12',
    '2030',
    'John Snow'
);

// Autoriza a transação
$transaction = (new eRede($store))->create($transaction);

if ($transaction->getReturnCode() == '00') {
    printf("Transação autorizada com sucesso; tid=%s\n", $transaction->getTid());
}
```

Por padrão, a transação é capturada automaticamente; caso seja necessário apenas autorizar a transação, o método `Transaction::capture()` deverá ser chamado com o parâmetro `false`:

```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

// Transação que será autorizada
$transaction = (new Transaction(20.99, 'pedido' . time()))->creditCard(
    '5448280000000007',
    '235',
    '12',
    '2030',
    'John Snow'
)->capture(false);

// Autoriza a transação
$transaction = (new eRede($store))->create($transaction);

if ($transaction->getReturnCode() == '00') {
    printf("Transação autorizada com sucesso; tid=%s\n", $transaction->getTid());
}
//...
```

## Adiciona configuração de parcelamento
```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

// Transação que será autorizada
$transaction = (new Transaction(20.99, 'pedido' . time()))->creditCard(
    '5448280000000007',
    '235',
    '12',
    '2030',
    'John Snow'
);

// Configuração de parcelamento
$transaction->setInstallments(3);

// Autoriza a transação
$transaction = (new eRede($store))->create($transaction);

if ($transaction->getReturnCode() == '00') {
    printf("Transação autorizada com sucesso; tid=%s\n", $transaction->getTid());
}
```

## Adiciona informação adicional de gateway e módulo

```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

// Transação que será autorizada
$transaction = (new Transaction(20.99, 'pedido' . time()))->creditCard(
    '5448280000000007',
    '235',
    '12',
    '2030',
    'John Snow'
)->additional(1234, 56);

// Autoriza a transação
$transaction = (new eRede($store))->create($transaction);

if ($transaction->getReturnCode() == '00') {
    printf("Transação autorizada com sucesso; tid=%s\n", $transaction->getTid());
}
```

## Autorizando uma transação com MCC dinâmico

```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

// Transação que será autorizada
$transaction = (new Transaction(20.99, 'pedido' . time()))->creditCard(
    '5448280000000007',
    '235',
    '12',
    '2030',
    'John Snow'
)->mcc(
    'LOJADOZE',
    '22349202212',
    new SubMerchant(
       '1234',
       'São Paulo',
       'Brasil'
    )
);

// Autoriza a transação
$transaction = (new eRede($store))->create($transaction);

if ($transaction->getReturnCode() == '00') {
    printf("Transação autorizada com sucesso; tid=%s\n", $transaction->getTid());
}
//...
```

## Autorizando uma transação IATA

```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

// Transação que será autorizada
$transaction = (new Transaction(20.99, 'pedido' . time()))->creditCard(
    '5448280000000007',
    '235',
    '12',
    '2030',
    'John Snow'
)->iata('code123', '250');

// Autoriza a transação
$transaction = (new eRede($store))->create($transaction);

if ($transaction->getReturnCode() == '00') {
    printf("Transação autorizada com sucesso; tid=%s\n", $transaction->getTid());
}
```

## Capturando uma transação

```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

// Transação que será capturada
$transaction =  (new eRede($store))->capture((new Transaction(20.99))->setTid('TID123'));

if ($transaction->getReturnCode() == '00') {
    printf("Transação capturada com sucesso; tid=%s\n", $transaction->getTid());
}
```

## Cancelando uma transação

```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

// Transação que será cancelada
$transaction = (new eRede($store))->cancel((new Transaction(20.99))->setTid('TID123'));

if ($transaction->getReturnCode() == '359') {
    printf("Transação cancelada com sucesso; tid=%s\n", $transaction->getTid());
}
```

## Consultando uma transação pelo ID

```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

$transaction = (new eRede($store))->get('TID123');

printf("O status atual da autorização é %s\n", $transaction->getAuthorization()->getStatus());
```

## Consultando uma transação pela referência

```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

$transaction = (new eRede($store))->getByReference('pedido123');

printf("O status atual da autorização é %s\n", $transaction->getAuthorization()->getStatus());
```

## Consultando cancelamentos de uma transação

```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

$transaction = (new eRede($store))->getRefunds('TID123');

printf("O status atual da autorização é %s\n", $transaction->getAuthorization()->getStatus());
```

## Transação com autenticação

```php
<?php
// Configuração da loja em modo produção
$store = new Store('PV', 'TOKEN', Environment::production());

// Configuração da loja em modo sandbox
// $store = new Store('PV', 'TOKEN', Environment::sandbox());

// Configura a transação que será autorizada após a autenticação
$transaction = (new Transaction(25, 'pedido' . time()))->debitCard(
    '5277696455399733',
    '123',
    '01',
    '2030',
    'John Snow'
);

// Configura o 3dSecure para autenticação
$transaction->threeDSecure(
    new Device(
        ColorDepth: 1,
        DeviceType3ds: 'BROWSER',
        JavaEnabled: false,
        Language: 'BR',
        ScreenHeight: 500,
        ScreenWidth: 500,
        TimeZoneOffset: 3
    )
);
$transaction->addUrl('https://redirecturl.com/3ds/success', Url::THREE_D_SECURE_SUCCESS);
$transaction->addUrl('https://redirecturl.com/3ds/failure', Url::THREE_D_SECURE_FAILURE);

$transaction = (new eRede($store))->create($transaction);

if ($transaction->getReturnCode() == '220') {
    printf("Redirecione o cliente para \"%s\" para autenticação\n", $transaction->getThreeDSecure()->getUrl());
}
```

## Autorizando via Token de Cartão (Cofre)

Utilize o `cardToken` (tokenizationId retornado pelo cofre de cartões da Rede) no lugar do número do cartão.  
Para transações com token de bandeira (network token), informe também o `tokenCryptogram` (Base64).

```php
<?php
$store = new Store('PV', 'TOKEN', Environment::production());

$transaction = (new Transaction(20.99, 'pedido' . time()))
    ->setCardToken('TOKEN_DO_COFRE')                // token armazenado no cofre Rede
    ->setTokenCryptogram('CRIPTOGRAMA_BASE64');      // opcional — para network tokens de bandeira

// Informe apenas os dados necessários (CVV+vencimento ainda exigidos pela bandeira)
$transaction->creditCard(null, '235', '12', '2030', 'John Snow');

$transaction = (new eRede($store))->create($transaction);

if ($transaction->getReturnCode() == '00') {
    printf("Transação aprovada com token; tid=%s\n", $transaction->getTid());
}
```

## Transações Recorrentes e Card-on-File

Para recorrência Mastercard e transações card-on-file, utilize o `transactionLinkId` para correlacionar as cobranças.  
O campo `brandTid` (identificador da transação na bandeira, alfanumérico, até 21 caracteres) é retornado pela API.

```php
<?php
$store = new Store('PV', 'TOKEN', Environment::production());

// Primeira cobrança
$transaction = (new Transaction(20.99, 'pedido' . time()))
    ->creditCard('5448280000000007', '235', '12', '2030', 'John Snow')
    ->capture();

$transaction = (new eRede($store))->create($transaction);

if ($transaction->getReturnCode() == '00') {
    // Guarde o transactionLinkId para as cobranças subsequentes
    $linkId = $transaction->getTransactionLinkId();
    printf("tid=%s | transactionLinkId=%s\n", $transaction->getTid(), $linkId);
}

// Cobrança subsequente — informe o transactionLinkId da transação original
$recurrentTransaction = (new Transaction(20.99, 'pedido' . time()))
    ->setTransactionLinkId($linkId)
    ->creditCard('5448280000000007', '235', '12', '2030', 'John Snow')
    ->capture();

$recurrentTransaction = (new eRede($store))->create($recurrentTransaction);
```

## Lendo campos novos na resposta (Brand v2)

```php
<?php
$store = new Store('PV', 'TOKEN', Environment::production());

$transaction = (new eRede($store))->get('TID123');

$brand = $transaction->getBrand();

if ($brand !== null) {
    printf("Bandeira:              %s\n", $brand->getName());
    printf("Código autorização:    %s\n", $brand->getAuthorizationCode());
    printf("Merchant Advice Code:  %s\n", $brand->getMerchantAdviceCode());
    printf("Brand TID:             %s\n", $brand->getBrandTid());
    printf("Transaction Link ID:   %s\n", $brand->getTransactionLinkId());
}
```

---

# Changelog

## v2.0.0 — 2026-02-27

### Quebra de compatibilidade
- **Autenticação**: removida a autenticação Basic Auth (`CURLOPT_USERPWD`). A API v2 exige **OAuth 2.0**.

### Novas funcionalidades
- **`OAuthServiceInterface`**: contrato injetável para provedores de token OAuth; permite substituir a implementação padrão (cURL) por qualquer outra sem alterar o código do SDK.
- **`OAuthService`** (cURL): implementação padrão; obtém e renova o `access_token` via fluxo `client_credentials`; token armazenado em cache no `Store` com buffer de 60 s; `curl_close()` removido (deprecated no PHP 8.0+ com `CurlHandle`).
- **`GuzzleOAuthService`**: implementação alternativa baseada em `guzzlehttp/guzzle ^7`; aceitada pelo construtor de `eRede` via `oauthService:` ou injetada diretamente em qualquer serviço.
- **`AbstractService::setOAuthService()`**: permite injetar um provedor OAuth customizado em qualquer serviço individualmente.
- **`eRede`**: novo parâmetro opcional `oauthService: OAuthServiceInterface` no construtor; propagado automaticamente para todos os serviços internos.
- **`Environment`**: versão atualizada para `v2`; novos endpoints de sandbox e OAuth; método `getOAuthEndpoint()`.
- **`Store`**: métodos `getAccessToken()`, `setAccessToken()`, `invalidateAccessToken()` para gerência do ciclo de vida do token.
- **`Transaction`**: novos campos:
  - `orderId` — código do pedido para rastreamento
  - `cardToken` — token do cofre de cartões (substitui o número do cartão)
  - `tokenCryptogram` — criptograma Base64 para network tokens de bandeira
  - `transactionLinkId` — correlação entre transações recorrentes / card-on-file Mastercard
  - `brandTid` — tipo alterado de `int` para `string` (alfanumérico, até 21 chars)
- **`Brand`**: novos campos retornados pela API:
  - `authorizationCode` — código de autorização do emissor
  - `merchantAdviceCode` — MAC Mastercard (indica ação sugerida pelo emissor)
  - `brandTid` — identificador da transação na bandeira
  - `transactionLinkId` — correlação para recorrência Mastercard
- **`composer.json`**: corrigido JSON inválido (vírgula ausente e campo `version` removido de `authors`); Guzzle adicionado como dependência sugerida (`suggest`).

### Endpoints

| Ambiente | Transações | OAuth |
|---|---|---|
| Produção | `https://api.userede.com.br/erede/v2/transactions` | `https://api.userede.com.br/redelabs/oauth2/token` |
| Sandbox | `https://sandbox-erede.useredecloud.com.br/v2/transactions` | `https://rl7-sandbox-api.useredecloud.com.br/oauth2/token` |
