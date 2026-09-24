<?php
namespace Tests\Feature;
use App\Services\InstituteImportPreflightService;
use Tests\TestCase;
class ImportPreflightTest extends TestCase {
 public function test_required_fields_are_checked_before_import(): void { $r=(new InstituteImportPreflightService)->validate([['name'=>'A'],['name'=>'']]); $this->assertSame(1,$r['valid']); $this->assertSame(1,$r['invalid']); }
}