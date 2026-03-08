---
description: How to scaffold a new Admin Module (Backend & Frontend)
---
# How to Create a New Admin Module

When asked to "create a new admin module" or scaffold a new resource for the untitled-cms admin panel, follow these strict architectural and design standards.

## 1. Backend Standards (Laravel)

### Routing
- Admin routes go in `routes/web.php` inside the `auth` middleware group (see the existing `Route::middleware('auth')->group()` block).
- Use `Route::resource('models', ModelController::class);` for standard CRUD operations.

### Controllers
- Create standard resource controllers (`Index`, `Create`, `Store`, `Edit`, `Update`, `Destroy`).
- **Authorization**: EVERY method must verify authorization using `$this->authorize('action', Model::class)` or `$this->authorize('action', $model)`.
- **Validation**: Perform inline validation using `$request->validate([...])` directly in the controller (do not create separate FormRequest files unless exceptionally complex).
- **Sanitization**: If the model accepts rich text content, sanitize it with `clean($validated['content'])` using the Purifier package.
- **Activity Logging**: Log creation, updates, and deletions using `ActivityLogger::log()` (see exact signature below).
- **Response**: Return Inertia responses (`return Inertia::render('Module/Index')`) and redirect back to the index on success using `return redirect()->route('models.index')->with('success', 'Message');`.
- **Soft Delete**: Always use `SoftDeletes` on models. Never use hard delete in controllers — use `$model->delete()` (soft) only. If a force-delete is needed, require a separate explicit confirmation.

#### ActivityLogger Signature
```php
// Full signature:
ActivityLogger::log(string $action, string $description, $subject = null, ?array $beforeState = null, bool $isAiAction = false);

// Creating a record:
ActivityLogger::log('create', "Created model: {$model->title}", $model);

// Updating — capture before-state first:
$beforeState = $model->only(['title', 'status', 'content']);
$model->update($data);
ActivityLogger::log('update', "Updated model: {$model->title}", $model, $beforeState);

// Deleting:
ActivityLogger::log('delete', "Deleted model: {$title}", $model);
```

### Models & Migrations
- Models must extend `MongoDB\Laravel\Eloquent\Model`, **not** the standard Eloquent model.
- Always include `use Illuminate\Database\Eloquent\SoftDeletes;` (do **not** use the deprecated MongoDB SoftDeletes trait).
- Set `protected $connection = 'mongodb';` and `protected $collection = 'your_collection';`.
- Ensure Models have `$guarded = [];` or `$fillable` properly defined.
- Add `$table->softDeletes();` in the migration.
- Migrations must include necessary fields, timestamps, and indexes if required.

```php
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\Model;

class YourModel extends Model {
    use SoftDeletes;
    protected $connection = 'mongodb';
    protected $collection = 'your_models';
    protected $fillable = ['title', 'slug', 'status', ...];
}
```

## 2. Frontend Standards (React + Inertia + shadcn/ui)

Modules must be placed in `resources/js/Pages/[ModuleName]/`.

### 2.1 Index Page (`Index.tsx`)
- **Layout Wrap**: Use `<AuthenticatedLayout header="[Module Title]">`.
- **Permissions**: Extract permissions via Inertia props (`const { permissions } = usePage().props.auth;`). Use these to conditionally render "Create", "Edit", and "Delete" buttons.
- **Header**: Use a flex layout to display the page title (`<h1 className="text-2xl font-bold tracking-tight">`) and the Add Button (`<Button><Plus className="mr-2 h-4 w-4" /> Add Model</Button>`).
- **Data Table**: Use the custom `@/Components/Common/DataTable` component.
  - Define columns using `ColumnDef<ModelModel>[]` from `@tanstack/react-table`.
  - Use `<DataTableColumnHeader column={column} title="Field" />` for sortable headers.
  - Create a `DropdownMenu` from shadcn/ui for table actions (Edit, Delete).
  - Define a responsive `mobileCardRenderer` for the DataTable and show records cleanly on mobile screens using shadcn/ui `Card`.

### 2.2 Create/Edit Pages (`Create.tsx`, `Edit.tsx`)
- **Layout Wrap**: Use `<AuthenticatedLayout header="Create/Edit Model">`.
- **Form State**: Use Inertia's `useForm` hook (`const { data, setData, post, put, processing, errors, isDirty } = useForm({...})`).
- **Form Layout**: Use our custom `@/Components/Common/FormLayouts` module. Wrap the primary forms in `<FormSplitLayout sidebar={<SidebarContent/>}> <MainContent/>} </FormSplitLayout>`.
  - **Sidebar Content**: Place metadata (Status, SEO, Publishing dates, Categories) in multiple shadcn/ui `<Card>` elements within the sidebar slot.
  - **Main Content**: Place core content (Title, Rich Text Editor, Core configurations) in `<Card>` elements within the main slot.
