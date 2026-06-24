<?php

namespace App\Terminal\Commands;

use App\Models\SkillCategory;
use App\Terminal\TerminalResponse;

class SkillsCommand extends BaseCommand
{
    public function name(): string
    {
        return 'skills';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        if ($arg !== null) {
            return $this->responseUnknownOption($arg);
        }

        $categories = SkillCategory::activeOrdered()->get();

        if ($categories->isEmpty()) {
            return TerminalResponse::echo($this->renderError('no skills entries found.'));
        }

        $sections = $categories
            ->map(function (SkillCategory $category) {
                $tags = implode('', array_map(
                    fn ($item) => '<span class="t-skill-tag">'.e($item).'</span>',
                    $category->items ?? []
                ));

                return <<<HTML
                <div class="t-skill-row">
                    <span class="t-skill-cat">$category->name</span>
                    <div class="t-skill-tags">$tags</div>
                </div>
                HTML;
            })
            ->implode('');

        return TerminalResponse::echo(<<<HTML
        <div class="t-block">
            {$this->header('skills')}
            <div class="t-skills-new">$sections</div>
        </div>
        HTML);
    }
}
