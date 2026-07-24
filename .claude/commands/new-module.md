---
description: Scaffold new domain module folder structure under app/Domain/
argument-hint: <ModuleName in PascalCase, e.g. Inventory or Sales>
---

# /new-module $ARGUMENTS

Create a new domain module at `app/Domain/$ARGUMENTS/` following RISA ERP DDD-lite convention.

## Steps to perform

1. **Validate the argument:**
   - Must be PascalCase (e.g. `Inventory`, `Sales`, `Consignment`)
   - Must not already exist under `app/Domain/`
   - If invalid or exists, stop and report the problem — do NOT overwrite

2. **Create folder structure:**
   ```
   app/Domain/$ARGUMENTS/
   ├── Models/.gitkeep
   ├── Actions/.gitkeep
   └── Data/.gitkeep
   ```

3. **Create a README.md** at `app/Domain/$ARGUMENTS/README.md` with this template:
   ```markdown
   # $ARGUMENTS Domain

   > Purpose: (one-sentence description of what this domain owns)

   ## Aggregates

   - (List the main aggregate roots — usually Models — here)

   ## External dependencies

   - (List other domains this depends on, and how)

   ## Publishes to

   - (List downstream systems this pushes data to, if any — e.g. company_profile DB)
   ```

4. **Report back** with:
   - The paths created
   - A reminder that models go in `Models/`, business logic in `Actions/`, DTOs in `Data/`
   - Suggestion to run `/new-model <ModelName>` next if a model is needed

## Rules

- Use PSR-4: model namespace will be `App\Domain\$ARGUMENTS\Models\<Name>`
- Do NOT modify `composer.json` autoload (existing `App\ => app/` mapping already covers this)
- Do NOT create empty PHP files — only folders + .gitkeep + README
