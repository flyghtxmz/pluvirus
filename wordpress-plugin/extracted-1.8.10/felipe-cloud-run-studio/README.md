# Felipe AI Studio

Versao 1.8.7 do plugin, com ajuste no fluxo rewarded para liberar o download apenas apos a confirmacao real do anuncio concluido.

## O que mudou

- a URL do endpoint e a chave podem ser salvas dentro do proprio plugin
- o visitante nao escolhe template nem escreve prompt
- cada artigo passa a definir o template no editor
- o prompt fica escondido dentro do plugin
- o shortcode no front so executa o template ja vinculado ao artigo
- o painel admin ganhou selecao de tipo de geracao e dropdown de modelos
- a home do plugin agora tem cadastro de templates
- o campo de base foi removido dos templates
- o Painel de teste voltou a aceitar prompt livre, inclusive com a opcao `Nenhum`
- templates e painel de teste agora aceitam tamanho, proporcao, formato e qualidade JPEG
- o editor ganhou um bloco proprio do Gutenberg para inserir o gerador e escolher o template direto no bloco

## Onde configurar

Abra:

- `Ferramentas > Felipe AI Studio`

E salve:

- URL do endpoint
- chave `X-Site-Secret`
- timeout

## Como usar no artigo

Opcao 1: bloco do Gutenberg

1. Edite o artigo ou pagina.
2. Adicione o bloco `Felipe AI Studio`.
3. Na lateral do bloco, escolha o template.
4. Se quiser, sobrescreva o modelo, o titulo e o texto do botao.
5. Publique ou atualize.

Opcao 2: fluxo legado com metabox + shortcode

1. Edite o artigo.
2. Na lateral do editor, encontre a caixa `Felipe AI Studio`.
3. Ative o gerador no artigo.
4. Escolha o template.
5. Se quiser, defina um modelo especifico para esse artigo.
6. Publique ou atualize o artigo.
7. Na pagina do artigo, use o shortcode:

```text
[felipe_cloud_run_studio]
```

## Fallback via wp-config.php

Se voce quiser, o plugin ainda aceita configuracao por constantes:

```php
define('FCRS_CLOUD_RUN_ENDPOINT', 'https://SEU-ENDPOINT');
define('FCRS_CLOUD_RUN_SHARED_SECRET', 'SUA_CHAVE_LONGA');
define('FCRS_CLOUD_RUN_TIMEOUT', 120);
```
