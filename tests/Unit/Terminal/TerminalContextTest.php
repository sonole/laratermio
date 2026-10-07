<?php

use App\Terminal\TerminalContext;

describe('TerminalContext', function () {
    it('starts at the home directory', function () {
        expect((new TerminalContext)->getCwd())->toBe('~');
    });

    it('remembers the directory that was set', function () {
        $context = new TerminalContext;

        $context->setCwd('~/projects');
        expect($context->getCwd())->toBe('~/projects');

        $context->setCwd('~');
        expect($context->getCwd())->toBe('~');
    });

    it('keeps each instance independent', function () {
        $a = new TerminalContext;
        $b = new TerminalContext;

        $a->setCwd('~/skills');

        expect($b->getCwd())->toBe('~');
    });
});

describe('TerminalContext::FILESYSTEM_ROOTS', function () {
    it('lists the virtual directories the visitor can cd into', function () {
        expect(TerminalContext::FILESYSTEM_ROOTS)
            ->toBe(['projects', 'skills', 'experience', 'education', 'contact']);
    });

    it('only holds plain unique directory names', function () {
        $roots = TerminalContext::FILESYSTEM_ROOTS;

        expect($roots)->toHaveCount(count(array_unique($roots)));

        foreach ($roots as $root) {
            expect($root)->toMatch('/^[a-z]+$/');
        }
    });
});
