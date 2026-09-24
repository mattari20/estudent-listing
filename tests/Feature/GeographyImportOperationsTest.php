<?php
namespace Tests\Feature;
use Tests\TestCase;
class GeographyImportOperationsTest extends TestCase {
 public function test_geography_import_requires_operator_access(): void {
  $this->get(route('institutes.operations.geography-import.create'))->assertRedirect();
 }
}