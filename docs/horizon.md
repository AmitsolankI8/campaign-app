# Laravel Horizon

Horizon is installed with its configuration in `config/horizon.php` and its application provider in `app/Providers/HorizonServiceProvider.php`.

## Run Horizon

Use Linux, WSL, or a Linux container with PHP's `pcntl` and `posix` extensions and a running Redis server. Native Windows/WAMP cannot run Horizon workers.

Set these values in that environment's `.env`, along with the appropriate Redis host, port, and credentials:

```dotenv
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
```

The Predis client is included, so the PHP Redis extension is optional. The existing local database queue remains configured until you switch it to Redis; Horizon does not process database queues.

```sh
php artisan config:clear
php artisan horizon
```

Run Horizon as the queue worker instead of the `queue:listen` process in `composer dev`. The dashboard is at `/horizon`. The generated authorization gate allows dashboard access locally and denies access outside `local` until explicitly configured.

For production, use a process monitor to keep `php artisan horizon` running and run `php artisan horizon:terminate` during deployment so the monitor restarts workers with the updated code. Configure dashboard authorization before enabling production access.

## Composer on Windows

To install the locked dependencies on native Windows for development:

```sh
composer install --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
```

These flags only allow installation; they do not enable Horizon workers on Windows. Run normal `composer install` on the Linux worker host so platform requirements are checked.

See the [Laravel Horizon documentation](https://laravel.com/docs/13.x/horizon) for worker configuration, metrics, and deployment details.
