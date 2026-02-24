## Stave - Worship Service Planning System

This is a Laravel 12 application for managing worship services, liturgy elements, songs, and readings.

### Core Domain Models

- **Service**: A worship service on a specific date, based on a template
- **Template**: Reusable service structure with liturgy elements
- **LiturgyElement**: Individual service components that can belong to Services or Templates via polymorphic relationship
  - Types: section, song, reading, sermon, prayer, supper, baptism, other (defined in `LiturgyElementType` enum)
  - Has optional content relationship to Song or Reading models
  - Can have assignee (User)
- **Song**: Worship songs with lyrics, CCLI info, sheets (PDFs), and recordings (audio)
- **Reading**: Scripture or other readings with type classification
- **Person/User**: People management with User accounts linked to Person records

### Key Development Commands

```bash
# Testing
npm run pest                                # Run all tests
php artisan test tests/Feature/ServiceTest  # Test specific file
php artisan test --filter=testName          # Filter by test name
vendor/bin/pest --parallel                  # Run tests in parallel

# Code Quality
npm run pint:fix                            # Format changed files
npm run larastan                            # Static analysis
npm run rector:dry                          # Preview refactoring
npm run rector                              # Apply refactoring

# Building
npm run build                              # Production build
```

### Application Architecture

- **Livewire Components**: Located in `app/Livewire/` with Forms subdirectory
- **Views**: Blade templates in `resources/views/livewire/`
- **Element Components**: Service/Template element views in `livewire/elements/`
- **Testing**: Feature tests with Pest v4 including browser testing capability
- **File Storage**: Songs can have Sheet and Recording attachments via local filesystem

### Application Conventions

- Always use `use` statements to import classes at the top of PHP files rather than using fully qualified class names inline (e.g., use `Person::create()` instead of `\App\Models\Person::create()`).

### Database Schema

- SQLite database at `database/database.sqlite`
- Polymorphic relationships for liturgy elements (can belong to Service or Template)
- Content polymorphism for Songs and Readings
- Comments system via Spatie Laravel Comments package

### URL & Serving

Application served via Laravel Herd at: `https://stave.test`