- **Components**:
  - Standard Inputs: Use shadcn/ui `<Input>`, `<Label>`, `<Select>`, `<Textarea>`, `<Switch>`.
  - Slugs: Use `@/Components/SlugInput`.
  - Rich Text: Use `@/Components/Editor`.
  - AI-Assisted Fields: Use `<AiInput>` instead of plain `<Input>` for title/name fields. Use `<AiTextarea>` instead of plain `<Textarea>` for description fields.
- **Footer**: Include `<StickyFormFooter isSaving={processing} isDirty={isDirty} onSave={submit} />` at the bottom of the form for the save actions.

## 3. AI Chat Integration (Required for Every New Module)

Every new module must integrate with the AI Chat assistant system so the AI can help admins work within the module.

### 3.1 Register Module Context in `AiContextService`

In `app/Services/AiContextService.php`, add a case to the existing `match ($module)` expression and implement a private context method:

```php
// In the match expression (existing structure):
return match ($module) {
    'pages'        => $this->pagesContext(),
    'banners'      => $this->bannersContext(),
    'vault'        => $this->vaultContext(),
    'users'        => $this->usersContext(),
    'your_module'  => $this->yourModuleContext(),  // ← add this line
    default        => [],
};

// Add the private method:
private function yourModuleContext(): array
{
    return [
        'module' => 'your_module',
        'stats' => Cache::remember('ai_context_your_module_stats', 60, fn() => [
            'total'  => YourModel::count(),
            'active' => YourModel::where('status', 'active')->count(),
        ]),
        'recent' => YourModel::latest()->limit(5)->get(['id', 'title', 'status']),
    ];
}
```

This allows the AI to answer questions like *"how many active records are there?"* accurately.

### 3.2 Register AI Actions in `AiActionService`

In `app/Services/AiActionService.php`, three additions are required:

**a) Add to the `$whitelist` string array:**
```php
protected array $whitelist = [
    // ... existing entries ...
    'create_your_module',
    'update_your_module',
    'update_your_module_status',
];
```

**b) Add cases to the `match` in `resolveProposal()`:**
```php
return match ($action) {
    // ... existing cases ...
    'create_your_module'        => $this->resolveCreateYourModule($raw),
    'update_your_module'        => $this->resolveUpdateYourModule($raw),
    'update_your_module_status' => $this->resolveUpdateYourModuleStatus($raw),
    default => throw new \Exception("Unhandled action: {$action}"),
};
```

**c) Add cases to the `match` in `execute()`:**
```php
return match ($action) {
    // ... existing cases ...
    'create_your_module'        => $this->executeCreateYourModule($proposal),
    'update_your_module'        => $this->executeUpdateYourModule($proposal),
    'update_your_module_status' => $this->executeUpdateYourModuleStatus($proposal),
    default => throw new \Exception("Unhandled action: {$action}"),
};
```

**d) Implement private resolver and executor methods following the Smart Resolution Pattern:**

```php
// ─── YourModule Title Resolver (shared helper) ─────────────────────────────
private function resolveYourModelByTitle(?string $title): YourModel
{
    /** @var YourModel $model */
    $model = $this->resolveModelByTitle(YourModel::class, $title, 'your_module');
    return $model;
}

// ─── Resolvers ──────────────────────────────────────────────────────────────
private function resolveCreateYourModule(array $raw): array
{
    return [
        'action'      => 'create_your_module',
        'description' => 'Create a new your_module',
        'params'      => [
            'title'  => $raw['params']['title'] ?? null,
            'status' => 'draft',
        ],
    ];
}

private function resolveUpdateYourModule(array $raw): array
{
    $model = $this->resolveYourModelByTitle($raw['params']['title'] ?? null);

    return [
        'action'          => 'update_your_module',
        'description'     => "Update your_module \"{$model->title}\"",
        'resolved_id'     => (string) $model->id,
        'resolved_title'  => $model->title,
        'needs_restore'   => $model->trashed(),  // ← enables frontend restore warning
        'params'          => array_filter([
            'title'   => $raw['params']['new_title'] ?? null,
            'content' => $raw['params']['content'] ?? null,
        ]),
    ];
}

private function resolveUpdateYourModuleStatus(array $raw): array
{
    $model  = $this->resolveYourModelByTitle($raw['params']['title'] ?? null);
    $status = $raw['params']['status'] ?? 'draft';

    return [
        'action'         => 'update_your_module_status',
        'description'    => "Set your_module \"{$model->title}\" status to {$status}",
        'resolved_id'    => (string) $model->id,
        'resolved_title' => $model->title,
        'needs_restore'  => $model->trashed(),
        'params'         => ['status' => $status],
    ];
}

// ─── Executors ──────────────────────────────────────────────────────────────
private function executeCreateYourModule(array $proposal): array
{
    $params = $proposal['params'];
    $model  = YourModel::create([
        'title'  => $params['title'],
        'slug'   => Str::slug($params['title']),
        'status' => 'draft',
    ]);

    ActivityLogger::log('ai_created', "AI created your_module: {$model->title}", $model, null, true);

    return ['subject' => 'YourModel', 'id' => (string) $model->id, 'title' => $model->title, 'url' => route('your_modules.edit', $model->id)];
}

private function executeUpdateYourModule(array $proposal): array
{
    $model = $this->findAndRestoreIfTrashed(YourModel::class, $proposal['resolved_id']);

    $beforeState = $model->only(['title', 'content', 'status']);  // ← snapshot before writing
    $params = $proposal['params'];
    if (isset($params['content'])) {
        $params['content'] = clean($params['content']);  // ← always sanitize AI-generated HTML
    }
    $model->fill(array_filter($params))->save();

    ActivityLogger::log('ai_updated', "AI updated your_module: {$model->title}", $model, $beforeState, true);

    return ['subject' => 'YourModel', 'id' => (string) $model->id, 'title' => $model->title, 'url' => route('your_modules.edit', $model->id)];
}

private function executeUpdateYourModuleStatus(array $proposal): array
{
    $model = $this->findAndRestoreIfTrashed(YourModel::class, $proposal['resolved_id']);

    $beforeState = $model->only(['status']);
    $model->update(['status' => $proposal['params']['status']]);

    ActivityLogger::log('ai_updated', "AI set your_module \"{$model->title}\" to {$proposal['params']['status']}", $model, $beforeState, true);

    return ['subject' => 'YourModel', 'id' => (string) $model->id, 'title' => $model->title, 'status' => $model->status, 'url' => route('your_modules.edit', $model->id)];
}
```

