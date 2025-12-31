<?php

namespace Tests\Unit\Models;

use App\Models\Assignment;
use App\Models\Rubric;
use App\Models\RubricCriteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RubricTest extends TestCase
{
    use RefreshDatabase;

    public function test_rubric_belongs_to_assignment(): void
    {
        $assignment = Assignment::factory()->create();
        $rubric = Rubric::factory()->create(['assignment_id' => $assignment->id]);

        $this->assertTrue($rubric->assignment->is($assignment));
    }

    public function test_rubric_has_many_criteria(): void
    {
        $rubric = Rubric::factory()->create();
        $criteria = RubricCriteria::factory()->create(['rubric_id' => $rubric->id]);

        $this->assertTrue($rubric->criteria->contains($criteria));
    }

    public function test_is_structured_returns_true_for_structured_type(): void
    {
        $rubric = Rubric::factory()->structured()->create();

        $this->assertTrue($rubric->isStructured());
        $this->assertFalse($rubric->isFreeform());
    }

    public function test_is_freeform_returns_true_for_freeform_type(): void
    {
        $rubric = Rubric::factory()->freeform()->create();

        $this->assertTrue($rubric->isFreeform());
        $this->assertFalse($rubric->isStructured());
    }

    public function test_get_total_points_sums_criteria_for_structured(): void
    {
        $rubric = Rubric::factory()->structured()->create();
        RubricCriteria::factory()->create(['rubric_id' => $rubric->id, 'max_points' => 25]);
        RubricCriteria::factory()->create(['rubric_id' => $rubric->id, 'max_points' => 25]);
        RubricCriteria::factory()->create(['rubric_id' => $rubric->id, 'max_points' => 50]);

        $this->assertEquals(100, $rubric->getTotalPoints());
    }

    public function test_get_total_points_uses_assignment_max_for_freeform(): void
    {
        $assignment = Assignment::factory()->create(['max_points' => 100]);
        $rubric = Rubric::factory()->freeform()->create(['assignment_id' => $assignment->id]);

        $this->assertEquals(100, $rubric->getTotalPoints());
    }
}
