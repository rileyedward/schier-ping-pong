# Schier Pong

Ping-pong league tracker for recording leagues, seasons, matchups, and player ratings.

## Overview

### What is Schier Pong?

Schier Pong is a web application for running ping-pong leagues. It lets organizers create leagues and seasons, register players, record matchup results, and track rating changes over time.

### Key Features

- **League Management**: Create and manage multiple ping-pong leagues.
- **Season Management**: Organize play into discrete seasons within each league.
- **Player Profiles**: Track players and their participation across seasons.
- **Matchup Recording**: Log match results between players.
- **Rating Tracking**: Automatically compute and store rating changes from each matchup.
- **Authentication**: User accounts powered by Laravel Fortify.

## Getting Started

### Prerequisites

Ensure you have the following prerequisites installed on your system. You can verify each installation by running the provided commands in your terminal.

1. **PHP** (8.3+) is required for the application. Check if PHP is installed by running:

   ```bash
   php --version
   ```

2. **Composer** is necessary for managing PHP dependencies. Verify its installation with:

   ```bash
   composer --version
   ```

3. **Node** and **NPM** are needed for managing frontend dependencies. Check their installations with:

   ```bash
   node --version
   npm --version
   ```

### Installation

1. Duplicate the example environment file and configure it with your settings:

   ```bash
   cp .env.example .env
   ```

2. Install PHP and JavaScript dependencies:

   ```bash
   composer install
   npm install
   ```

3. Generate a new PHP application key:

   ```bash
   php artisan key:generate
   ```

4. Create the SQLite database file:

   ```bash
   touch database/database.sqlite
   ```

5. Apply database migrations and seed test data:

   ```bash
   php artisan migrate --seed
   ```

6. Start the development environment (server, queue worker, log tail, and Vite dev server):

   ```bash
   composer dev
   ```

   Alternatively, run the backend and frontend separately:

   ```bash
   php artisan serve
   npm run dev
   ```

## Testing

Run the Pest test suite along with Pint formatting checks:

```bash
composer test
```

## Tech Stack

- **Laravel 13** — PHP application framework
- **Inertia.js** — SPA-style routing bridge
- **Vue 3** + **TypeScript** — frontend
- **Tailwind CSS v4** + **reka-ui** — styling and components
- **Laravel Fortify** — authentication
- **Laravel Wayfinder** — typed route helpers
- **Pest** — testing
- **Pint** — PHP code style
