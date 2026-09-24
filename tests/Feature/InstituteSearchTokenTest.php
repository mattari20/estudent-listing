<?php
namespace Tests\Feature;
use App\Services\InstituteSearchTokenService; use Tests\TestCase;
class InstituteSearchTokenTest extends TestCase { public function test_normalization_is_stable(): void { $s=new InstituteSearchTokenService; $this->assertSame('university of lahore',$s->normalize(' University---of   Lahore ')); $this->assertSame(['university','of','lahore'],$s->tokens('University of Lahore')); } }