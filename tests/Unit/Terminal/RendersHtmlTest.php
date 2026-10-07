<?php

use App\Terminal\Concerns\RendersHtml;

/** Exposes the protected helpers of the RendersHtml trait. */
function rendersHtmlProbe(): object
{
    return new class
    {
        use RendersHtml;

        public function unknownOption(string $arg): string
        {
            return $this->renderUnknownOption($arg);
        }

        public function error(string $message): string
        {
            return $this->renderError($message);
        }

        public function notFound(string $command): string
        {
            return $this->renderNotFound($command);
        }
    };
}

describe('RendersHtml::header', function () {
    it('renders the title as a comment-style header', function () {
        expect(rendersHtmlProbe()->header('projects'))
            ->toBe('<p class="t-header">// projects</p>');
    });

    it('escapes the title', function () {
        $html = rendersHtmlProbe()->header('<script>alert(1)</script>');

        expect($html)
            ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
            ->not->toContain('<script>');
    });
});

describe('RendersHtml::renderError', function () {
    it('wraps the message in the error class', function () {
        expect(rendersHtmlProbe()->error('something broke'))
            ->toBe("<span class='t-error'>something broke</span>");
    });

    it('leaves the message as given so callers can embed markup, and must escape user data themselves', function () {
        expect(rendersHtmlProbe()->error('no <strong>x</strong>'))
            ->toContain('<strong>x</strong>');
    });
});

describe('RendersHtml::renderUnknownOption', function () {
    it('names the option and points at help', function () {
        $html = rendersHtmlProbe()->unknownOption('--nope');

        expect($html)
            ->toContain("class='t-error'")
            ->toContain('unknown option: <strong>--nope</strong>')
            ->toContain("<span class='t-accent'>help</span>");
    });

    it('escapes the option', function () {
        $html = rendersHtmlProbe()->unknownOption('<img src=x onerror=alert(1)>');

        expect($html)
            ->toContain('&lt;img src=x onerror=alert(1)&gt;')
            ->not->toContain('<img');
    });

    it('escapes quotes so the value cannot break out of an attribute', function () {
        expect(rendersHtmlProbe()->unknownOption('"\'x'))->toContain('&quot;&#039;x');
    });
});

describe('RendersHtml::renderNotFound', function () {
    it('names the command and points at help', function () {
        $html = rendersHtmlProbe()->notFound('foo');

        expect($html)
            ->toContain("class='t-error'")
            ->toContain('command not found: <strong>foo</strong>')
            ->toContain("<span class='t-accent'>help</span>");
    });

    it('escapes the command', function () {
        $html = rendersHtmlProbe()->notFound('<b>rm</b> & co');

        expect($html)
            ->toContain('&lt;b&gt;rm&lt;/b&gt; &amp; co')
            ->not->toContain('<b>rm');
    });
});
