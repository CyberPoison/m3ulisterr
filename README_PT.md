# TMDB para VOD: Playlist Gratuita de TV ao Vivo, Filmes e Séries \[Xtream Codes e M3U8\]


[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)


## Atualização 14/09/2026

Uma grande revisão de estabilidade, idioma, legendas, análise de dados e segurança. Destaques:

- <strong>Dashboard analítico M3uListerr (novo):</strong> um `dashboard.php` independente que registra e visualiza cada reprodução — um globo interativo, gráficos e uma tabela de sessões mostrando título/pôster, filme vs série, conta, idioma de áudio solicitado e reproduzido, a versão exata e o serviço de debrid utilizado, progresso de reprodução (% e h:mm:ss), legendas oferecidas, país/cidade/ISP, dispositivo e user-agent, IP e tempo de resolução. Protegido por login com um banco de dados SQLite privado, além de um editor de `config.php` integrado e gerenciamento de credenciais. Veja [Dashboard M3uListerr](#dashboard-analítico-m3ulisterr).
- <strong>Áudio no idioma correto:</strong> lançamentos com múltiplos áudios não tocam mais o idioma errado. A faixa de áudio escolhida agora é baseada no próprio cabeçalho do arquivo (ex.: um lançamento "ITA ENG" toca em inglês, não em italiano) e a tag HLS `LANGUAGE` relata o que realmente está sendo reproduzido em vez de sempre afirmar ser inglês.
- <strong>Conta Francesa / `?lang=fr`:</strong> um caminho francês dedicado que aceita apenas lançamentos cujo áudio padrão seja realmente francês (verificado pelo cabeçalho do contêiner, não apenas a tag), preferindo torrents em cache e uma escala de qualidade x264 1080p→720p→SD (`?codec=x265` inverte para uma escala que prioriza HEVC). Streams franceses são servidos através de um leve byte-proxy (sem ffmpeg) que resolve a fonte uma vez e transmite por partes, evitando limites de taxa de IP do debrid.
- <strong>Legendas:</strong> faixas de legenda em Português Europeu (pt-PT) e Brasileiro (pt-BR) são oferecidas, com um fallback direto para a API do OpenSubtitles caso o provedor incluído esteja fora do ar, e suporte completo a legendas para episódios de séries de TV.
- <strong>Estabilidade de reprodução:</strong> corrigidos quadros duplicados / descontinuidades de timestamp nos limites do segmento, e corrigido um bug de esgotamento de memória que silenciosamente produzia segmentos vazios (buffering infinito) em fontes 4K/remux de alto bitrate.
- <strong>Reforço de Segurança:</strong> bloqueio de acesso público a arquivos sensíveis (`.git`, `cache.json`, logs, `config.php`, os dados privados do dashboard), adicionada uma proteção SSRF ao proxy de vídeo, e uma rigorosa Content-Security-Policy (Política de Segurança de Conteúdo), proteção CSRF, reforço de sessão e bloqueio de login no dashboard.

---

## Atualização 28/09/2025

- <strong>TV ao Vivo:</strong> Corrigida a seção de TV ao Vivo e adicionado o DrewLive, uma fonte massiva all-in-one com mais de 7.000 canais.
- <strong>Read Debrid:</strong> Corrigidas as verificações de cache do Read Debrid e adicionado o Streamio Sites como fonte de debrid (suporte para mais serviços de debrid em breve).
- <strong>Fontes de stream:</strong> Limpas e removidas várias fontes diretas de stream tanto no script principal quanto no HeadlessVidX para melhorar a estabilidade.
- <strong>VOD Adulto:</strong> Corrigida a fonte de VOD Adulto, a biblioteca de 10.000 filmes adultos agora atualiza automaticamente todo domingo.
- <strong>HeadlessVidX:</strong> Grande reformulação e correção de bugs. O software tinha diversos problemas e passei várias semanas estabilizando e trazendo-o ao padrão que eu queria.
- <strong>No Geral:</strong> Grande parte do projeto tinha quebrado após mais de um ano sem atualizações. As coisas estão funcionando muito melhor agora e tenho planos para adicionar mais funcionalidades nas próximas versões.

---

# Resumo

<p>Crie Playlists de Vídeo sob Demanda (VOD) de TV ao Vivo, Filmes e Séries usando os formatos Xtream Codes ou M3U8.

Gere playlists dinâmicas para TV ao Vivo, Filmes e Séries de TV usando uma versão simulada do Xtream Codes. Crie playlists de IPTV, Filmes e Séries com metadados abrangentes. Links de streaming localizados usando TMDB, Real-Debrid, Premiumize e Fontes Diretas. Ideal para uso com aplicativos como iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player e mais.</p>

<table style="border-collapse: collapse; border: none;">
  <tr>
    <td style="border: none;">
      <a href="https://github.com/CyberPoison/m3ulisterr/archive/refs/tags/latest.zip">
        <img src="https://img.shields.io/badge/Download%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="Download ZIP">
      </a>
    </td>
    <td style="border: none; padding-left: 10px;"> <!-- Ajuste o preenchimento conforme necessário -->
      <a href="https://ko-fi.com/gogetta69">
        <img src="https://img.shields.io/badge/Ko--fi-Support-F16061?style=for-the-badge&logo=ko-fi&logoColor=white" alt="Ko-fi">
      </a>
    </td>
  </tr>
</table>

# Vídeo de Demonstração

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="Demo GIF" width="70%">
<br><br>

# Capturas de Tela

<table>
  <tr>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110311.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110433.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110501.png" width="400">
    </td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110535.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110653.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110819.png" width="400">
    </td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110832.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110847.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623111001.png" width="400">
    </td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623111026.png" width="400">
    </td>
    <!-- Adicione mais imagens e linhas conforme necessário -->
  </tr>
</table>

# Funcionalidades

- Geração dinâmica de playlist para TV ao vivo, filmes e séries de TV
- Integração com TMDB, Real Debrid, Premiumize e fontes diretas para melhor recuperação de conteúdo
- Emulação do software Xtream Codes para detalhes completos de metadados
- Inclusão de fontes de TV ao Vivo como [Daddylive](https://href.li/?https://dlhd.so/24-7-channels.php), [TheTVApp](https://href.li/?https://thetvapp.to/), [MoveOnJoy](https://i.imgur.com/dFazdys.png), [Streamed Su Sports](https://href.li/?https://streamed.pk/), [Pluto TV](https://href.li/?https://downloads.pluto.tv/docs/pluto_tv_channels_listing.pdf) e mais.
- A maioria dos canais de TV ao vivo inclui informações detalhadas do Guia de TV (EPG).
- Cache automático de links de streaming encontrados para reprodução eficiente
- 10 mil filmes adultos completos adicionados ao VOD (desativado por padrão)
- Seleção de áudio no idioma correto para lançamentos com múltiplos áudios (lê o cabeçalho do arquivo, não apenas a tag)
- Caminho Francês dedicado (`UnlimitedFR` / `?lang=fr`) com áudio francês verificado no cabeçalho e uma escala de qualidade que prioriza cache
- Faixas de legendas em Português Europeu (pt-PT) e Brasileiro (pt-BR), com fallback direto para a API do OpenSubtitles
- Dashboard analítico **M3uListerr**: estatísticas de reprodução, globo interativo, gráficos e tabela por sessão (veja abaixo)

# Começando (Instalação e Uso)

[![Thumbnail do Vídeo](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **Configuração**: Comece configurando o script com a [Chave API gratuita do TMDB](https://developer.themoviedb.org/docs/getting-started) obrigatória e uma chave privada opcional para [Real Debrid](https://real-debrid.com/apitoken) ou [Premiumize](https://www.premiumize.me/account), que não são obrigatórias.

2. **Integração Xtream Codes**: Insira o endereço IP ou domínio como um servidor Xtream Codes. Qualquer nome de usuário e senha funcionarão, pois o script não requer autenticação. Isso carregará automaticamente as playlists de TV ao Vivo, Filmes e Séries no aplicativo.

3. **Apps Não-Xtream Codes**: Se seu aplicativo não suporta Xtream Codes, carregue http://ENDERECO_IP/player_api.php?action=get_vod_streams (substitua ENDERECO_IP pelo IP do seu computador) em seu navegador, em seguida localize o `playlist.m3u8` na mesma pasta do script e carregue-o como uma playlist M3U. Note que as playlists M3U8 estão disponíveis apenas para filmes e TV ao vivo; séries de TV não podem ser carregadas como playlist M3U.

4. **Reprodução**: Assim que tudo estiver configurado e as playlists carregadas, você poderá reproduzir um vídeo. Clicar no botão play fará com que o script procure em múltiplos sites em segundo plano por um link executável. Por favor, seja paciente e permita algum tempo para um link ser encontrado e o streaming começar. O script armazena em cache o link encontrado por aproximadamente 3 horas, alinhando-se com a expiração típica do token de acesso da maioria das fontes diretas, que ocorre em cerca de 4 horas.

5. **Hospedagem Local**: Se você não possui uma empresa de hospedagem para executar este script extremamente leve, pode instalar e executar softwares em seu computador desktop como Xampp.

# Mudanças e Adições

- Adicionado o serviço Premiumize como alternativa ao Real-Debrid. (usado apenas com sites de torrent)
- Adicionadas threads ao pesquisar sites de torrent por links magnéticos. (acelera o tempo necessário para encontrar um link)
- Adicionadas e corrigidas fontes diretas de filmes e séries de TV, bem como mais extratores de links.
- Adicionada a seção de esportes do TheTvApp na Playlist de TV ao Vivo (configure seu app para carregar EPG e playlist a cada 12 horas ou menos.)
- Adicionado o PlutoTV à playlist de TV ao vivo (Múltiplos Idiomas Aqui: https://github.com/matthuisman/i.mjh.nz)
- Redesenhadas as funções e playlist de TV ao Vivo e DaddyLive. (todas as imagens na playlist estão funcionando)
- Corrigidos vários bugs nas funções de busca e filtragem de torrent. (agora encontra links com muito mais frequência)
- Corrigida a ordenação por resolução, mais provável de obter links de maior qualidade (sites de torrent)
- Adicionados filmes adultos ao VOD (desativado por padrão)<br>

# O que é HeadlessVidX?

HeadlessVidX é uma ferramenta projetada para simplificar o desenvolvimento de extratores de vídeo para sites de streaming. Ele oferece uma solução fácil de usar para os usuários, independentemente de suas habilidades de programação, para adicionar rapidamente sites de streaming de vídeo a ferramentas como 'TMDB TO VOD'.
<table>
  <tr>
<td align="center">
        <img src="https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/Screenshot%202024-06-14%20at%2016-41-13%20HeadlessVidX%20-%20Home.png" width="400">
    </td>
    <td align="center">
      <img src="https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/Screenshot%202024-06-14%20at%2016-40-15%20HeadlessVidX%20-%20Trainer.png" width="400">   
    </td>
  </tr>
</table>

# Criando Playlists

Você não precisa mais executar manualmente create_playlist.php e create_tv_playlist.php. Com o fluxo de trabalho configurado no GitHub, essas playlists são geradas automaticamente duas vezes por dia. Para criar sua própria playlist de filmes e séries, basta definir $userCreatePlaylist como true no arquivo config.php.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b






## Implantação com Docker

Agora você pode rodar toda a stack usando Docker e Docker Compose.

### Pré-requisitos
- Docker e Docker Compose instalados.

### Início Rápido
1. Clone o repositório.
2. Configure o seu `config.php` (ou use variáveis de ambiente para algumas configurações).
3. Execute o seguinte comando no diretório raiz:
   ```bash
   docker-compose up -d
   ```
4. Acesse o site em `http://localhost:8080`.

### GitHub Actions
O projeto inclui um fluxo de trabalho do GitHub Actions `.github/workflows/deploy.yml` que compila e faz push da imagem Docker automaticamente para o GitHub Container Registry (GHCR) em cada push na branch `main`.

### Variáveis de Ambiente
As seguintes variáveis de ambiente podem ser usadas para configurar o contêiner:
- `HEADLESSVIDX_ADDRESS`: O endereço do serviço HeadlessVidX (padrão: `localhost:3202`). No docker-compose, isso é definido como `headlessvidx:3202`.

# Dashboard Analítico M3uListerr

### Dashboard Screenshots

![Overview](wiki/Overview.png)
![Globe](wiki/Globe.png)
![Sessions](wiki/Sessions.png)
![Titles](wiki/Titles.png)
![Cache Logs](wiki/cache%20logs.png)


O `dashboard.php` é um painel de controle e análise autônomo e protegido por login para o servidor. Ele registra um evento leve por reprodução (eventos de resolução, playlist, segmento, legenda e proxy) em um log privado, importa-os para um banco de dados SQLite local, e os renderiza em um dashboard moderno.

### O que ele mostra
- **Visão Geral** — total de sessões, resoluções, espectadores únicos e países; tempos médios de resolução e entrega de segmento; contagem de acertos/falhas de cache; e gráficos de barras para serviço de debrid utilizado, país, dispositivo/player, conta, idioma, **ISP do cliente** e **filmes vs séries de TV**.
- **Globo** — um globo 3D interativo mapeando de onde cada filme/série de TV foi solicitado.
- **Sessões** — uma tabela por reprodução com pôster e título, **tipo (Filme/Série) e código do episódio**, conta (e sua senha), idioma solicitado e de áudio, o nome exato da **versão e seus idiomas**, o **serviço de debrid (AD/PM) e provedor**, **progresso da reprodução** (% e `h:mm:ss` alcançados), as faixas de legenda oferecidas, país + bandeira, cidade/cep, **ISP do cliente**, dispositivo, **user-agent** e IP, e o tempo de resolução (ou marcador de cache).
- **Títulos** — filmes e séries de TV mais solicitados em uma grade de pôsteres.
- **Cache** — um visualizador para as entradas do arquivo `cache.json` do resolver.
- **Configuração** — edite um conjunto de configurações permitidas do `config.php` a partir do navegador (um backup com timestamp é gravado e o arquivo tem sua sintaxe verificada antes de ser substituído).
- **Conta** — mude o nome de usuário e a senha do dashboard.

### Segurança
- A primeira execução solicita a criação de uma conta admin; a senha é armazenada em hash (Argon2id) num banco de dados SQLite privado, nunca no código.
- Todo o estado é mantido em um diretório privado `m3ulisterr_data/` que o servidor web recusa servir (Banco de dados SQLite, log de eventos, sessões e um segredo por instalação); ele é ignorado pelo git e nunca deve ser feito commit dele.
- Uma rigorosa Content-Security-Policy (com um nonce por resposta), tokens CSRF em toda gravação, vinculação de sessões/timeouts e limitação de tentativas de login protegem o dashboard. As bibliotecas de gráficos incluídas são servidas a partir do disco após o login ao invés de qualquer CDN.
- A geolocalização de IP (país/cidade/ISP) utiliza o serviço gratuito ip-api.com; cada IP de espectador é procurado apenas uma vez e colocado em cache.

### Como começar
1. Implante os arquivos como de costume (o dashboard não precisa de configurações adicionais).
2. Garanta que o app possa criar um diretório gravável `m3ulisterr_data/` junto ao site (ele é criado automaticamente quando gravável).
3. Abra `http://SEU_SERVIDOR/dashboard.php`, crie a conta de admin, e faça o login. Os dados são importados no primeiro carregamento e sempre que você pressionar **Refresh data (Atualizar dados)**.


# 🙏 Special Thanks

This project's source code was originally created by **Michell Smith a.k.a [gogetta69](https://github.com/gogetta69)**. 
If you appreciate the original foundation of this project, please consider supporting them:
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20Michell-F16061?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gogetta69)

The project has since been significantly refactored, modernized, and maintained by **[CyberPoison](https://github.com/CyberPoison)**.

---

# Aviso Legal

Este script recupera informações de filmes do TMDB e procura por conteúdos relacionados em sites de terceiros. A legalidade do streaming ou download de conteúdos através destes sites é incerta. Por favor, exerça cautela e considere as implicações legais e éticas do uso deste script para acessar e consumir conteúdo protegido por direitos autorais. Sempre respeite as leis de direitos autorais e os termos de serviço dos sites que você visita.
