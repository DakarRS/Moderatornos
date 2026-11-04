# Moderatornos

Filament admin and read-only JSON API (`backend/`), with a TanStack Start frontend (`frontend/`).

```sh
docker compose up --build
```

| What | URL |
| --- | --- |
| Frontend | http://localhost:3100 |
| Admin | http://localhost:8100/admin (`admin@example.com` / `password`, local only) |
| API | http://localhost:8100/api/people?search=&category=&page=&per_page= and http://localhost:8100/api/categories?search= |
| Postgres | `localhost:5435` (`aternos` / `secret`) |

Categories are managed in the admin, and the frontend tabs follow their order.

Run backend tests with `docker compose exec backend php artisan test`.

## Dependencies

`frontend/node_modules` is one folder shared by your machine and Docker, and the frontend container resets it to `package-lock.json` every time it starts. Your editor reads the same packages the app runs on.

It holds Linux builds of native tools, so run the app through Docker rather than directly on your machine.

Add or update packages from your machine, so they pass through your security tooling, then restart the container to restore the Linux builds:

```sh
cd frontend && nvm use && npm install <package>
docker compose restart frontend
```

`shadcn add` imports `cn` from a package of the same name and installs it. This project uses `@/lib/utils`, so change the import in the new components and run `npm uninstall cn`.
