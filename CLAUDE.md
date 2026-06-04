# CLAUDE.md

Guidance for working in this repository (Laravel 12 + Inertia/Vue HR/vacancies app).

## Architecture rule (SOLID, but KISS first)

Add structure only where it earns its keep. Default to the simplest thing that works.

- **Controller** → thin: authorize, hand the request to a FormRequest, delegate to a
  Service (when one exists), return the response. No business logic in controllers.
- **FormRequest** → all validation lives here (one per Store/Update). Branch-scope
  authorization goes in `authorize()` (return `false` → 403), not in `prepareForValidation()`.
- **Service** (`app/Services`) → create one only when an action has a transaction, a
  cascade, or ≥3 steps of logic (e.g. Employee/Vacancy/Department). Trivial CRUD
  (Branch/Position/User) stays inline in the controller.
- **Abstraction / interface** → introduce only when there is a second implementation or
  an external boundary. Otherwise inject the concrete class (the container still allows
  mocking in tests). Do **not** add an interface per service by default.
- **No repositories over Eloquent.** Eloquent is the data layer. Use query scopes on the
  model for reusable query fragments (see `Employee::scopeActive/viewableBy/search`).
- **Enums over magic strings** for fixed value sets (`App\Enums\*`).
- **Authorization** goes through Policies + `Gate`, not ad-hoc `abort(403)`.

## Org structure import

`php artisan org:import --file=storage/app/tj_structure.json --fresh` imports the
Tojiktelecom structure. One Branch per businessUnit; departments form one connected
tree per branch; ordering is preserved via `departments.sort_order`.

## Quality gates (run before pushing)

```bash
vendor/bin/pint            # format (use --test in CI to check only)
vendor/bin/phpstan analyse --memory-limit=1G   # level 5, baseline in phpstan-baseline.neon
php artisan test
```

CI (`.github/workflows/ci.yml`) runs all three on push/PR against a Postgres service.
When PHPStan flags pre-existing issues you are not touching, leave the baseline; do not
lower the level. Frontend changes require `npm run build` (assets are git-ignored).
