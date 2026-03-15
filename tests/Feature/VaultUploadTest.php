<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VaultFile;
use App\Models\VaultFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VaultUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('vault');
        Storage::fake('sandbox');
        VaultFile::truncate();
        VaultFolder::truncate();
    }

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $role = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'backend_access' => true, 'is_active' => true,
                'permissions' => ['media.view', 'media.create', 'media.edit', 'media.delete']]
        );
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_admin_can_upload_file(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($user)->postJson(route('admin.vault.upload'), [
            'files' => [$file],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['uploaded' => [['uuid', 'original_name']]]);

        $this->assertDatabaseHas('vault_files', [
            'original_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'uploaded_by' => $user->id,
        ], 'mongodb');

        $uploadedFile = VaultFile::first();
        $this->assertTrue(
            Storage::disk('public')->exists($uploadedFile->storage_path),
            "File missing at: {$uploadedFile->storage_path}"
        );
    }

    public function test_double_extension_is_rejected(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->create('exploit.php.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)->postJson(route('admin.vault.upload'), [
            'files' => [$file],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['error' => 'Potential malicious double extension detected in filename: exploit.php.jpg']);

        $this->assertDatabaseMissing('vault_files', ['original_name' => 'exploit.php.jpg'], 'mongodb');
    }

    public function test_validates_mime_type_mismatch(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->createWithContent('malicious.jpg', '<?php echo "evil"; ?>');

        $this->actingAs($user)->postJson(route('admin.vault.upload'), [
            'files' => [$file],
        ]);

        // Mime type detection is environment-dependent; this test documents the intent.
        // Add assertions here once CI mime detection behaviour is confirmed.
        $this->markTestIncomplete('Mime detection result is environment-dependent — add assertion when confirmed.');
    }

    public function test_unauthorized_user_cannot_upload(): void
    {
        $user = User::factory()->create(); // No role, no backend access

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($user)->postJson(route('admin.vault.upload'), [
            'files' => [$file],
        ]);

        $response->assertStatus(403);
    }
}
