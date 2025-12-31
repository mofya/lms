<?php

namespace Tests\Unit\Models;

use App\Models\Rubric;
use App\Models\RubricCriteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RubricCriteriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_rubric_criteria_belongs_to_rubric(): void
    {
        $rubric = Rubric::factory()->create();
        $criteria = RubricCriteria::factory()->create(['rubric_id' => $rubric->id]);

        $this->assertInstanceOf(Rubric::class, $criteria->rubric);
        $this->assertEquals($rubric->id, $criteria->rubric->id);
    }

    public function test_max_points_is_cast_to_integer(): void
    {
        $criteria = RubricCriteria::factory()->create(['max_points' => '25']);

        $criteria->refresh();

        $this->assertIsInt($criteria->max_points);
        $this->assertEquals(25, $criteria->max_points);
    }

    public function test_position_is_cast_to_integer(): void
    {
        $criteria = RubricCriteria::factory()->create(['position' => '3']);

        $criteria->refresh();

        $this->assertIsInt($criteria->position);
        $this->assertEquals(3, $criteria->position);
    }

    public function test_uses_custom_table_name(): void
    {
        $criteria = new RubricCriteria;

        $this->assertEquals('rubric_criteria', $criteria->getTable());
    }

    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $rubric = Rubric::factory()->create();
        $data = [
            'rubric_id' => $rubric->id,
            'name' => 'Content Quality',
            'description' => 'Evaluates the quality of written content',
            'max_points' => 20,
            'position' => 1,
        ];

        $criteria = RubricCriteria::create($data);

        $this->assertEquals($rubric->id, $criteria->rubric_id);
        $this->assertEquals('Content Quality', $criteria->name);
        $this->assertEquals('Evaluates the quality of written content', $criteria->description);
        $this->assertEquals(20, $criteria->max_points);
        $this->assertEquals(1, $criteria->position);
    }

    public function test_factory_creates_valid_rubric_criteria(): void
    {
        $criteria = RubricCriteria::factory()->create();

        $this->assertNotNull($criteria->id);
        $this->assertNotNull($criteria->rubric_id);
        $this->assertNotNull($criteria->name);
        $this->assertNotNull($criteria->max_points);
    }

    public function test_multiple_criteria_can_belong_to_same_rubric(): void
    {
        $rubric = Rubric::factory()->create();
        $criteria1 = RubricCriteria::factory()->create(['rubric_id' => $rubric->id, 'position' => 1]);
        $criteria2 = RubricCriteria::factory()->create(['rubric_id' => $rubric->id, 'position' => 2]);
        $criteria3 = RubricCriteria::factory()->create(['rubric_id' => $rubric->id, 'position' => 3]);

        $this->assertCount(3, $rubric->criteria);
        $this->assertTrue($rubric->criteria->contains($criteria1));
        $this->assertTrue($rubric->criteria->contains($criteria2));
        $this->assertTrue($rubric->criteria->contains($criteria3));
    }
}
