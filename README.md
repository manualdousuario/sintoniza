# 🎧 Sintoniza

[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/manualdousuario/sintoniza/blob/master/README.md)
[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](https://github.com/manualdousuario/sintoniza/blob/master/README.pt-br.md)

Sintoniza is a powerful podcast synchronization server based on the gPodder protocol. It helps you keep your podcast subscriptions, episodes, and listening history in sync across all your devices.

This project is a fork of [oPodSync](https://github.com/kd2org/opodsync).

## ✨ Features

- Full compatibility with GPodder and NextCloud gPodder
- Smart subscription and episode history tracking
- Seamless device-to-device synchronization
- Complete podcast and episode metadata
- Global statistics dashboard
- Administrative interface for user management
- Built with PHP 8.0+ and MySQL/MariaDB

## 📱 Tested Applications

- [AntennaPod](https://github.com/AntennaPod/AntennaPod) 3.5.0 - Android

![AntennaPod 3.5.0](https://github.com/manualdousuario/sintoniza/blob/main/assets/antennapod_350.gif?raw=true)

- [Cardo](https://cardo-podcast.github.io) 1.90 - Windows/MacOS/Linux
- [Kasts](https://invent.kde.org/multimedia/kasts) 21.88 - [Windows](https://cdn.kde.org/ci-builds/multimedia/kasts/)/Android/Linux
- [gPodder](https://gpodder.github.io/) 3.11.4 - Windows/macOS/Linux/BSD

## 🐳 Docker Installation

### Prerequisites

You only need:
- Docker and docker compose

### Setup

1. First, get the compose file:
```bash
curl -o ./docker-compose.yml https://raw.githubusercontent.com/manualdousuario/sintoniza/main/docker-compose.yml
```

2. Configure the settings:
```bash
nano docker-compose.yml
```

3. Update the following configuration:
```yaml
services:
  sintoniza:
    container_name: sintoniza
    image: ghcr.io/manualdousuario/sintoniza:latest
    ports:
      - "80:80"
    environment:
      SINTONIZA_DB_HOST: ${DB_HOST:-db}
      SINTONIZA_DB_USER: ${DB_USER}
      SINTONIZA_DB_PASS: ${DB_PASS}
      SINTONIZA_DB_NAME: ${DB_NAME}
      SINTONIZA_DB_PORT: ${DB_PORT:-3306}
      BASE_URL: ${BASE_URL:-https://sintoniza.xyz/}
      TITLE: ${TITLE:-Sintoniza}
      ADMIN_PASSWORD: ${ADMIN_PASSWORD:-p@ssw0rd}
      DEBUG: ${DEBUG:-false}
      ENABLE_SUBSCRIPTIONS: ${ENABLE_SUBSCRIPTIONS:-false}
      DISABLE_USER_METADATA_UPDATE: ${DISABLE_USER_METADATA_UPDATE:-false}
      SMTP_USER: ${SMTP_USER}
      SMTP_PASS: ${SMTP_PASS}
      SMTP_HOST: ${SMTP_HOST}
      SMTP_FROM: ${SMTP_FROM}
      SMTP_NAME: ${SMTP_NAME:-"Sintoniza"}
      SMTP_PORT: ${SMTP_PORT:-587}587
      SMTP_SECURE: ${SMTP_SECURE:-tls}
      SMTP_AUTH: ${SMTP_AUTH:-true}
    depends_on:
      - db
  db:
    image: mariadb:10.11
    container_name: db
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASS}
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASS}
    ports:
      - 3306:3306
    volumes:
      - ./mariadb/data:/var/lib/mysql
```

Note: All environment variables are required.

### Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| SINTONIZA_DB_HOST | Database host address | db |
| SINTONIZA_DB_USER | Database username | user |
| SINTONIZA_DB_PASS | Database password | password |
| SINTONIZA_DB_NAME | Database name | database_name |
| SINTONIZA_DB_PORT | Database port | database_port |
| BASE_URL | Base URL for the application | https://sintoniza.xyz/ |
| TITLE | Application title | Sintoniza |
| ADMIN_PASSWORD | Administrator password | p@ssw0rd |
| DEBUG | Enable debug mode | true |
| ENABLE_SUBSCRIPTIONS | Allow subscriptions | true |
| DISABLE_USER_METADATA_UPDATE | Prevent users from updating their metadata | false |
| SMTP_USER | SMTP username for email | email@email.com |
| SMTP_PASS | SMTP password | password |
| SMTP_HOST | SMTP server host | smtp.email.com |
| SMTP_FROM | Email address to send from | email@email.com |
| SMTP_NAME | Sender name for emails | "Sintoniza" |
| SMTP_PORT | SMTP server port | 587 |
| SMTP_SECURE | SMTP security type | tls |
| SMTP_AUTH | Enable SMTP authentication | true |

4. Start the services:
```bash
docker compose up -d
```

## 🛠️ Maintenance

### Logs

View application logs:
```bash
docker-compose logs sintoniza
```

Debug information can be found in `/app/logs`

### Security

It's recommended to use [NGINX Proxy Manager](https://nginxproxymanager.com/) as a frontend web service for this container to add security and caching layers. Other web services like Caddy will also work correctly.

---

Made with ❤️! If you have questions or suggestions, open an issue and we'll help! 😉

A public instance is available at [PC do Manual](https://sintoniza.pcdomanual.com/)
