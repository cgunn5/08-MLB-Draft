<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ApplicationBundleExporter;
use App\Support\ApplicationBundleImporter;
use App\Support\ApplicationBundlePaths;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApplicationBundleTest extends TestCase
{
    private string $fileDatabasePath;

    private string $uploadsDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileDatabasePath = storage_path('app/testing-application-bundle-'.uniqid('', true).'.sqlite');
        $this->uploadsDirectory = storage_path('app/testing-uploads-'.uniqid('', true));

        config([
            'database.connections.sqlite.database' => $this->fileDatabasePath,
            'application_bundle.uploads_directory' => $this->uploadsDirectory,
            'application_bundle.export_directory' => storage_path('app/testing-exports-'.uniqid('', true)),
        ]);

        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');

        if (isset($this->fileDatabasePath)) {
            File::delete($this->fileDatabasePath);
        }

        if (isset($this->uploadsDirectory)) {
            File::deleteDirectory($this->uploadsDirectory);
        }

        File::deleteDirectory((string) config('application_bundle.export_directory'));

        parent::tearDown();
    }

    public function test_export_and_import_round_trip_preserves_users_and_upload_files(): void
    {
        $sourceUser = User::factory()->admin()->create([
            'email' => 'source@example.com',
            'name' => 'Source Admin',
        ]);

        File::ensureDirectoryExists($this->uploadsDirectory);
        File::put($this->uploadsDirectory.'/sample-stats.csv', "name,avg\nPlayer One,.300\n");

        $export = (new ApplicationBundleExporter)->export();
        $this->assertFileExists($export['path']);
        $this->assertSame(1, $export['manifest']['upload_count']);

        User::query()->delete();
        File::delete($this->uploadsDirectory.'/sample-stats.csv');
        $this->assertDatabaseCount('users', 0);

        $manifest = (new ApplicationBundleImporter)->import($export['path']);
        $this->assertSame(1, $manifest['upload_count']);

        DB::purge('sqlite');

        $this->assertDatabaseHas('users', [
            'email' => $sourceUser->email,
            'name' => 'Source Admin',
        ]);
        $this->assertFileExists($this->uploadsDirectory.'/sample-stats.csv');
        $this->assertStringContainsString('Player One', (string) file_get_contents($this->uploadsDirectory.'/sample-stats.csv'));
    }

    public function test_admin_can_download_bundle(): void
    {
        File::ensureDirectoryExists($this->uploadsDirectory);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.application-bundle.download'))
            ->assertOk()
            ->assertDownload();
    }

    public function test_admin_can_import_bundle_from_upload_form(): void
    {
        User::factory()->admin()->create([
            'email' => 'bundle-user@example.com',
            'name' => 'Bundle User',
        ]);

        File::ensureDirectoryExists($this->uploadsDirectory);
        File::put($this->uploadsDirectory.'/bundle.csv', 'col1,col2\n1,2');

        $export = (new ApplicationBundleExporter)->export();
        User::query()->delete();

        $zip = new UploadedFile(
            $export['path'],
            'mlb-draft-bundle.zip',
            'application/zip',
            null,
            true,
        );

        $admin = User::factory()->admin()->create(['email' => 'importer@example.com']);

        $this->actingAs($admin)
            ->post(route('admin.application-bundle.store'), [
                'bundle' => $zip,
                'confirm' => '1',
            ])
            ->assertRedirect(route('admin.application-bundle.show'))
            ->assertSessionHas('status', 'bundle-imported');

        DB::purge('sqlite');

        $this->assertDatabaseHas('users', [
            'email' => 'bundle-user@example.com',
            'name' => 'Bundle User',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'importer@example.com',
        ]);
    }

    public function test_non_admin_cannot_access_sync_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.application-bundle.show'))
            ->assertForbidden();
    }

    public function test_import_rejects_invalid_bundle(): void
    {
        $admin = User::factory()->admin()->create();
        $invalidZipPath = storage_path('app/invalid-bundle-'.uniqid('', true).'.zip');
        File::put($invalidZipPath, 'not a zip');

        $zip = new UploadedFile(
            $invalidZipPath,
            'invalid.zip',
            'application/zip',
            null,
            true,
        );

        $this->actingAs($admin)
            ->post(route('admin.application-bundle.store'), [
                'bundle' => $zip,
                'confirm' => '1',
            ])
            ->assertSessionHasErrors('bundle');

        File::delete($invalidZipPath);
    }
}
