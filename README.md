# Moderatornos

A directory of the Aternos moderators and a knowledge base of fixes the Aternos help center doesn't cover. Filament admin and JSON API (`backend/`), with a TanStack Start frontend (`frontend/`).

```sh
docker compose up --build
```

| What | URL |
| --- | --- |
| Frontend | http://localhost:3100 (People at `/`, Solutions at `/solutions`) |
| Admin | http://localhost:8100/admin (`admin` / `password`, local only). The backend root redirects here. |
| API | http://localhost:8100/api, see below |
| Postgres | `localhost:5435` (`aternos` / `secret`) |

The ports are offset from the usual 3000/8000/5432 so this can run next to other projects.

### API

| Endpoint | Notes |
| --- | --- |
| `GET /api/people?search=&category=&page=&per_page=` | Search matches names and country names. |
| `GET /api/people/{id}` | |
| `GET /api/categories?search=` | |
| `GET /api/solutions?search=&tag=&edition=&page=&per_page=` | Published only. Full-text search that tolerates typos. `edition` is `java` or `bedrock`, and entries for both editions show under either. |
| `GET /api/solutions/{slug}` | Includes the markdown fields rendered to HTML, with raw HTML escaped. |
| `POST /api/solutions/match` with `{"url": "https://mclo.gs/…"}` | Fetches the log from mclo.gs and returns published solutions whose signatures it contains. |

Everything is rate limited. Apart from log matching, the API is read-only.

### Admin

- Admins manage everything. Editors manage People and Solutions only.
- Categories and Tags are admin-only. Category order sets the order of the frontend tabs.
- Solutions start as drafts and stay hidden until Published is switched on.
- A solution's slug is editable and is filled from the title when you create it. Renaming a solution later doesn't change the slug, so links shared in Discord keep working unless you edit the slug yourself.
- Signatures are plain text matched without case against mclo.gs logs, not regular expressions.

### Development notes

- Run backend tests with `docker compose exec backend php artisan test`. Tests fake mclo.gs and refuse any other outgoing request.
- The frontend container doesn't notice new route files. Run `docker compose restart frontend` after adding one, so `routeTree.gen.ts` is regenerated.

## Production

Pushing to `main` runs the tests, builds images and deploys to the server with Docker Compose behind Caddy. See `deploy/`.

- Roll back by re-running the deploy workflow for an older commit in GitHub Actions.
- Create an account from the server: `ssh -t root@<server> docker exec -it moderatornos-backend-1 php artisan user:create --role=admin`
- Invite people from the admin's Invites page. Registration is closed without a valid link.
- Secrets live in `/opt/moderatornos/.env` and `backup.env` on the server, never in this repository.
- Back up now: `ssh root@<server> moderatornos-backup`
## Dependencies

`frontend/node_modules` is one folder shared by your machine and Docker, and the frontend container resets it to `package-lock.json` every time it starts. Your editor reads the same packages the app runs on.

It holds Linux builds of native tools, so run the app through Docker rather than directly on your machine.

Add or update packages from your machine, so they pass through your security tooling, then restart the container to restore the Linux builds:

```sh
cd frontend && nvm use && npm install <package>
docker compose restart frontend
```

`shadcn add` imports `cn` from a package of the same name and installs it. This project uses `@/lib/utils`, so change the import in the new components and run `npm uninstall cn`.
