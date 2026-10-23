<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use InvalidArgumentException;
use Tests\TestCase;

class StructuredEnrichmentUnsupportedTypeTest extends TestCase
{
    public function test_unknown_entity_type_is_rejected_before_normalization(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported enrichment entity type.');

        app(StructuredEnrichmentValidator::class)->normalize('unknown-type', [
            'name' => 'Synthetic unknown entity',
        ]);
    }

    public function test_invalid_entity_does_not_receive_generated_slug(): void
    {
        $payload = ['name' => 'Synthetic unknown entity'];

        try {
            app(StructuredEnrichmentValidator::class)->normalize('unknown-type', $payload);
            $this->fail('Unsupported entity types must not be normalized.');
        } catch (InvalidArgumentException $exception) {
            $this->assertArrayNotHasKey('slug', $payload);
            $this->assertSame('Unsupported enrichment entity type.', $exception->getMessage());
        }
    }
}
