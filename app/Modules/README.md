# Module Boundary

Each directory under `app/Modules` owns one business capability. Keep business rules inside the module that owns them and expose only application actions, DTOs, resources, and routes across module boundaries.

Standard module layout:

```text
ModuleName/
├── Application/       # Use cases, commands, queries, DTOs
├── Domain/            # Entities, value objects, policies, contracts, enums
├── Infrastructure/   # Persistence, external services, queue adapters
├── Presentation/     # Controllers, Form Requests, Resources, Inertia concerns
├── routes.php
└── ModuleServiceProvider.php
```

The current modules are `Identity`, `AcademicCatalog`, `Documents`, `Reviews`, `Bounties`, `Moderation`, and `Administration`.
