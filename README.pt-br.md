# 🎧 Sintoniza

[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/manualdousuario/sintoniza/blob/master/README.md)
[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](https://github.com/manualdousuario/sintoniza/blob/master/README.pt-br.md)

Sintoniza é um poderoso servidor de sincronização de podcasts baseado no protocolo gPodder. Ele ajuda você a manter suas assinaturas, episódios e histórico de reprodução sincronizados em todos os seus dispositivos.

Este projeto é um fork do [oPodSync](https://github.com/kd2org/opodsync).

## ✨ Recursos

- Compatibilidade total com GPodder e NextCloud gPodder
- Rastreamento inteligente de assinaturas e histórico de episódios
- Sincronização perfeita entre dispositivos
- Metadados completos de podcasts e episódios
- Painel de estatísticas globais
- Interface administrativa para gerenciamento de usuários
- Desenvolvido com PHP 8.0+ e MySQL/MariaDB

## 📱 Aplicativos Testados

- [AntennaPod](https://github.com/AntennaPod/AntennaPod) 3.5.0 - Android

![AntennaPod 3.5.0](https://github.com/manualdousuario/sintoniza/blob/main/assets/antennapod_350.gif?raw=true)

- [Cardo](https://cardo-podcast.github.io) 1.90 - Windows/MacOS/Linux
- [Kasts](https://invent.kde.org/multimedia/kasts) 21.88 - [Windows](https://cdn.kde.org/ci-builds/multimedia/kasts/)/Android/Linux
- [gPodder](https://gpodder.github.io/) 3.11.4 - Windows/macOS/Linux/BSD

## 🐳 Instalação via Docker

### Pré-requisitos

Você só precisa ter instalado:
- Docker e docker compose

### Configuração

1. Primeiro, baixe o arquivo compose:
```bash
curl -o ./docker-compose.yml https://raw.githubusercontent.com/manualdousuario/sintoniza/main/docker-compose.yml
```

2. Configure as definições:
```bash
nano docker-compose.yml
```

3. Atualize as seguintes configurações:
```yaml
services:
  sintoniza:
    container_name: sintoniza
    image: ghcr.io/manualdousuario/sintoniza:latest
    ports:
      - "80:80"
    environment:
      SINTONIZA_DB_HOST: mariadb
      SINTONIZA_DB_USER: user
      SINTONIZA_DB_PASS: password
      SINTONIZA_DB_NAME: database_name
      SINTONIZA_DB_PORT: 3306
      BASE_URL: https://sintoniza.xyz/
      TITLE: Sintoniza
      DEBUG: true
      ENABLE_SUBSCRIPTIONS: true
      DISABLE_USER_METADATA_UPDATE: false
      SMTP_USER: email@email.com
      SMTP_PASS: password
      SMTP_HOST: smtp.email.com
      SMTP_FROM: email@email.com
      SMTP_NAME: "Sintoniza"
      SMTP_PORT: 587
      SMTP_SECURE: tls
      SMTP_AUTH: true
    depends_on:
      - db
  db:
    image: mariadb:10.11
    container_name: db
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: database_name
      MYSQL_USER: database_user
      MYSQL_PASSWORD: database_password
    ports:
      - 3306:3306
    volumes:
      - ./mariadb/data:/var/lib/mysql
```

Observação: Todas as variáveis de ambiente são obrigatórias.

4. Inicie os serviços:
```bash
docker compose up -d
```

## 🛠️ Manutenção

### Logs

Visualize os logs da aplicação:
```bash
docker-compose logs sintoniza
```

Informações de debug podem ser encontradas em `/app/logs`

### Segurança

Recomenda-se usar o [NGINX Proxy Manager](https://nginxproxymanager.com/) como serviço web na frente deste container para adicionar camadas de segurança e cache. Outros serviços web como Caddy também funcionarão corretamente.

---

Feito com ❤️! Se tiver dúvidas ou sugestões, abra uma issue que a gente ajuda! 😉

Uma instância pública está disponível em [PC do Manual](https://sintoniza.pcdomanual.com/)
