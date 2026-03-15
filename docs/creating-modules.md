# Creating Custom Content Modules

A "module" in Untitled CMS is a self-contained feature: a MongoDB model, a controller, a Laravel policy, and a React page. This guide walks through building a complete **Events** module as an example.

## Overview

```
app/Models/Event.php                    ← MongoDB model
app/Policies/EventPolicy.php            ← Authorization
app/Http/Controllers/EventController.php ← CRUD controller
resources/js/Pages/Events/Index.tsx     ← React admin page
resources/js/Pages/Events/Create.tsx
resources/js/Pages/Events/Edit.tsx
```

## Step 1 — Add Permissions

Open `app/Models/Role.php` and add your permissions to `availablePermissions()`:

```php
// Events
'events.view',
'events.create',
'events.edit',
'events.delete',
```

This is the **single source of truth** for permissions. Every permission must be listed here before it can be assigned to a role.

Then update `database/seeders/RoleSeeder.php` to grant new permissions to the appropriate roles:

```php
// In the admin role permissions array:
'events.view', 'events.create', 'events.edit', 'events.delete',
```

Run the seeder:

```bash
php artisan db:seed --class=RoleSeeder
```

## Step 2 — Create the Model

```php
<?php
// app/Models/Event.php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'events';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'starts_at',
        'ends_at',
        'location',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];
}
```

## Step 3 — Create the Policy

```php
<?php
// app/Policies/EventPolicy.php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('events.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('events.create');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->hasPermission('events.edit');
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasPermission('events.delete');
    }
}
```

Register the policy in `app/Providers/AppServiceProvider.php`:

```php
use App\Models\Event;
use App\Policies\EventPolicy;

Gate::policy(Event::class, EventPolicy::class);
```

## Step 4 — Create the Controller

```php
<?php
// app/Http/Controllers/EventController.php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Event::class);

        return Inertia::render('Events/Index', [
            'events' => Event::orderBy('starts_at', 'desc')->paginate(25),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Event::class);
        return Inertia::render('Events/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Event::class);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:events,slug',
            'description' => 'nullable|string',
            'starts_at'   => 'required|date',
            'ends_at'     => 'nullable|date|after_or_equal:starts_at',
            'location'    => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        // Auto-generate unique slug
        $base = Str::slug($validated['slug'] ?? $validated['title']);
        $slug = $base;
        $i = 2;
        while (Event::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        $validated['slug'] = $slug;
        $validated['created_by'] = auth()->id();

        Event::create($validated);

        \App\Services\ActivityLogger::log('create', "Created event: {$validated['title']}");

        return redirect()->route('events.index')->with('success', 'Event created.');
    }

    public function edit(string $id)
    {
        $event = Event::findOrFail($id);
        $this->authorize('update', $event);

        return Inertia::render('Events/Edit', ['event' => $event]);
    }

    public function update(Request $request, string $id)
    {
        $event = Event::findOrFail($id);
        $this->authorize('update', $event);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at'   => 'required|date',
            'ends_at'     => 'nullable|date|after_or_equal:starts_at',
            'location'    => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        $event->update($validated);

        \App\Services\ActivityLogger::log('update', "Updated event: {$event->title}", $event);

        return redirect()->route('events.index')->with('success', 'Event updated.');
    }

    public function destroy(string $id)
    {
        $event = Event::findOrFail($id);
        $this->authorize('delete', $event);

        $title = $event->title;
        $event->delete();

        \App\Services\ActivityLogger::log('delete', "Deleted event: {$title}", $event);

        return redirect()->route('events.index')->with('success', 'Event deleted.');
    }
}
```

## Step 5 — Register Routes

Add to `routes/web.php` inside the `auth` + `verified` middleware group:

```php
Route::resource('events', \App\Http\Controllers\EventController::class)
    ->except(['show']);
```

## Step 6 — Create the Frontend Page

Create `resources/js/Pages/Events/Index.tsx` using the standard pattern:

```tsx
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { DataTable } from '@/Components/Common/DataTable';
import { columns } from './columns'; // define your TanStack Table columns

interface Event {
    id: string;
    title: string;
    starts_at: string;
    is_active: boolean;
}

interface Props {
    events: { data: Event[]; /* pagination */ };
}

export default function Index({ events }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title="Events" />
            <DataTable columns={columns} data={events.data} />
        </AuthenticatedLayout>
    );
}
```

## Step 7 — Add to Navigation

Open `resources/js/Components/nav-main.tsx` (or the sidebar config) and add your module to the navigation items array, gated on the appropriate permission prop passed from `HandleInertiaRequests`.

## Key Conventions

| Rule | Detail |
|------|--------|
| Permissions | Always add to `Role::availablePermissions()` first |
| Authorization | Use `$this->authorize()` in every controller action |
| Slugs | Generate with collision loop (see Step 4) |
| Logging | Call `ActivityLogger::log()` for create/update/delete |
| Inertia props | No client-side API calls — pass all data as props |
| MongoDB model | Must set `protected $connection = 'mongodb'` |
| Soft deletes | Use `MongoDB\Laravel\Eloquent\SoftDeletes` (not the standard Eloquent one) |

## Optional: Service Layer

For complex business logic, extract it into `app/Services/EventService.php` following the pattern used by `VaultService`. Keep controllers thin — they should only validate, authorize, call the service, and return a response.
