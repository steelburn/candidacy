<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSecurityTest extends TestCase
{
    /** @test */
    public function it_requires_authentication_for_reports()
    {
        $response = $this->getJson('/api/reports/dashboard');
        
        $response->assertStatus(401);
    }
}
