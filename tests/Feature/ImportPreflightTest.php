<?php
namespace Tests\Feature;
use App\Services\InstituteImportPreflightService;
use Tests\TestCase;
class ImportPreflightTest extends TestCase {
 public function test_required_fields_are_checked_before_import(): void { $r=(new InstituteImportPreflightService)->validate([['name'=>'A'],['name'=>'']]); $this->assertSame(1,$r['valid']); $this->assertSame(1,$r['invalid']); }

    public function test_whitespace_only_name_is_invalid(): void
 {
  $result = (new InstituteImportPreflightService)->validate([['name' => '   ']]);
  $this->assertSame(0, $result['valid']);
  $this->assertSame(1, $result['invalid']);
 }


    public function test_empty_import_input_is_a_valid_empty_preflight_result(): void
    {
        $result = (new InstituteImportPreflightService)->validate([]);

        $this->assertSame(0, $result['valid']);
        $this->assertSame(0, $result['invalid']);
        $this->assertSame([], $result['errors']);
    }
}