# Holocron

Holocron is a Laravel package for recording and querying historical events across your application.

It combines two things in one entry:

- audit-style field changes
- timeline-style human-readable activity

That means you can store low-level diffs like `old` and `new`, while also storing events like `Order marked as paid`, `Invoice email resent`, or `Manual note added by support staff`.

## Version 1 scope

This scaffold is focused on the MVP:

- polymorphic history entries
- manual event recording
- automatic model event recording for `created`, `updated`, `deleted`, and `restored`
- actor tracking
- optional message, category, metadata, and field-level changes
- per-model history queries
- timeline-ready output helpers

## Installation

Install the package with Composer:

```bash
composer require egough/holocron
```

Publish the config and migration:

```bash
php artisan vendor:publish --tag=holocron-config
php artisan vendor:publish --tag=holocron-migrations
php artisan migrate
```

## Quick start

### Manual recording

```php
use Egough\Holocron\Facades\Holocron;

Holocron::record('status_changed')
    ->on($order)
    ->by(auth()->user())
    ->withMeta([
        'from' => 'pending',
        'to' => 'paid',
    ])
    ->message('Order status changed from pending to paid')
    ->save();
```

You can also record explicit attribute diffs:

```php
Holocron::record('updated')
    ->on($post)
    ->by($user)
    ->withChanges([
        'title' => [
            'old' => 'Old Title',
            'new' => 'New Title',
        ],
    ])
    ->message('Post title updated')
    ->save();
```

### Model integration

```php
use Egough\Holocron\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasHistory;

    protected array $holocronTrack = [
        'status',
        'total',
    ];
}
```

Once attached, you can query a model timeline directly:

```php
$order->history;
$order->history()->latest('recorded_at')->get();
$order->latestHistory()->get();
$order->holocronTimeline();
```

And you can record history from the model itself:

```php
$project->recordHistory(
    event: 'archived',
    message: 'Project archived by admin',
    actor: auth()->user(),
);
```

### Automatic model events

The `HasHistory` trait automatically records:

- `created`
- `updated`
- `deleted`
- `restored`

For updates, Holocron stores tracked diffs as:

```json
{
  "status": {
    "old": "draft",
    "new": "active"
  }
}
```

## Configuration

The published config looks like this:

```php
return [
    'table_name' => 'holocron_entries',

    'auto_record' => [
        'created' => true,
        'updated' => true,
        'deleted' => true,
        'restored' => true,
    ],

    'exclude' => [
        'created_at',
        'updated_at',
    ],

    'actor_resolver' => static fn () => auth()->user(),
];
```

Per model, you can narrow tracked fields with `protected array $holocronTrack = [...];` and override auto-recorded events with `protected array $holocronEvents = [...];`.

## Querying

The `HolocronEntry` model includes helpful scopes:

```php
use Egough\Holocron\Models\HolocronEntry;

HolocronEntry::query()
    ->forSubject($order)
    ->event('status_changed')
    ->latest('recorded_at')
    ->get();

HolocronEntry::query()
    ->causedBy($user)
    ->category('communication')
    ->get();
```
