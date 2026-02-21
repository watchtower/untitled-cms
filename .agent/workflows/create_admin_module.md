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

### Models & Migrations
- Ensure Models have `$guarded = [];` or `$fillable` properly defined.
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
- **Footer**: Include `<StickyFormFooter isSaving={processing} isDirty={isDirty} onSave={submit} />` at the bottom of the form for the save actions.

## 3. Workflow Steps for AI Execution

When tasked to create a module:

1. **Understand Request**: Clarify what fields the module needs (Title, Content, Status, Image, etc.).
2. **Create Migration**: Use Artisan to make a model and migration `php artisan make:model ModelName -m`. Apply fields.
3. **Run Migration**: Execute `php artisan migrate`.
4. **Define Policy & Permissions**: Create `ModelPolicy`, register it in `AuthServiceProvider` (if required), and ensure seeders or initial permissions tables contain `models.view`, `models.create`, `models.edit`, `models.delete`.
5. **Create Controller**: Write the logic following the backend standards above.
6. **Register Route**: Add the resource route to `routes/web.php`.
7. **Scaffold Views**:
   - Create `Index.tsx` with the standardized DataTable layout.
   - Create `Create.tsx` with `FormSplitLayout` and `StickyFormFooter`.
   - Create `Edit.tsx` with similar styling to Create.tsx.
8. **Verify Build**: Run `npm run build` or ensure Vite runs successfully without strict Type errors preventing compilation.
9. **Test**: Use the `task_boundary` walkthrough pattern to confirm the UI works as intended.
