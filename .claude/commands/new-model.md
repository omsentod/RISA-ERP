---
description: Scaffold a new Eloquent model with migration, factory, and Filament resource — placed in the correct DDD domain folder
argument-hint: <Domain> <ModelName>  (e.g. Product Category  → app/Domain/Product/Models/Category.php)
---

# /new-model $ARGUMENTS

Scaffold a complete model setup for RISA ERP: model + migration + factory + Filament resource.

## Steps to perform

1. **Parse arguments:**
   - Expected: `<Domain> <ModelName>` (e.g., `Product Category`)
   - `<Domain>` must exist under `app/Domain/` — if not, suggest running `/new-module <Domain>` first
   - `<ModelName>` singular PascalCase

2. **Confirm the plan with the user** before generating (unless the user already provided full detail):
   - Table name (plural snake_case, derived from ModelName)
   - Columns you propose (id, name, timestamps, softDeletes at minimum)
   - Whether it needs audit columns (`created_by`, `updated_by`, `deleted_by`)
   - Whether it's a master data table (soft delete) or transactional (audit)
   - Any relationships (belongsTo, hasMany, belongsToMany)

3. **Generate:**

   **a. Migration** — `database/migrations/YYYY_MM_DD_HHMMSS_create_<table>_table.php`
   ```php
   <?php

   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up(): void
       {
           Schema::create('<table>', function (Blueprint $table) {
               $table->id();
               // TODO: add columns here based on plan
               $table->timestamps();
               $table->softDeletes();
           });
       }

       public function down(): void
       {
           Schema::dropIfExists('<table>');
       }
   };
   ```

   **b. Model** — `app/Domain/<Domain>/Models/<ModelName>.php`
   ```php
   <?php

   namespace App\Domain\<Domain>\Models;

   use Illuminate\Database\Eloquent\Factories\HasFactory;
   use Illuminate\Database\Eloquent\Model;
   use Illuminate\Database\Eloquent\SoftDeletes;

   class <ModelName> extends Model
   {
       use HasFactory, SoftDeletes;

       protected $fillable = [
           // TODO: add fillable columns
       ];

       protected function casts(): array
       {
           return [
               // TODO: add casts (dates, booleans, arrays)
           ];
       }
   }
   ```

   **c. Factory** — `database/factories/<ModelName>Factory.php`
   ```php
   <?php

   namespace Database\Factories;

   use App\Domain\<Domain>\Models\<ModelName>;
   use Illuminate\Database\Eloquent\Factories\Factory;

   class <ModelName>Factory extends Factory
   {
       protected $model = <ModelName>::class;

       public function definition(): array
       {
           return [
               // TODO: define default attributes using $this->faker
           ];
       }
   }
   ```

   **d. Filament resource** — run:
   ```bash
   php artisan make:filament-resource <ModelName> --model-namespace="App\Domain\<Domain>\Models"
   ```
   (Note: if the flag doesn't work in your Filament version, create resource then manually edit the `$model` property.)

4. **After generating:**
   - Do NOT run the migration automatically — the user reviews first
   - Suggest running the `db-guardian` agent to validate the migration
   - Suggest running `php artisan migrate` when ready

## Rules

- Table name always plural snake_case (e.g. `Category` → `categories`, `ProductRegistration` → `product_registrations`)
- Model always singular PascalCase
- Factory always in `database/factories/`, not in domain folder (Laravel convention)
- Migration filename includes timestamp — use `date +%Y_%m_%d_%H%M%S` for consistency
- If ModelName ends in "y", table pluralization: `Category` → `categories` (not `categorys`)
- Always include `SoftDeletes` for master data unless user explicitly says no
