<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HazardReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

class StatisticsExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function record(User $owner, string $date, string $category = 'accident', string $status = 'submitted'): HazardReport
    {
        $report = HazardReport::create([
            'reference_number' => 'HZ-STAT-'.uniqid(), 'user_id' => $owner->id,
            'category_id' => $category, 'department_id' => 'mdrrmo', 'title' => 'PRIVATE eyewitness record',
            'description' => 'PRIVATE evidence and contact information', 'address' => 'PRIVATE exact address',
            'barangay' => 'Anambongan', 'selected_latitude' => 15.86, 'selected_longitude' => 120.4,
            'location_source' => 'manual', 'status' => $status, 'occurred_at' => '2025-12-31 16:00:00',
        ]);
        $report->forceFill(['created_at' => $date])->save();

        return $report;
    }

    public function test_daily_and_yearly_category_counts_use_selected_basis_manila_boundaries_and_zero_periods(): void
    {
        $owner = User::factory()->create();
        $this->record($owner, '2026-01-31 15:59:59');
        $this->record($owner, '2026-01-31 16:00:00');
        $this->record($owner, '2026-02-01 16:00:00', 'tree');
        $this->record($owner, '2026-02-01 16:00:00', 'tree', 'rejected');
        $this->record($owner, '2026-02-01 16:00:00', 'tree')->delete();
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        $query = '/api/admin/analytics?from=2026-02-01&to=2026-02-03&group=day&basis=submitted';
        $this->getJson($query)->assertOk()->assertJsonPath('total', 2)->assertJsonPath('timezone', 'Asia/Manila')
            ->assertJsonCount(3, 'periods')->assertJsonPath('periods.0.categories.accident', 1)
            ->assertJsonPath('periods.1.categories.tree', 1)->assertJsonPath('periods.2.total', 0);
        $this->getJson($query.'&category=tree')->assertOk()->assertJsonPath('total', 1)->assertJsonCount(1, 'categories');
        $this->getJson(str_replace('basis=submitted', 'basis=incident', $query))->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/admin/analytics?from=2025-01-01&to=2027-12-31&group=year&basis=submitted')
            ->assertOk()->assertJsonPath('total', 3)->assertJsonCount(3, 'periods')
            ->assertJsonPath('periods.0.total', 0)->assertJsonPath('periods.1.categories.accident', 2)
            ->assertJsonPath('periods.1.categories.tree', 1)->assertJsonPath('periods.2.total', 0);
    }

    public function test_exports_are_admin_only_and_validate_the_same_bounded_filters(): void
    {
        $url = '/api/admin/analytics/export/xlsx?from=2026-01-01&to=2026-12-31&group=month';
        $this->getJson($url)->assertUnauthorized();
        $this->authenticateUser(User::factory()->create());
        foreach (['pdf', 'docx', 'xlsx'] as $format) {
            $this->getJson(str_replace('/xlsx?', '/'.$format.'?', $url))->assertForbidden();
        }
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        $this->getJson(str_replace('to=2026-12-31', 'to=2046-12-31', $url))->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->getJson(str_replace('group=month', 'group=year&basis=invalid', $url))->assertUnprocessable()->assertJsonValidationErrors('basis');
        $this->getJson(str_replace('to=2026-12-31&group=month', 'to=2046-12-31&group=year', $url))->assertUnprocessable();
        $this->getJson(str_replace('/xlsx?', '/html?', $url))->assertNotFound();
    }

    public function test_pdf_word_and_excel_are_real_documents_with_charts_and_only_aggregate_statistics(): void
    {
        $owner = User::factory()->create();
        $this->record($owner, '2026-02-01 00:00:00');
        $this->record($owner, '2026-02-01 00:00:00', 'tree');
        Category::where('id', 'accident')->update(['name' => '=SUM(1,2) & <Category>']);
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        $query = '?from=2026-02-01&to=2026-02-02&group=day&basis=submitted';
        foreach (['pdf', 'docx', 'xlsx'] as $format) {
            $response = $this->getJson('/api/admin/analytics/export/'.$format.$query)->assertOk();
            $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $bytes = $response->getContent();
            if ($format === 'pdf') {
                $this->assertStringStartsWith('%PDF-', $bytes);
                $this->assertStringContainsString('/Subtype /Image', $bytes);

                continue;
            }
            $file = tempnam(sys_get_temp_dir(), 'bantay-stat-test-');
            try {
                file_put_contents($file, $bytes);
                $zip = new ZipArchive;
                $this->assertTrue($zip->open($file));
                $xml = $zip->getFromName($format === 'docx' ? 'word/document.xml' : 'xl/sharedStrings.xml');
                $this->assertStringContainsString('2026-02-01', $xml);
                $this->assertStringContainsString('Total reports', $xml);
                $this->assertStringNotContainsString('PRIVATE', $xml);
                $media = array_filter(array_map(fn ($i) => $zip->getNameIndex($i), range(0, $zip->numFiles - 1)), fn ($name) => str_starts_with($name, $format === 'docx' ? 'word/media/' : 'xl/media/'));
                $this->assertNotEmpty($media);
                $this->assertNotFalse(simplexml_load_string($xml));
                $zip->close();
                if ($format === 'xlsx') {
                    $book = IOFactory::load($file);
                    $sheet = $book->getSheetByName('Category totals');
                    $values = $sheet->toArray(null, true, false);
                    $row = array_values(array_filter($values, fn ($cells) => $cells[0] === '=SUM(1,2) & <Category>'))[0];
                    $this->assertSame(1, $row[1]);
                    $this->assertSame('s', $sheet->getCell('A2')->getDataType());
                    $this->assertSame(2, $book->getSheet(0)->getCell('B4')->getValue());
                    $book->disconnectWorksheets();
                }
            } finally {
                unlink($file);
            }
        }
        $this->assertSame([], glob(storage_path('app/private/statistics-exports/statistics-*')));
    }

    public function test_export_generation_is_rate_limited_and_twenty_year_statistics_remain_bounded(): void
    {
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        $this->getJson('/api/admin/analytics?from=2020-01-01&to=2039-12-31&group=year&basis=submitted')
            ->assertOk()->assertJsonCount(20, 'periods');
        $url = '/api/admin/analytics/export/pdf?from=2026-01-01&to=2026-01-01&group=day';
        for ($index = 0; $index < 6; $index++) {
            $this->getJson($url)->assertOk();
        }
        $this->getJson($url)->assertTooManyRequests();
    }
}
