<?php

use App\Terminal\TerminalContext;

describe('TerminalContext in the container', function () {
    it('is shared within one request', function () {
        app(TerminalContext::class)->setCwd('~/skills');

        expect(app(TerminalContext::class)->getCwd())->toBe('~/skills')
            ->and(app(TerminalContext::class))->toBe(app(TerminalContext::class));
    });

    it('starts at home in a fresh scope', function () {
        app(TerminalContext::class)->setCwd('~/skills');

        app()->forgetScopedInstances();

        expect(app(TerminalContext::class)->getCwd())->toBe('~');
    });
});
