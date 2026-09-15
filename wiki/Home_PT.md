# TMDB para VOD - Wiki Oficial

Bem-vindo à wiki do projeto TMDB para VOD. Este guia fornece detalhes abrangentes sobre como instalar, utilizar o Docker, os comandos/configurações disponíveis, e como o sistema funciona nos bastidores.

## Como Funciona

O projeto gera dinamicamente playlists de TV ao Vivo, Filmes e Séries de TV (Video on Demand - VOD) utilizando formatos como **Xtream Codes** e **M3U8**. O sistema usa uma versão simulada de Xtream Codes para entregar metadados completos de IPTV para seu player de escolha (como iMplayer, Tivimate, IPTV Streamers Pro, etc.).

Quando você seleciona um filme ou série:
1. O sistema utiliza a API do **TMDB** para buscar os metadados.
2. Em segundo plano, o script pesquisa e encontra links de streaming a partir de fontes como **Real-Debrid**, **Premiumize** ou fontes diretas de vídeo (com o uso do **HeadlessVidX**).
3. O vídeo é resolvido e o arquivo é processado, mantendo um armazenamento temporário (cache) por aproximadamente 3 horas.
4. O áudio do idioma correto é escolhido verificando os cabeçalhos dos arquivos para precisão.
5. Um sistema de legendas busca legendas no OpenSubtitles caso não haja outra disponível.
6. A reprodução tem análises detalhadas salvas através do dashboard **M3uListerr**.

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="Demo GIF" width="70%">

---

## Instalação (Começando)

Siga os passos abaixo para iniciar e configurar seu servidor local ou em nuvem:

1. **Configuração da API**: 
   - Obtenha uma [Chave API gratuita do TMDB](https://developer.themoviedb.org/docs/getting-started).
   - Opcionalmente, crie chaves privadas para serviços como [Real Debrid](https://real-debrid.com/apitoken) ou [Premiumize](https://www.premiumize.me/account) (altamente recomendado para conteúdos premium).
   - Configure o arquivo `config.php` com as suas chaves e definições.

2. **Integração Xtream Codes** (Recomendado):
   - No seu aplicativo player de IPTV, insira o endereço IP ou domínio do servidor (onde o script está hospedado) como um servidor Xtream Codes. 
   - Você pode usar qualquer nome de usuário e senha (o sistema não exige autenticação real para esta simulação).

3. **Apps Não-Xtream Codes (M3U)**:
   - Se seu aplicativo só suporta M3U, navegue até: `http://ENDERECO_IP/player_api.php?action=get_vod_streams` no seu navegador.
   - Isso irá gerar um arquivo `playlist.m3u8` no diretório do script, que você pode importar no seu player.
   - *Nota:* Playlists M3U8 estão disponíveis apenas para filmes e TV ao vivo. Séries necessitam de Xtream Codes.

4. **Hospedagem Local**:
   - Caso você queira rodar diretamente no seu desktop, você pode usar um ambiente como **XAMPP** para servir os arquivos PHP sem necessidade de alugar servidores dedicados.

---

## Implantação com Docker

Para uma instalação mais limpa e moderna, sugerimos usar Docker e Docker Compose, já embutidos no projeto.

### Pré-requisitos
- **Docker** e **Docker Compose** instalados no seu sistema.

### Início Rápido com Docker
1. Clone este repositório para a sua máquina.
2. Defina e configure o seu `config.php` ou configure as variáveis de ambiente necessárias.
3. No terminal, na raiz do projeto, execute:
   ```bash
   docker-compose up -d
   ```
4. O servidor de VOD estará acessível localmente através de `http://localhost:8080`.

### Variáveis de Ambiente
- `HEADLESSVIDX_ADDRESS`: Configura o endereço do microserviço HeadlessVidX (o padrão é `localhost:3202`. Se você usa docker-compose, já fica configurado como `headlessvidx:3202`).

---

## Painel de Controle e Comandos (Dashboard M3uListerr)

Uma vez que o projeto esteja rodando, você pode acessar seu Dashboard Analítico:

- **URL de acesso**: `http://SEU_SERVIDOR/dashboard.php`
- O dashboard requer a criação de um usuário admin na primeira execução.

### Funcionalidades do Dashboard
- **Estatísticas Globais**: Monitore onde (por país e ISP) as requisições estão acontecendo no "Globo 3D".
- **Sessões ao vivo e Tabela**: Veja exatamente o título sendo reproduzido, a conta em uso, resolução de tempo e progresso (`h:mm:ss`).
- **Gerenciador de Configurações (Commands & Config)**: Você pode editar configurações essenciais do `config.php` diretamente pelo navegador de forma segura. O sistema verifica a sintaxe (syntax check) antes de aplicar.
- **Segurança Reforçada**: Tudo fica contido na pasta `m3ulisterr_data/`, usando banco SQLite protegido contra acesso público.



### Dashboard Screenshots

![Overview](Overview.png)
![Globe](Globe.png)
![Sessions](Sessions.png)
![Titles](Titles.png)
![Cache Logs](cache%20logs.png)

