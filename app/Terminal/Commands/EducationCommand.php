<?php

namespace App\Terminal\Commands;

use App\Models\Education;
use App\Terminal\TerminalResponse;

class EducationCommand extends BaseCommand
{
    public function name(): string
    {
        return 'education';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        if ($arg !== null) {
            return $this->responseUnknownOption($arg);
        }

        $all = Education::activeOrdered()->get();

        if ($all->isEmpty()) {
            return TerminalResponse::echo($this->renderError('no education entries found.'));
        }

        $degrees = $all->where('is_certification', false);
        $certifications = $all->where('is_certification', true);

        $degreesSection = '';
        if ($degrees->isNotEmpty()) {
            $degreesHtml = $degrees->map(fn (Education $edu) => $this->renderItem($edu))->implode('');
            $degreesSection = '<p class="t-header">// degrees</p>'.$degreesHtml;
        }

        $certificationsSection = '';
        if ($certifications->isNotEmpty()) {
            $certsHtml = $certifications->map(fn (Education $edu) => $this->renderItem($edu))->implode('');
            $certificationsSection = '<p class="t-header">// certifications</p>'.$certsHtml;
        }

        return TerminalResponse::echo(<<<HTML
        <div class="t-block">
            {$this->header('education')}
            {$degreesSection}
            {$certificationsSection}
        </div>
        HTML);
    }

    private function renderItem(Education $edu): string
    {
        $title = e($edu->title);
        $institution = e($edu->institution);
        $period = e($edu->period);
        $description = $edu->description
            ? '<p class="t-paragraph">'.nl2br(e($edu->description)).'</p>'
            : '';
        $certificateLink = $edu->certificate_url
            ? '<p class="t-paragraph"><a href="'.e($edu->certificate_url).'" target="_blank" class="t-link">Certificate URL</a></p>'
            : '';

        return <<<HTML
        <div class="t-exp-item">
            <div class="t-exp-header">
                <span class="t-accent">$title</span> <span class="t-dim">@</span> <span class="t-label">$institution</span>
                <span class="t-period">$period</span>
            </div>
            $description
            $certificateLink
        </div>
        HTML;
    }
}
