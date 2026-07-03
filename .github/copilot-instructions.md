# Copilot Instructions

Follow `AGENTS.md` and `CLAUDE.md` for repository rules.

- This is a Laravel + Inertia/React + Vite app.
- Frontend source is `resources/ts`.
- Keep `resources/ts/Pages` as thin Inertia entries; put reusable client code in `resources/ts/features/client` or `resources/ts/shared`.
- Prefer existing Laravel Actions, Services, Form Requests, Policies, Enums, DTOs, Resources, and ViewModels.
- Do not change public routes or business rules during refactors unless explicitly requested.
- Do not expose raw exceptions, secrets, SQL errors, filesystem paths, tokens, or private user data.
- Commit messages and PR descriptions must be in English.
- Merge-grade validation is `./scripts/ci-check-docker.sh`.
