<?php

use App\Terminal\TerminalResponse;

describe('TerminalResponse constructors', function () {
    it('builds an echo response carrying html', function () {
        $response = TerminalResponse::echo('<p>hi</p>');

        expect($response->type)->toBe('echo')
            ->and($response->html)->toBe('<p>hi</p>')
            ->and($response->toArray())->toBe(['type' => 'echo', 'html' => '<p>hi</p>']);
    });

    it('builds a clear response with only a type', function () {
        $response = TerminalResponse::clear();

        expect($response->type)->toBe('clear')
            ->and($response->toArray())->toBe(['type' => 'clear']);
    });

    it('builds an open response carrying a url', function () {
        $response = TerminalResponse::open('https://example.com');

        expect($response->type)->toBe('open')
            ->and($response->url)->toBe('https://example.com')
            ->and($response->toArray())->toBe(['type' => 'open', 'url' => 'https://example.com']);
    });

    it('builds a client history response with only a type', function () {
        expect(TerminalResponse::clientHistory()->toArray())->toBe(['type' => 'client_history']);
    });

    it('builds a cd response with the prompt as html and the new path', function () {
        $response = TerminalResponse::cd('visitor@host:~/skills$ ', '~/skills');

        expect($response->type)->toBe('cd')
            ->and($response->html)->toBe('visitor@host:~/skills$ ')
            ->and($response->path)->toBe('~/skills')
            ->and($response->toArray())->toBe([
                'type' => 'cd',
                'html' => 'visitor@host:~/skills$ ',
                'path' => '~/skills',
            ]);
    });

    it('builds the keyed responses with the key in the payload', function (string $factory, string $type) {
        $response = TerminalResponse::$factory('some-key');

        expect($response->type)->toBe($type)
            ->and($response->key)->toBe('some-key')
            ->and($response->toArray())->toBe(['type' => $type, 'key' => 'some-key']);
    })->with([
        'theme' => ['theme', 'theme'],
        'paginate' => ['paginate', 'paginate'],
        'selector' => ['selector', 'selector'],
        'overlay' => ['overlay', 'overlay'],
    ]);
});

describe('TerminalResponse::toArray', function () {
    it('omits every empty field except the type', function () {
        expect(TerminalResponse::echo('')->toArray())->toBe(['type' => 'echo'])
            ->and(TerminalResponse::open('')->toArray())->toBe(['type' => 'open'])
            ->and(TerminalResponse::theme('')->toArray())->toBe(['type' => 'theme']);
    });

    it('keeps a path even when the prompt html is empty', function () {
        expect(TerminalResponse::cd('', '~')->toArray())->toBe(['type' => 'cd', 'path' => '~']);
    });

    it('does not escape or alter the html it carries', function () {
        $html = '<span class="t-error">a &amp; b</span>';

        expect(TerminalResponse::echo($html)->toArray()['html'])->toBe($html);
    });
});

describe('TerminalResponse as a value object', function () {
    it('cannot be instantiated directly', function () {
        $constructor = (new ReflectionClass(TerminalResponse::class))->getConstructor();

        expect($constructor->isPrivate())->toBeTrue();
    });

    it('is immutable', function () {
        $response = TerminalResponse::echo('hi');

        expect(fn () => $response->html = 'changed')->toThrow(Error::class);
    });
});
