# 🎧 Sintoniza

[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://ghcr.io/butialabs/lastfm)
[![PHP](https://img.shields.io/badge/PHP-8.4+-purple?logo=php)](https://php.net)

Sintoniza is a powerful podcast synchronization server based on the gPodder protocol. It helps you keep your podcast subscriptions, episodes, and listening history in sync across all your devices!

A public instance is available at [PC do Manual](https://sintoniza.pcdomanual.com/)

## ✨ Features

- Full compatibility with GPodder and NextCloud gPodder
- Smart subscription and episode history tracking
- Seamless device-to-device synchronization
- Complete podcast and episode metadata (via PodcastIndex + RSS)
- Global statistics dashboard
- Administrative interface for user management
- Built with PHP 8.4 and MySQL/MariaDB

## 📱 Tested Applications

- [AntennaPod](https://github.com/AntennaPod/AntennaPod) 3.5.0+ - Android
- [Cardo](https://cardo-podcast.github.io) 1.90+ - Windows/MacOS/Linux
- [Kasts](https://invent.kde.org/multimedia/kasts) 21.88+ - [Windows](https://cdn.kde.org/ci-builds/multimedia/kasts/)/Android/Linux
- [gPodder](https://gpodder.github.io) 3.11.4+ - Windows/macOS/Linux
- [YourPods](https://apps.apple.com/us/app/yourpods-podcast-player/id6757721236) 2+ - iOS

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
      MYSQL_HOST: ${DB_HOST:-db}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASS}
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_PORT: ${DB_PORT:-3306}
      BASE_URL: ${BASE_URL:-https://sintoniza.xyz/}
      TITLE: ${TITLE:-Sintoniza}
      DEBUG: ${DEBUG:-false}
      ENABLE_SUBSCRIPTIONS: ${ENABLE_SUBSCRIPTIONS:-false}
      SMTP_USER: ${SMTP_USER}
      SMTP_PASS: ${SMTP_PASS}
      SMTP_HOST: ${SMTP_HOST}
      SMTP_FROM: ${SMTP_FROM}
      SMTP_NAME: ${SMTP_NAME:-"Sintoniza"}
      SMTP_PORT: ${SMTP_PORT:-587}
      SMTP_SECURE: ${SMTP_SECURE:-tls}
      SMTP_AUTH: ${SMTP_AUTH:-true}
      PODCAST_INDEX_API_KEY: ${PODCAST_INDEX_API_KEY}
      PODCAST_INDEX_API_SECRET: ${PODCAST_INDEX_API_SECRET}
      PODCAST_INDEX_USE_AS_PRIMARY: ${PODCAST_INDEX_USE_AS_PRIMARY:-true}
      PODCAST_INDEX_FALLBACK_TO_RSS: ${PODCAST_INDEX_FALLBACK_TO_RSS:-true}
      SESSION_NAME: ${SESSION_NAME:-sintoniza_session}
      SESSION_LIFETIME: ${SESSION_LIFETIME:-86400}
      SESSION_SECURE: ${SESSION_SECURE:-true}
      SESSION_HTTP_ONLY: ${SESSION_HTTP_ONLY:-true}
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

Note: All environment variables without defaults are required.

### Environment Variables

#### Core

| Variable | Description | Example |
|----------|-------------|---------|
| MYSQL_HOST | Database host address | db |
| MYSQL_USER | Database username | user |
| MYSQL_PASSWORD | Database password | password |
| MYSQL_DATABASE | Database name | database_name |
| MYSQL_PORT | Database port | 3306 |
| BASE_URL | Base URL for the application | https://sintoniza.xyz/ |
| TITLE | Application title | Sintoniza |
| DEBUG | Enable debug mode (Whoops error handler) | true |
| ENABLE_SUBSCRIPTIONS | Allow new user registrations | true |

#### SMTP (password recovery & notifications)

| Variable | Description | Example |
|----------|-------------|---------|
| SMTP_USER | SMTP username | email@email.com |
| SMTP_PASS | SMTP password | password |
| SMTP_HOST | SMTP server host | smtp.email.com |
| SMTP_FROM | Email address to send from | email@email.com |
| SMTP_NAME | Sender name for emails | "Sintoniza" |
| SMTP_PORT | SMTP server port | 587 |
| SMTP_SECURE | SMTP security type (tls/ssl) | tls |
| SMTP_AUTH | Enable SMTP authentication | true |

#### PodcastIndex (optional but recommended)

| Variable | Description | Example |
|----------|-------------|---------|
| PODCAST_INDEX_API_KEY | API key from [podcastindex.org](https://api.podcastindex.org/) | — |
| PODCAST_INDEX_API_SECRET | API secret from PodcastIndex | — |
| PODCAST_INDEX_USE_AS_PRIMARY | Use PodcastIndex as the primary metadata source | true |
| PODCAST_INDEX_FALLBACK_TO_RSS | Fall back to parsing the raw RSS feed when PodcastIndex fails | true |

#### Session

| Variable | Description | Example |
|----------|-------------|---------|
| SESSION_NAME | Session cookie name | sintoniza_session |
| SESSION_LIFETIME | Session lifetime in seconds | 86400 |
| SESSION_SECURE | Send session cookie only over HTTPS | true |
| SESSION_HTTP_ONLY | Mark session cookie as HttpOnly | true |

4. Start the services:
```bash
docker compose up -d
```

Database migrations are applied automatically on container startup via Phinx.

## 🛠️ Maintenance

### Logs

View application logs:
```bash
docker compose logs sintoniza
```

Application logs written by Monolog are available inside the container at `/app/logs`.

### Security

It's recommended to use [NGINX Proxy Manager](https://nginxproxymanager.com/) as a frontend web service for this container to add security and caching layers. Other web services like Caddy will also work correctly.

## ⚠️ Breaking changes

### 2.1.2 — Feed canonicalization & deduplication

This release introduces aggressive feed URL normalization, HTTP redirect tracking, and RSS-native canonical detection (`<atom:link rel="self">`, `<itunes:new-feed-url>`). The migration `20260424130000_normalize_v2_and_dedup` runs automatically on startup and may collapse a large number of duplicate feed rows in the existing database.

After the upgrade, existing instances should run the new one-shot CLI command to opportunistically refetch every active feed and merge variants that only become detectable through HTTP redirects or RSS-declared canonical URLs:

```bash
php cli/sintoniza recanonicalize --sleep-ms=200
```

This is safe to interrupt and re-run; merges are recorded in the new `feed_aliases` table so legacy subscription URLs continue to resolve to the canonical feed.

---

This project is a fork of [oPodSync](https://github.com/kd2org/opodsync).

Made with ❤️! If you have questions or suggestions, open an issue and we'll help! 😉
