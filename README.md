# Plugin de Ranqueamento

## Compatibilidade

Este plugin é compatível com o **OJS** versão **3.3.0**.

## Baixar Plugin

Para baixar o plugin, já até a [página de lançamentos](https://gitlab.lepidus.com.br/softwares-pkp/plugins_ojs/rankingPlugin/-/releases) e baixe o pacote tar.gz da última versão compatível com seu OJS.

## Instalação

1. Entre na área adminstrativa do seu OJS e navegue para `Configurações`>`Website`>`Plugins`>`Enviar novo plugin`.
2. Clique em **Enviar Arquivo** e selecione o arquivo **rakingPlugin.tar.gz**.
3. CLique em **Salvar** e o plugin será instalado no seu OJS.

## Requerimentos

### allowed_hosts

Adicione o Host atual na opção *allowed_hosts* no arquivo de configuração `config.inc.php`.
Exemplo: `allowed_hosts = '["127.0.0.1", "localhost"]'`

### Conteúdo Adicional

Em `Configurações` > `Website` > `Aparência` > `Avançado`, adicione o seguinte código em **Conteúdo Adicional**:

```html
<div class="rankingTabs"></div>
```

## Créditos

Desenvolvido por [Lepidus Tecnologia](https://github.com/lepidus).

## Licença

Este plugin está licenciado sob a Licença Pública Geral GNU v3.0

Copyright (c) 2025 Lepidus Tecnologia
