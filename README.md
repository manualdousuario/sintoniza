# 🎧 Sintoniza

[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://ghcr.io/manualdousuario/sintoniza)
[![PHP](https://img.shields.io/badge/PHP-8.4+-purple?logo=php)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-red?logo=laravel)](https://laravel.com)

Sintoniza is a powerful podcast synchronization server based on the gPodder protocol. It helps you keep your podcast subscriptions, episodes, and listening history in sync across all your devices!

A public instance is available at [PC do Manual](https://sintoniza.pcdomanual.com/)

## ✨ Features

- Full compatibility with GPodder and NextCloud gPodder
- Smart subscription and episode history tracking
- Seamless device-to-device synchronization
- Administrative interface for user management (Filament, at `/admin`)
- Global statistics dashboard
- Built with PHP 8.4, Laravel 13, Livewire, Tailwind CSS and MySQL/MariaDB

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
curl -o ./compose.yml https://raw.githubusercontent.com/manualdousuario/sintoniza/main/compose.yml
```

2. Configure the settings:
```bash
nano docker-compose.yml
```

3. Start the services:
```bash
docker compose up -d
```

### Environment Variables

#### Core

| Variable | Description | Example |
|----------|-------------|---------|
| APP_KEY | **Required.** Encryption key | base64:... |
| DB_HOST | Database host address | db |
| DB_USERNAME | Database username | user |
| DB_PASSWORD | **Required.** Database password | password |
| DB_DATABASE | Database name | database_name |
| DB_PORT | Database port | 3306 |
| APP_URL | Base URL for the application | https://sintoniza.xyz/ |
| APP_NAME | Application title | Sintoniza |
| APP_DEBUG | Enable debug mode. Keep this `false` in production | false |
| ENABLE_SUBSCRIPTIONS |Allow new user registrations | true |

#### SMTP

| Variable | Description | Example |
|----------|-------------|---------|
| MAIL_USERNAME | SMTP username | email@email.com |
| MAIL_PASSWORD | **Required.** SMTP password | password |
| MAIL_HOST | SMTP server host | smtp.email.com |
| MAIL_FROM_ADDRESS | Email address to send from | email@email.com |
| MAIL_FROM_NAME | Sender name for emails | "Sintoniza" |
| MAIL_PORT | SMTP server port | 587 |
| MAIL_SCHEME | SMTP security type (tls/ssl) | tls |

#### PodcastIndex (optional but recommended)

| Variable | Description | Example |
|----------|-------------|---------|
| PODCAST_INDEX_API_KEY | API key from [podcastindex.org](https://api.podcastindex.org/) | — |
| PODCAST_INDEX_API_SECRET | API secret from PodcastIndex | — |
| PODCAST_INDEX_USE_AS_PRIMARY | Use PodcastIndex as the primary metadata source | true |
| PODCAST_INDEX_FALLBACK_TO_RSS | Fall back to parsing the raw RSS feed when PodcastIndex fails | true |

---

This project is a fork of [oPodSync](https://github.com/kd2org/opodsync).

Made with ❤️! If you have questions or suggestions, open an issue and we'll help! 😉