The shared helpers `resolveModelByTitle()` and `findAndRestoreIfTrashed()` already exist in the service — do not re-implement them.

### 3.3 Add Quick Prompt Chips (if relevant)

In `resources/js/Components/Ai/AiChatSidebar.tsx`, add module-specific prompts to the existing `QUICK_PROMPTS` flat array:

```tsx
// Existing flat array — append new prompts relevant to the new module:
const QUICK_PROMPTS = [
    'Help me write a blog intro',
    'Suggest SEO tags for this page',
    'How many pages are published?',
    'What can you help with?',
    // ↓ add module-specific prompts:
    'Create a new your_module',
    'List all active your_modules',
];
```

### 3.4 Document AI Capabilities in Module

Add an `## AI Chat` section to the module's documentation (or inline as a comment in the controller) listing:
- Supported AI actions (create/update/status)
- Fields the AI can set
- Fields the AI cannot set (e.g. passwords, API keys)

### 3.5 AI Safety & Reliability
When scaffolding the module, guarantee the following safety nets:
- **Strict Prompting:** If the AI generates content (like Rich Text), ensure the system prompts (`AiService`) strictly instruct it to output *only* raw, publish-ready HTML, prohibiting placeholder texts, un-escaped quotes, or conversational meta-instructions (e.g. "Here is your content:").
- **Error Surfacing:** Ensure the Action Controller / Service throws specific `Exception` messages if an ID/Title resolution fails (e.g., `No your_module found matching '{title}'`), so the frontend chat sidebar can elegantly trap and display the error rather than failing silently.
- **Activity Log Restores:** Ensure any UI that lists the module's history or timeline gracefully handles the `is_ai_action` Activity Log flag, exposing a clear "Undo/Restore" button leveraging the `before_state` snapshot.

---

## 4. Workflow Steps for AI Execution

When tasked to create a module:

1. **Understand Request**: Clarify what fields the module needs (Title, Content, Status, Image, etc.).
2. **Create Migration**: Use Artisan to make a model and migration `php artisan make:model ModelName -m`. Apply fields. Add `$table->softDeletes()`.
3. **Run Migration**: Execute `php artisan migrate`.
4. **Define Policy & Permissions**: Create `app/Policies/ModelPolicy.php`. Laravel auto-discovers policies by naming convention — no manual registration needed. Ensure seeders or initial permissions contain `models.view`, `models.create`, `models.edit`, `models.delete`.
5. **Create Controller**: Write the logic following the backend standards above. Use `SoftDeletes`. Log all actions.
6. **Register Route**: Add the resource route to `routes/web.php` inside the `auth` middleware group.
7. **Scaffold Views**:
   - Create `Index.tsx` with the standardized DataTable layout.
   - Create `Create.tsx` with `FormSplitLayout`, `StickyFormFooter`, and `AiInput`/`AiTextarea` on key fields.
   - Create `Edit.tsx` with similar styling to Create.tsx.
8. **Register AI Context**: Add the module context to `AiContextService` (see Section 3.1).
9. **Register AI Actions**: Add `create_X`, `update_X`, and `update_X_status` to `AiActionService` — whitelist array + both `match` expressions + private methods (see Section 3.2).
10. **Add Quick Prompts**: Append module-specific prompts to the `QUICK_PROMPTS` array in `AiChatSidebar.tsx` (see Section 3.3).
11. **Verify Build**: Run `npm run build` or ensure Vite runs successfully without strict Type errors preventing compilation.
12. **Test**: Use the `task_boundary` walkthrough pattern to confirm the UI works as intended, including asking the AI chat to create a test record.
