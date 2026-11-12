<?php

namespace Tests\Feature;

use App\Services\PhotoStorage;
use App\Services\UploadScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['security.scan_uploads' => true]);
        Storage::fake('evidence');
        Storage::fake('quarantine');
    }

    public function test_only_clean_original_and_reencoded_photos_can_be_saved(): void
    {
        $scanner = \Mockery::mock(UploadScanner::class);
        $scanner->shouldReceive('scan')->twice()->andReturn('clean');
        $this->app->instance(UploadScanner::class, $scanner);
        $result = app(PhotoStorage::class)->store(UploadedFile::fake()->image('synthetic.png', 30, 30));
        Storage::disk('evidence')->assertExists($result['path']);
        $this->assertCount(0, Storage::disk('quarantine')->allFiles());
    }

    public function test_infected_upload_is_quarantined_privately_and_never_saved_as_evidence(): void
    {
        $scanner = \Mockery::mock(UploadScanner::class);
        $scanner->shouldReceive('scan')->once()->andReturn('infected');
        $this->app->instance(UploadScanner::class, $scanner);
        try {
            app(PhotoStorage::class)->store(UploadedFile::fake()->image('synthetic.png'));
            $this->fail('Malware must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('photos', $e->errors());
        }
        $this->assertCount(0, Storage::disk('evidence')->allFiles());
        $files = Storage::disk('quarantine')->allFiles();
        $this->assertCount(1, $files);
        $this->assertStringNotContainsString(str_replace('\\', '/', public_path()).'/', str_replace('\\', '/', Storage::disk('quarantine')->path($files[0])));
        $this->get('/storage/security-quarantine/'.$files[0])->assertForbidden();
        // Windows fileperms cannot represent Unix 0600. The non-public root and
        // denied HTTP path are checked on both systems; Linux CI also checks mode.
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->assertSame('private', Storage::disk('quarantine')->getVisibility($files[0]));
        }
        $this->assertDatabaseCount('hazard_photos', 0);
    }

    public function test_scanner_outage_fails_closed_before_any_evidence_write(): void
    {
        $scanner = \Mockery::mock(UploadScanner::class);
        $scanner->shouldReceive('scan')->once()->andReturn('unavailable');
        $this->app->instance(UploadScanner::class, $scanner);
        try {
            app(PhotoStorage::class)->store(UploadedFile::fake()->image('synthetic.png'));
            $this->fail('Outage must not allow upload.');
        } catch (HttpException $e) {
            $this->assertSame(503, $e->getStatusCode());
        }
        $this->assertCount(0, Storage::disk('evidence')->allFiles());
    }

    public function test_clamav_response_parser_rejects_errors_and_oversized_input(): void
    {
        $scanner = new class extends UploadScanner
        {
            public string $reply = "stream: OK\0";

            protected function exchange(string $bytes): string
            {
                return $this->reply;
            }
        };
        $this->assertSame('clean', $scanner->scan('test'));
        $scanner->reply = "stream: Test-Signature FOUND\0";
        $this->assertSame('infected', $scanner->scan('test'));
        $scanner->reply = "INSTREAM size limit exceeded. ERROR\0";
        $this->assertSame('unavailable', $scanner->scan('test'));
        $this->assertSame('unavailable', $scanner->scan(str_repeat('x', 1024 * 1024 + 1)));
    }
}
