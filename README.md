![Holocron](https://banners.beyondco.de/Holocron.png?theme=light&packageManager=composer+require&packageName=egough%2Fholocron&pattern=architect&style=style_2&description=Polymorphic+history+%26+activity+timeline+for+Laravel&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/egough/holocron.svg?style=flat-square)](https://packagist.org/packages/egough/holocron)
[![Total Downloads](https://img.shields.io/packagist/dt/egough/holocron.svg?style=flat-square)](https://packagist.org/packages/egough/holocron)
[![License](https://img.shields.io/packagist/l/egough/holocron.svg?style=flat-square)](https://packagist.org/packages/egough/holocron)
[![Tests](https://github.com/realedwardgough/holocron/actions/workflows/tests.yml/badge.svg)](https://github.com/realedwardgough/holocron/actions/workflows/tests.yml)

Polymorphic history recording and activity timeline for Laravel.

Combines audit-style field diffing with human-readable event logging — so you can track both low-level attribute changes and meaningful business events in a single, consistent history record.

---

## Quick Example
```php
// Record a manual event
Holocron::record('status_changed')
    ->on($order)
    ->by(auth()->user())
    ->message('Order marked as paid')
    ->withMeta(['from' => 'pending', 'to' => 'paid'])
    ->save();

// Automatic tracking via model trait
class Order extends Model
{
    use HasHistory;

    protected array $holocronTrack = ['status', 'total'];
}

// Query the timeline
$order->history()->latest('recorded_at')->get();
$order->holocronTimeline();
```

---

## When Should I Use This Package?

Use Holocron when your application needs a persistent, queryable record of what happened, who did it, and what changed.

**Typical use cases:**

- Order or payment lifecycle tracking
- Content moderation and editorial history
- Support ticket and case notes
- User account activity logs
- Audit trails for compliance

---

### When Not to Use It

Holocron is not a debugging or error-tracking tool. For application exceptions and performance monitoring, consider something like Flare or Sentry.

If you only need simple model diffing without human-readable events, a lightweight audit package may be a better fit. Holocron is designed for applications where both matter.

---

## Installation
```bash
composer require egough/holocron
```

Publish the config and migrations:
```bash
php artisan vendor:publish --tag=holocron-config
php artisan vendor:publish --tag=holocron-migrations
php artisan migrate
```

---

## Manual Recording

Use the `Holocron` facade or helper to record events anywhere in your application.
```php
use Egough\Holocron\Facades\Holocron;

Holocron::record('note_added')
    ->on($ticket)
    ->by(auth()->user())
    ->message('Support note added by agent')
    ->category('communication')
    ->save();
```

Record explicit attribute diffs:
```php
Holocron::record('updated')
    ->on($post)
    ->by($user)
    ->withChanges([
        'title' => ['old' => 'Old Title', 'new' => 'New Title'],
    ])
    ->message('Post title updated')
    ->save();
```

Attach arbitrary metadata:
```php
Holocron::record('invoice_resent')
    ->on($invoice)
    ->by($user)
    ->withMeta(['recipient' => 'billing@example.com'])
    ->save();
```

---

## Model Integration

Add the `HasHistory` trait to any Eloquent model to enable automatic and on-demand history recording.
```php
use Egough\Holocron\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasHistory;

    protected array $holocronTrack = ['status', 'total'];
}
```

### Automatic Events

The trait automatically records `created`, `updated`, `deleted`, and `restored` events. For updates, tracked field diffs are stored as:
```json
{
  "status": {
    "old": "draft",
    "new": "active"
  }
}
```

Control which events are recorded per model:
```php
protected array $holocronEvents = ['created', 'deleted'];
```

### Querying History
```php
$order->history;

$order->history()->latest('recorded_at')->get();

$order->latestHistory()->get();

$order->holocronTimeline();
```

### Recording from the Model
```php
$project->recordHistory(
    event: 'archived',
    message: 'Project archived by admin',
    actor: auth()->user(),
);
```

---

## Querying

The `HolocronEntry` model includes helpful query scopes.
```php
use Egough\Holocron\Models\HolocronEntry;

// Filter by subject and event type
HolocronEntry::query()
    ->forSubject($order)
    ->event('status_changed')
    ->latest('recorded_at')
    ->get();

// Filter by actor and category
HolocronEntry::query()
    ->causedBy($user)
    ->category('communication')
    ->get();
```

---

## Configuration

The published config file gives you control over table naming, automatic event recording, excluded attributes, and actor resolution.
```php
return [
    'table_name' => 'holocron_entries',

    'auto_record' => [
        'created'  => true,
        'updated'  => true,
        'deleted'  => true,
        'restored' => true,
    ],

    'exclude' => [
        'created_at',
        'updated_at',
    ],

    'actor_resolver' => static fn () => auth()->user(),
];
```

---

## Testing

Install development dependencies:
```bash
composer install
```

Run the test suite:
```bash
composer test
```

Or run PHPUnit directly:
```bash
vendor/bin/phpunit
```

Tests use an in-memory SQLite setup — no external database required.

---

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

---

## License

MIT
