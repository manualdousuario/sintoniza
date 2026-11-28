# 🎧 Sintoniza

[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://ghcr.io/manualdousuario/sintoniza)
[![PHP](https://img.shields.io/badge/PHP-8.4+-purple?logo=php)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-red?logo=laravel)](https://laravel.com)

Sintoniza is a small podcast synchronization server for apps that speak the gPodder or Nextcloud gpoddersync API. It keeps subscriptions and listening progress in sync across your devices, and does nothing else.

A public instance is available at [PC do Manual](https://sintoniza.pcdomanual.com/)

## ✨ Features

- gPodder API v1/v2 and Nextcloud gpoddersync compatibility (subscriptions, devices, episode actions)
- One account page with the connection details, devices, subscriptions (with OPML export) and settings
- Daily pruning that keeps only the latest action per episode and device
- Optional deletion of inactive accounts
- Administrative interface for user management (Filament, at `/admin`)
- No feed crawler, no queue worker, no front-end JavaScript: PHP 8.4, Laravel 13 and MySQL/MariaDB

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
nano compose.yml
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
| INACTIVE_USER_MONTHS | Delete non-admin users (and all their data) after this many months of inactivity. `0` (default) never deletes | 0 |

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

## ⬆️ Upgrading to v4

- The `2026_11_28_000000_drop_feed_tables` migration runs on start and **deletes** the `feeds`, `feed_aliases` and `episodes` tables, plus the `subscriptions.feed_id` and `episode_actions.episode_id` columns. Subscriptions, devices and episode actions are kept. Back up the database first if you want to keep the fetched feed data.
- `sintoniza:prune-episode-actions` runs every day at 04:00 and keeps only the latest action per episode and device. Run it once by hand with `--dry-run` to see how much it will remove.
- The queue worker is gone; only the scheduler runs in the container.
- The web pages for podcasts, episodes, latest updates and adding podcasts were removed. `/dashboard` is now the single account page; old profile and devices URLs redirect there.
- `PODCAST_INDEX_*` and `IMMEDIATE_FEED_FETCH` are no longer read and can be removed from your compose file.
- No API URL or response format changed: apps keep working without reconfiguration.

---

This project is a fork of [oPodSync](https://github.com/kd2org/opodsync).

Made with ❤️! If you have questions or suggestions, open an issue and we'll help! 😉
