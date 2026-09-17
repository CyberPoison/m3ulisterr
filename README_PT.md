# 🎬 TMDB para VOD: Playlist Gratuita de TV ao Vivo, Filmes e Séries [Xtream Codes e M3U8]

🌍 **Idiomas:** [English](README.md) | [Português](README_PT.md)

[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)
<br>

---

## 📝 Resumo

**Crie Playlists de Vídeo sob Demanda (VOD) de TV ao Vivo, Filmes e Séries usando os formatos Xtream Codes ou M3U8.**

Gere playlists dinâmicas para TV ao Vivo, Filmes e Séries de TV usando uma versão simulada do Xtream Codes. Crie playlists de IPTV, Filmes e Séries com metadados abrangentes. Os links de streaming são localizados usando integrações com **TMDB**, **Real-Debrid**, **Premiumize** e diversas **Fontes Diretas**. 

Ideal para ser utilizado em players renomados como *iMplayer*, *Tivimate*, *IPTV Streamers Pro*, *XCIPTV Player*, entre outros.

<table style="border-collapse: collapse; border: none;">
  <tr>
    <td style="border: none;">
      <a href="https://github.com/CyberPoison/m3ulisterr/archive/refs/tags/latest.zip">
        <img src="https://img.shields.io/badge/Download%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="Download ZIP">
      </a>
    </td>
    <td style="border: none; padding-left: 10px;">
      <a href="https://ko-fi.com/gogetta69">
        <img src="https://img.shields.io/badge/Ko--fi-Support-F16061?style=for-the-badge&logo=ko-fi&logoColor=white" alt="Ko-fi">
      </a>
    </td>
  </tr>
</table>

---

## 🎥 Vídeo de Demonstração

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="Demo GIF" width="70%">

---

## 🖼️ Capturas de Tela

<table>
  <tr>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110311.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110433.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110501.png" width="400"></td>
  </tr>
  <tr>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110535.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110653.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110819.png" width="400"></td>
  </tr>
  <tr>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110832.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110847.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623111001.png" width="400"></td>
  </tr>
  <tr>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623111026.png" width="400"></td>
  </tr>
</table>

---

## ✨ Funcionalidades Principais

