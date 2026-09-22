# Docker development

Docker and WAMP share the project source and Laravel `.env`, but they use
different host ports. Docker overrides only the service addresses that must be
resolved inside its network, so the WAMP database and Redis settings remain
unchanged.

## Start the stack

```bash
docker compose up --build -d
docker compose exec app php artisan migrate --seed
```

The default Docker endpoints are:

- Application: <http://localhost:8080>
- Vite: <http://127.0.0.1:5174>
- phpMyAdmin: <http://localhost:8081>
- MySQL from the host: `127.0.0.1:13306`
- Redis from the host: `127.0.0.1:16379`

Log in to Docker phpMyAdmin with the `DOCKER_DB_USERNAME` and
`DOCKER_DB_PASSWORD` values. The defaults are `campaign_app` and `secret`.

## Change Docker ports

Set only the `DOCKER_*` values in `.env`. These values are used by Docker
Compose and do not change the existing WAMP `APP_URL`, `DB_*`, or `REDIS_*`
settings.

For example:

```dotenv
DOCKER_APP_PORT=8080
DOCKER_VITE_PORT=5174
DOCKER_PHPMYADMIN_PORT=8081
DOCKER_DB_PORT=13306
DOCKER_REDIS_PORT=16379
```

Inside Docker, Laravel always connects to `mysql:3306` and `redis:6379`.
Those internal ports should not be replaced with the host-facing ports above.

## Common commands

```bash
docker compose logs -f app nginx vite queue
docker compose exec app php artisan migrate
docker compose restart queue
docker compose down
```

`docker compose down` keeps the Docker database. To intentionally remove its
data too, use `docker compose down --volumes`.

WAMP can still be used normally after stopping Docker. Its dependencies stay
in the host `vendor` and `node_modules` directories; Docker uses separate named
volumes for both. The Docker stack also runs the default Laravel queue worker.
Both the default queue and communication queue use the Docker Redis service.
