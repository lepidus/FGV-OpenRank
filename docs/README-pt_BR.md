**Português Brasileiro** | [English](../README.md) | [Español](README-es.md)

# FGV OpenRank

[![OJS compatibility](https://img.shields.io/badge/ojs-3.3.0.x-brightgreen)](https://github.com/pkp/ojs/tree/stable-3_3_0)
[![License type](https://img.shields.io/badge/license-GPL--3.0-blue)](https://www.gnu.org/licenses/gpl-3.0)

Este plugin adiciona um bloco de ranqueamento à página inicial de revistas que usam o [OJS](https://pkp.sfu.ca/software/ojs/). O bloco lista artigos em abas — **Mais recentes**, **Mais lidos**, **Mais citados**, **Em alta** e uma aba de conteúdo livre, **Destaque** — e o editor-gerente decide quais abas aparecem, em que ordem, com qual título, descrição e quantidade de itens.

## Como funciona

Você escolhe onde o bloco fica nas configurações do plugin — no início da página inicial, depois de uma seção específica dela, no fim, ou no lugar em que você inserir `<div class="rankingTabs"></div>` no Conteúdo Adicional. Cada aba carrega seus artigos de forma assíncrona, a partir da API do próprio plugin, que entrega dados de um cache atualizado uma vez por dia. Os serviços externos (Crossref, Altmetric) são consultados pela tarefa agendada, e não enquanto o leitor espera a página carregar.

| Aba | O que lista | Fonte | Requer |
| --- | --- | --- | --- |
| **Mais recentes** | Os artigos publicados mais recentemente | OJS | — |
| **Mais lidos** | Os artigos mais acessados nos últimos *N* dias (120 por padrão) | Estatísticas de uso do OJS | Estatísticas de uso registradas |
| **Mais citados** | Os artigos mais citados da revista | [Crossref](https://www.crossref.org/services/cited-by/) | ISSN da revista + DOIs dos artigos |
| **Em alta** | Os artigos com maior pontuação Altmetric | API do [Altmetric](https://www.altmetric.com/) ou lista manual de DOIs | DOIs dos artigos (ISSN e chave de API, se automático) |
| **Destaque** | O conteúdo que você mesmo escrever (texto formatado) | — | — |

## Primeiros passos

### 1. Instale o plugin

Baixe o `.tar.gz` da última versão compatível com seu OJS na [página de lançamentos](https://gitlab.lepidus.com.br/softwares-pkp/plugins_ojs/rankingPlugin/-/releases), acesse *Configurações → Website → Plugins → Enviar novo plugin*, envie o arquivo e habilite o plugin na sua revista.

### 2. Escolha onde o bloco aparece

Abra as *Configurações* do plugin e escolha a **Posição na página inicial da revista**:

- **No início da página inicial** — acima de todas as demais seções.
- **Depois de uma seção específica da página inicial** — então responda **Depois de qual seção?**: `1` posiciona o bloco depois da primeira seção, `2` depois da segunda, e assim por diante. A contagem considera cada seção que o tema empilha na página inicial — a imagem da página inicial, a descrição da revista, os anúncios, a edição atual, o conteúdo adicional e o que mais o tema exibir. O que conta como seção depende, portanto, do tema e do que a revista tem configurado, e alguns temas agrupam várias delas dentro de um mesmo contêiner, então pode ser preciso testar alguns números. Um número maior que a quantidade de seções leva o bloco para o fim da página.
- **No fim da página inicial** — abaixo de todas as demais seções.
- **Onde o elemento `rankingTabs` estiver** (padrão) — em *Configurações → Website → Aparência → Avançado*, inclua o seguinte em **Conteúdo Adicional**:

  ```html
  <div class="rankingTabs"></div>
  ```

  O bloco é renderizado dentro desse elemento.

As três primeiras opções são resolvidas pelo próprio plugin, sem CSS personalizado envolvido. Elas são contadas entre os blocos da página inicial, e não casadas com classes CSS específicas de cada tema, então se sustentam quando um tema renomeia, reordena ou remove alguma seção — um tema muito customizado ainda pode exigir ajustes.

### 3. Libere o host

As abas consultam a API do plugin no próprio endereço da revista, então o host precisa estar em `allowed_hosts`, no arquivo `config.inc.php`:

```php
allowed_hosts = '["minharevista.org"]'
```

### 4. Configure as abas

Abra as *Configurações* do plugin. Uma grade lista as cinco abas: use o botão de status para habilitar ou desabilitar cada uma, arraste as linhas para mudar a ordem em que aparecem e clique em uma aba para editá-la.

## Configuração de cada aba

Todas as abas têm:

- **Título personalizado** — substitui o título padrão. Multilíngue.
- **Descrição** — o texto exibido acima da lista. Multilíngue.
- **Itens por aba** — quantos artigos a aba reúne (4 por padrão).
- **Itens por página** — quantos são exibidos por vez, com os demais paginados (4 por padrão).

Algumas abas têm configurações próprias:

- **Mais lidos:** *Dias para mais lidos* — o período usado na contagem de acessos (120 por padrão).
- **Destaque:** *Conteúdo personalizado* — um campo de texto formatado. Essa aba não faz chamadas de API; ela exibe exatamente o que você escrever.
- **Em alta:** a chave de API do Altmetric e a lista manual de DOIs, descritas a seguir.

### A aba Em alta: chave de API ou lista manual

A aba funciona de duas formas:

- **Com uma chave de API do Altmetric.** Os artigos são buscados na API do Altmetric pelo ISSN da revista e ordenados por pontuação. A chave é validada no momento em que você salva e armazenada de forma criptografada — o que exige o `api_key_secret` configurado no `config.inc.php` ([como gerar um](https://forum.pkp.sfu.ca/t/how-to-generate-a-api-key-secret-code-in-ojs-3/72008)).
- **Com uma lista manual de DOIs.** Sem chave armazenada, a aba exibe os DOIs que você listar, na ordem em que foram cadastrados. Só são aceitos DOIs de artigos publicados nesta revista.

> [!NOTE]
> Enquanto houver uma chave armazenada, a lista manual é ignorada. Para voltar a usá-la, marque *Remover a chave de API armazenada* e salve.

## Cache e atualização diária

As abas são servidas a partir de um cache em arquivo, por revista. Uma tarefa agendada — *Atualização de cache do FGV OpenRank* — atualiza todas as abas de todas as revistas habilitadas diariamente à meia-noite. Por isso, mantenha o plugin **Acron** habilitado ou coloque o `runScheduledTasks.php` no crontab do servidor ([Guia do Administrador da PKP](https://docs.pkp.sfu.ca/admin-guide/)). Se o cache estiver vazio quando um leitor acessar a página, os dados são buscados na hora.

Para atualizar manualmente, a partir da raiz do OJS:

```bash
php tools/runScheduledTasks.php
```

## Requisitos

- **OJS 3.3.0.x**
- **`allowed_hosts`** incluindo o host da revista.
- **ISSN** cadastrado na revista — necessário para *Mais citados* e para *Em alta* quando há chave de API.
- **DOIs** atribuídos aos artigos — *Mais citados* e *Em alta* identificam os artigos pelo DOI, então um artigo sem DOI nunca aparece nessas abas.
- **`api_key_secret`** no `config.inc.php`, apenas se você for armazenar uma chave de API do Altmetric.

## Solução de problemas

<details>
<summary><strong>O bloco não aparece na página inicial</strong></summary>

Verifique se o plugin está habilitado nesta revista. Se a posição estiver definida como *Onde o elemento `rankingTabs` estiver*, confirme que `<div class="rankingTabs"></div>` está no *Conteúdo Adicional*. Apenas a primeira ocorrência do elemento na página é utilizada.

</details>

<details>
<summary><strong>O bloco aparece no lugar errado</strong></summary>

Com *Depois de uma seção específica da página inicial*, experimente outra resposta para **Depois de qual seção?** — quantas seções a página inicial tem depende do tema e do que a revista configurou. Números altos demais levam o bloco para o fim da página.

</details>

<details>
<summary><strong>Uma aba exibe mensagem de erro</strong></summary>

Confirme se o host da revista está em `allowed_hosts`. Erros vindos do Crossref ou do Altmetric ficam registrados nos logs do servidor do OJS.

</details>

<details>
<summary><strong>Mais citados ou Em alta está vazia</strong></summary>

Em geral, a revista não tem ISSN, os artigos não têm DOI ou o serviço externo ainda não tem dados sobre eles. Na aba Em alta sem chave de API, verifique se a lista manual de DOIs foi preenchida.

</details>

<details>
<summary><strong>Mais lidos está vazia</strong></summary>

Não há acessos registrados no período. Aumente o valor de *Dias para mais lidos* ou verifique se as estatísticas de uso do OJS estão sendo coletadas.

</details>

<details>
<summary><strong>A chave do Altmetric foi recusada ao salvar</strong></summary>

Ou a chave não é válida para a API do Altmetric, ou o `api_key_secret` não está configurado no `config.inc.php` — nesse caso, a chave não pode ser armazenada de forma criptografada.

</details>

## Onde buscar ajuda

- **O plugin:** abra uma issue neste repositório.
- **O OJS em si:** pergunte no [Fórum da Comunidade PKP](https://forum.pkp.sfu.ca/).

## Licença

Este plugin está licenciado sob a [Licença Pública Geral GNU v3.0](https://www.gnu.org/licenses/gpl-3.0).

Copyright (c) 2025-2026 Lepidus Tecnologia.