- **Geração Dinâmica de Playlist:** Para TV ao vivo, filmes e séries de TV.
- **Múltiplas Integrações:** Suporte ao TMDB, Real Debrid, Premiumize e Fontes Diretas para recuperação otimizada de conteúdo.
- **Emulação Completa:** Replica o ambiente do software Xtream Codes, oferecendo metadados ricos e completos.
- **Suporte a TV ao Vivo:** Inclusão de fontes conceituadas como [Daddylive](https://dlhd.so/24-7-channels.php), [TheTVApp](https://thetvapp.to/), [MoveOnJoy](https://i.imgur.com/dFazdys.png), [Streamed Su Sports](https://streamed.pk/), [Pluto TV](https://downloads.pluto.tv/docs/pluto_tv_channels_listing.pdf) e mais, grande parte com informações detalhadas de EPG (Guia de TV).
- **Eficiência e Cache:** Cache automático de links encontrados para reprodução ágil nas próximas solicitações.
- **VOD Adulto Opcional:** Conta com uma biblioteca de cerca de 10 mil filmes adultos (desativado por padrão).
- **Detecção Inteligente de Áudio:** Seleciona o idioma correto baseando-se no cabeçalho do arquivo, superando tags frequentemente imprecisas.
- **Experiência Localizada:** Caminho francês dedicado (`UnlimitedFR` / `?lang=fr`) para conteúdos nativos.
- **Sistema de Legendas Robusto:** Faixas em Português (PT-BR e PT-PT) inclusas, com suporte a fallback imediato pela API do OpenSubtitles.
- **Dashboard Analítico M3uListerr:** Registre, acompanhe e visualize cada evento no seu servidor com interface interativa (saiba mais na seção do Dashboard).

---

## 🚀 Começando (Instalação e Uso)

[![Thumbnail do Vídeo](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **Configuração Inicial**: Obtenha uma [Chave API gratuita do TMDB](https://developer.themoviedb.org/docs/getting-started) obrigatória. Opcionalmente, inclua chaves para [Real Debrid](https://real-debrid.com/apitoken) ou [Premiumize](https://www.premiumize.me/account) no arquivo `config.php`.
2. **Integração via Xtream Codes**: No seu player favorito, insira o IP/domínio da sua hospedagem como o servidor Xtream Codes. O usuário e a senha podem ser quaisquer valores, pois a autenticação não é exigida. Isso fará o download instantâneo das listas.
3. **Uso Alternativo (Sem Xtream Codes)**: Caso o player não suporte Xtream, carregue `http://ENDERECO_IP/player_api.php?action=get_vod_streams` no navegador. Em seguida, localize o arquivo `playlist.m3u8` criado e carregue-o como uma playlist comum de IPTV.
4. **Reprodução e Desempenho**: Ao reproduzir um título pela primeira vez, o servidor buscará links ativamente em segundo plano. Por favor, seja paciente enquanto o link inicial é selecionado. Este link será armazenado em cache por ~3 horas para acessos quase instantâneos subsequentes.
5. **Hospedagem Local**: O sistema é muito leve! Você pode facilmente rodá-lo localmente usando soluções como XAMPP, WAMP ou Docker no seu PC ou Raspberry Pi.

---

## ⚙️ O que é HeadlessVidX?

**HeadlessVidX** é uma ferramenta poderosa desenvolvida para simplificar a criação e extração de vídeos em sites de streaming. Ele permite que qualquer pessoa — independentemente do nível de conhecimento em programação — crie extratores que se conectam aos seus sites favoritos, integrando-os diretamente ao *TMDB TO VOD*.

<table>
  <tr>
    <td align="center"><img src="https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/Screenshot%202024-06-14%20at%2016-41-13%20HeadlessVidX%20-%20Home.png" width="400"></td>
    <td align="center"><img src="https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/Screenshot%202024-06-14%20at%2016-40-15%20HeadlessVidX%20-%20Trainer.png" width="400"></td>
  </tr>
</table>

---

## 📂 Criando Playlists Automáticas

Chega de rodar tarefas manuais! Graças à integração nativa com o **GitHub Actions**, suas playlists são geradas e atualizadas automaticamente duas vezes ao dia. 
Se desejar gerar listas personalizadas de filmes e séries por conta própria, ative a opção `$userCreatePlaylist = true` no arquivo `config.php`.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b

---

## 🐳 Implantação com Docker

Agora é possível provisionar todo o ambiente em poucos minutos utilizando o poder do Docker.

### ⚡ Início Rápido (Sem necessidade de git clone)

Use o comando abaixo para iniciar rapidamente o contêiner utilizando a imagem pública:

```bash
# Crie o diretório de dados
mkdir -p m3ulisterr_data
# Crie um arquivo de configuração vazio
touch config.php
# Execute o contêiner em segundo plano
# 1. Create a custom Docker network for DNS resolution
docker network create m3ulisterr_net

# 2. Start the Gluetun VPN container to bypass Debrid IP blocks
# (See [Gluetun VPN](https://github.com/qdm12/gluetun) for provider configurations)
docker run -d \
  --name gluetun \
  --network=m3ulisterr_net \
  --cap-add=NET_ADMIN \
  --device=/dev/net/tun:/dev/net/tun \
  -e VPN_SERVICE_PROVIDER=custom \
  qmcgaw/gluetun

# 3. Start m3ulisterr routed through the Gluetun VPN network
docker run -d \
  --name m3ulisterr \
  --network=container:gluetun \
  -v $(pwd)/m3ulisterr_data:/var/www/html/m3ulisterr_data \
  -v $(pwd)/config.php:/var/www/html/config.php \
  -e HEADLESSVIDX_ADDRESS=localhost:3202 \
  ghcr.io/cyberpoison/m3ulisterr:latest

# 4. Start a lightweight proxy to fix VPN asymmetric routing for incoming access
# (We fetch the VPN's internal IP directly to avoid any Docker DNS resolution bugs on your host)
GLUETUN_IP=$(docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' gluetun)
docker run -d \
  --name m3ulisterr-proxy \
  --network=m3ulisterr_net \
  -p 8080:80 \
  caddy:alpine caddy reverse-proxy --from :80 --to $GLUETUN_IP:80
```

### 🛠️ Usando Docker Compose
1. Clone o repositório.
2. Configure o seu `config.php`.
3. Na pasta raiz, rode o comando:
   ```bash
   docker-compose up -d
   ```
4. Acesse a interface web em `http://localhost:8080`.

**Variáveis de Ambiente Disponíveis:**
- `HEADLESSVIDX_ADDRESS`: O endereço do serviço HeadlessVidX (padrão: `localhost:3202`). No docker-compose, costuma estar definido como `headlessvidx:3202`.

---

## 📊 Dashboard Analítico M3uListerr

O **`dashboard.php`** é um painel de controle e análise moderno, independente e protegido por login. Ele monitora de maneira leve todas as métricas do servidor, incluindo sessões de reprodução, proxies, resoluções, logs e eventos geográficos, alimentando um banco de dados **SQLite privado**.

### 📸 Capturas de Tela do Dashboard e O que Elas Mostram

![Overview](wiki/Overview.png)
> 📈 **Visão Geral:** Fornece um resumo completo e instantâneo do uso da plataforma, apresentando o total de sessões, espectadores únicos e métricas de desempenho. Inclui gráficos intuitivos que detalham clientes, países, dispositivos e o equilíbrio entre filmes e séries. Essencial para entender o fluxo de tráfego do servidor de relance.

![Globe](wiki/Globe.png)
> 🌍 **Globo Interativo:** Um mapa 3D moderno e dinâmico que rastreia em tempo real a origem geográfica das requisições de mídia. Permite que você visualize o alcance global do seu conteúdo e identifique de onde seus usuários estão se conectando.

![Sessions](wiki/Sessions.png)
> 📋 **Sessões Detalhadas:** Exibe uma tabela granular para cada reprodução individual. Apresenta pôsteres, IPs, tipos de arquivo/codecs selecionados, provedores utilizados de Debrid, faixas de legenda ativadas e, notavelmente, o progresso exato da exibição do usuário em % e tempo. Muito útil para suporte e moderação de uso.

![Titles](wiki/Titles.png)
> 🎬 **Títulos Mais Acessados:** Uma galeria visual elegante exibindo as capas dos filmes e séries de TV mais requisitados na plataforma, ajudando-o a descobrir facilmente quais conteúdos estão em alta com a sua audiência.

![Cache Logs](wiki/cache%20logs.png)
> ⚡ **Logs de Cache:** Ferramenta dedicada ao gerenciamento interno de resoluções (`cache.json`). Permite investigar acertos e falhas na busca rápida por mídias e mensurar quão eficiente está a entrega aos usuários finais.

### 🛡️ Segurança e Privacidade
- **Login Seguro:** A senha de admin criada na primeira execução passa por hash forte (Argon2id) e é salva localmente. Nenhuma senha fica em texto plano no código.
- **Proteção Completa:** Todos os logs, arquivos SQLite e o diretório `m3ulisterr_data/` têm seu acesso negado via web (o servidor impede leituras não autorizadas). Tudo fica restrito e fora de escopo para commits no Git.
- **Prevenção de Ataques:** Proteções avançadas como CSP (Content-Security-Policy), tokens anti-CSRF, bloqueios a bruteforcing no login e rotatividade de sessão estão ativas.

### 🔧 Como Acessar o Dashboard
1. Implante seus arquivos conforme instruído anteriormente.
2. Certifique-se de que o sistema possui permissão de escrita para criar a pasta `m3ulisterr_data/` na raiz do site.
3. Acesse `http://SEU_SERVIDOR/dashboard.php`, crie uma conta administrativa no primeiro acesso, faça login e veja a mágica acontecer! Os dados se atualizam sempre que você clicar no botão **Refresh data**.

---

## 🔄 Atualizações e Changelog

### Atualização 14/09/2026
Uma grande revisão de estabilidade, idioma, legendas, análise de dados e segurança. Destaques:
- **Dashboard analítico M3uListerr (novo):** Integração total do `dashboard.php` independente. Protegido por login privado, estatísticas completas, monitoramento em tempo real, visualização de cache e editor do arquivo `config.php` diretamente no navegador.
- **Áudio no Idioma Correto:** Faixas de áudio escolhidas a dedo utilizando a leitura de cabeçalhos binários para reproduzir fielmente o que o nome do arquivo sugere (ex: evita erro de tocar Inglês em releases que são Dublados e Legendados).
- **Conta Francesa / `?lang=fr`:** Um caminho de rede projetado especificamente para priorizar torrents em Francês real com um proxy ultraleve próprio que não precisa do ffmpeg, otimizando o uso do debrid e qualidade do stream.
- **Legendas PT-BR e PT-PT:** Integração reforçada para usuários falantes da língua portuguesa (com fallback ao OpenSubtitles caso a fonte principal caia).
- **Estabilidade Aprimorada:** Resoluções para saltos, quadros duplicados no buffer e correções de estouro de memória durante a reprodução de mídias de altíssima taxa de bits (ex: 4K remuxes).
- **Segurança Máxima:** Controle severo de acesso público em diretórios como `.git`, arquivos `.json`, `config.php` e implementações SSRF robustas no proxy.

### Atualização 28/09/2025
- **TV ao Vivo:** Adição do DrewLive, aumentando a lista para mais de 7.000 canais ao vivo.
- **Streamio Sites & Debrid:** Adicionada nova integração de Debrid para aumentar as taxas de sucesso e caches corrigidos.
- **HeadlessVidX Aprimorado:** Grande reformulação do componente após meses inativo. Estabilização massiva do código.
- **Limpeza e Organização:** Remoção de fontes mortas para manter o software enxuto e produtivo.

---

## 🙏 Agradecimentos Especiais

O código fonte deste projeto foi originalmente criado por **Michell Smith a.k.a [gogetta69](https://github.com/gogetta69)**. 
Se você aprecia a fundação original deste projeto, por favor considere apoiar o criador:
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20Michell-F16061?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gogetta69)

O projeto desde então tem sido profundamente reatorado, modernizado e mantido por **[CyberPoison](https://github.com/CyberPoison)**.

---

## ⚖️ Aviso Legal

Este script recupera informações de metadados do TMDB e procura de forma autônoma por conteúdos relacionados em sites de terceiros baseados nas requisições dos usuários. A legalidade do streaming, scraping ou download através destas fontes externas de terceiros pode variar conforme a jurisdição do seu país. Por favor, utilize com responsabilidade, bom senso, e leve em consideração as diretrizes e leis éticas aplicáveis na sua localidade. O desenvolvedor exime-se de qualquer responsabilidade sobre o uso incorreto que se possa fazer do projeto.
