---
description: How to scaffold a new Admin Module (Backend & Frontend)
---
# How to Create a New Admin Module

When asked to "create a new admin module" or scaffold a new resource for the untitled-cms admin panel, follow these strict architectural and design standards. 

## 1. Backend Standards (Laravel)

### Routing
- Admin routes go in `routes/web.php` inside the `auth` middleware group.
- Use `Route::resource('models', ModelController::class);` for standard CRUD operations.

### Controllers
- Create standard resource controllers (`Index`, `Create`, `Store`, `Edit`, `Update`, `Destroy`).
- **Authorization**: EVERY method must verify authorization using `$this->authorize('action', Model::class)` or `$this->authorize('action', $model)`.
- **Validation**: Perform inline validation using `$request->validate([...])` directly in the controller (do not create separate FormRequest files unless exceptionally complex).
- **Sanitization**: If the model accepts rich text content, sanitize it with `clean($validated['content'])` using the Purifier package.
- **Activity Logging**: Log creation, updates, and deletions using `\App\Services\ActivityLogger::log('action', "Description", $model);`.
- **Response**: Return Inertia responses (`return Inertia::render('Module/Index')`) and redirect back to the index on success using `return redirect()->route('models.index')->with('success', 'Message');`.
- **Soft Delete**: Always use `SoftDeletes` on models. Never use hard delete in controllers — use `$model->delete()` (soft) only. If a force-delete is needed, require a separate explicit confirmation.

### Models & Migrations
- Ensure Models have `$guarded = [];` or `$fillable` properly defined.
- Always include `use Illuminate\Database\Eloquent\SoftDeletes;` (do **not** use the deprecated MongoDB SoftDeletes trait). 
- Add `$table->softDeletes();` in the migration.
- Migrations must include necessary fields, timestamps, and indexes if required.

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

In `app/Services/AiContextService.php`, add a case for the new module:

```php
case 'your_module':
    return [
        'module' => 'your_module',
        'stats' => [
            'total' => YourModel::count(),
            'active' => YourModel::where('status', 'active')->count(),
        ],
        'recent' => YourModel::latest()->limit(5)->get(['id', 'title', 'status']),
    ];
```

This allows the AI to answer questions like *"how many active records are there?"* accurately.

### 3.2 Register AI Actions in `AiActionService`

In `app/Services/AiActionService.php`, add the module's supported actions to the whitelist:

```php
'create_your_module' => fn($params) => $this->createYourModule($params),
'update_your_module' => fn($params) => $this->updateYourModule($params),
'update_your_module_status' => fn($params) => $this->updateYourModuleStatus($params),
```

Also implement the corresponding private methods that follow the **Smart Resolution Pattern**:
1. **Resolve (`resolveYourModuleByTitle`)**: 
   - Check active exact/like match first.
   - Fall back to checking the trash (`onlyTrashed()`).
   - Fall back to fuzzy searching (split words >3 chars, chain `orWhere`).
2. **Flag (`resolveUpdateYourModule`)**: Include `'needs_restore' => $model->trashed()` in the response array so the frontend shows the UI warning.
3. **Execute (`executeUpdateYourModule`)**: 
   - Fetch using `withTrashed()`.
   - Call `$model->restore()` if `trashed()`.
   - Save a `before_state` snapshot to `ActivityLogger` before writing.

### 3.3 Add Quick Prompt Chips (if relevant)

In `resources/js/Components/Ai/AiChatSidebar.tsx`, add module-specific prompts to `QUICK_PROMPTS` when users are on that module's pages. Example:

```tsx
// Detect module from usePage().url
const modulePrompts: Record<string, string[]> = {
    '/pages': ['Create a new page', 'List all draft pages', 'Help me write SEO'],
    '/banners': ['Create a new banner', 'List active banners'],
};
```

### 3.4 Document AI Capabilities in Module

Add an `## AI Chat` section to the module's documentation (or inline as a comment in the controller) listing:
- Supported AI actions (create/update/status)
- Fields the AI can set
- Fields the AI cannot set (e.g. passwords, API keys)

### 3.5 AI Safety & Reliability
When scaffolding the module, guarantee the following safety nets:
- **Strict Prompting:** If the AI generates content (like Rich Text), ensure the system prompts (`AiService`) strictly instruct it to output *only* raw, publish-ready HTML, prohibiting placeholder texts, un-escaped quotes, or conversational meta-instructions (e.g. "Here is your content:").
- **Error Surfacing:** Ensure the Action Controller / Service throws specific `Exception` messages if an ID/Title resolution fails (e.g., `No banner found matching '{title}'`), so the frontend chat sidebar can elegantly trap and display the error rather than failing silently.
- **Activity Log Restores:** Ensure any UI that lists the module's history or timeline gracefully handles the `is_ai_action` Activity Log flag, exposing a clear "Undo/Restore" button leveraging the `before_state` snapshot.

---

## 4. Workflow Steps for AI Execution

When tasked to create a module:

1. **Understand Request**: Clarify what fields the module needs (Title, Content, Status, Image, etc.).
2. **Create Migration**: Use Artisan to make a model and migration `php artisan make:model ModelName -m`. Apply fields. Add `$table->softDeletes()`.
3. **Run Migration**: Execute `php artisan migrate`.
4. **Define Policy & Permissions**: Create `ModelPolicy`, register it in `AuthServiceProvider` (if required), and ensure seeders or initial permissions tables contain `models.view`, `models.create`, `models.edit`, `models.delete`.
5. **Create Controller**: Write the logic following the backend standards above. Use `SoftDeletes`. Log all actions.
6. **Register Route**: Add the resource route to `routes/web.php`.
7. **Scaffold Views**:
   - Create `Index.tsx` with the standardized DataTable layout.
   - Create `Create.tsx` with `FormSplitLayout`, `StickyFormFooter`, and `AiInput`/`AiTextarea` on key fields.
   - Create `Edit.tsx` with similar styling to Create.tsx.
8. **Register AI Context**: Add the module context to `AiContextService` (see Section 3.1).
9. **Register AI Actions**: Add `create_X`, `update_X`, and `update_X_status` to `AiActionService` (see Section 3.2).
10. **Add Quick Prompts**: Update `AiChatSidebar.tsx` with module-specific prompt chips (see Section 3.3).
11. **Verify Build**: Run `npm run build` or ensure Vite runs successfully without strict Type errors preventing compilation.
12. **Test**: Use the `task_boundary` walkthrough pattern to confirm the UI works as intended, including asking the AI chat to create a test record.
