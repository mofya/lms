<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use App\Models\Rubric;

class AssignmentGradingPromptBuilder
{
    public function build(AssignmentSubmission $submission): string
    {
        $assignment = $submission->assignment;
        $rubric = $assignment->rubric;
        $submissionContent = $this->getSubmissionContent($submission);

        $prompt = "You are an expert educator grading a student assignment.\n\n";
        $prompt .= "ASSIGNMENT DETAILS:\n";
        $prompt .= "Title: {$assignment->title}\n";
        $prompt .= "Maximum Points: {$assignment->max_points}\n";
        
        if ($assignment->instructions) {
            $prompt .= "\nInstructions:\n{$assignment->instructions}\n";
        }

        if ($rubric) {
            $prompt .= "\n" . $this->buildRubricSection($rubric);
        }

        $prompt .= "\n\nSTUDENT SUBMISSION:\n";
        $prompt .= $submissionContent;

        $prompt .= "\n\nPlease grade this submission according to the rubric and provide:\n";
        $prompt .= "1. A numerical score out of {$assignment->max_points} points\n";
        
        if ($rubric && $rubric->isStructured()) {
            $prompt .= "2. Scores for each criterion (as JSON object with criterion names as keys)\n";
        }
        
        $prompt .= "3. Detailed feedback explaining strengths and areas for improvement\n";
        $prompt .= "4. Any concerns about plagiarism or academic integrity\n\n";
        
        $prompt .= "Respond in JSON format:\n";
        $prompt .= "{\n";
        $prompt .= "  \"score\": <number>,\n";
        
        if ($rubric && $rubric->isStructured()) {
            $prompt .= "  \"criteria_scores\": {\n";
            foreach ($rubric->criteria as $criterion) {
                $prompt .= "    \"{$criterion->name}\": <number>,\n";
            }
            $prompt .= "  },\n";
        }
        
        $prompt .= "  \"feedback\": \"<detailed feedback text>\",\n";
        $prompt .= "  \"plagiarism_concerns\": \"<any concerns or 'none'>\"\n";
        $prompt .= "}";

        return $prompt;
    }

    protected function buildRubricSection(Rubric $rubric): string
    {
        $section = "GRADING RUBRIC:\n";

        if ($rubric->isFreeform()) {
            $section .= $rubric->freeform_text;
        } else {
            foreach ($rubric->criteria as $criterion) {
                $section .= "\n- {$criterion->name} ({$criterion->max_points} points)\n";
                if ($criterion->description) {
                    $section .= "  {$criterion->description}\n";
                }
            }
        }

        return $section;
    }

    protected function getSubmissionContent(AssignmentSubmission $submission): string
    {
        $assignment = $submission->assignment;

        if ($assignment->type === 'text') {
            return $submission->content ?? '(No content submitted)';
        }

        if (in_array($assignment->type, ['file', 'code']) && $submission->file_path) {
            $filePath = storage_path('app/' . $submission->file_path);
            
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                
                // Limit file size for prompt (first 10000 characters)
                if (strlen($content) > 10000) {
                    $content = substr($content, 0, 10000) . "\n\n[... file truncated for grading ...]";
                }
                
                return "File: {$submission->file_name}\n\nContent:\n{$content}";
            }
        }

        return '(No submission content available)';
    }
}
