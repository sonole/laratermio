<?php

use App\Enums\SettingKey;
use App\Terminal\Commands\PingCommand;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('ping', function () {
    it('is named ping and hidden from help', function () {
        $command = new PingCommand;

        expect($command->name())->toBe('ping')
            ->and($command->helpGroup())->toBeNull();
    });

    it('prints four replies and a statistics summary for localhost', function () {
        $response = terminalCoreRun('ping', 'localhost');
        $html = $response['html'];

        expect($response['type'])->toBe('echo')
            ->and($html)->toContain('PING localhost (127.0.0.1): 56 data bytes')
            ->and(substr_count($html, '64 bytes from 127.0.0.1: icmp_seq='))->toBe(4)
            ->and($html)->toContain('--- localhost ping statistics ---')
            ->and($html)->toContain('4 packets transmitted, 4 received, <span class="t-accent">0%</span> packet loss')
            ->and($html)->toMatch('#round-trip min/avg/max/stddev = [\d.]+/[\d.]+/[\d.]+/[\d.]+ ms#');
    });

    it('numbers the replies from zero', function () {
        $html = terminalCoreHtml('ping', 'localhost');

        foreach ([0, 1, 2, 3] as $seq) {
            expect($html)->toContain("icmp_seq=$seq ttl=64");
        }
    });

    it('answers a loopback address with sub-millisecond times', function (string $host) {
        $html = terminalCoreHtml('ping', $host);

        preg_match_all('/time=([\d.]+) ms/', $html, $matches);

        expect($matches[1])->toHaveCount(4);

        foreach ($matches[1] as $time) {
            expect((float) $time)->toBeLessThan(1.0);
        }
    })->with(['127.0.0.1', 'localhost', 'LOCALHOST']);

    it('answers any valid ip address with internet-like times', function () {
        $html = terminalCoreHtml('ping', '8.8.8.8');

        preg_match_all('/time=([\d.]+) ms/', $html, $matches);

        expect($html)->toContain('PING 8.8.8.8 (8.8.8.8): 56 data bytes')
            ->and($html)->toContain('64 bytes from 8.8.8.8: icmp_seq=')
            ->and($matches[1])->toHaveCount(4);

        foreach ($matches[1] as $time) {
            expect((float) $time)->toBeGreaterThanOrEqual(12.0);
        }
    });

    it('accepts an ipv6 address', function () {
        expect(terminalCoreHtml('ping', '::1'))->toContain('PING ::1 (::1): 56 data bytes');
    });

    it('resolves the prompt hostname to a stable made-up address', function () {
        terminalCoreSetting(SettingKey::PromptHostname, 'my-host');

        $first = terminalCoreHtml('ping', 'my-host');
        $second = terminalCoreHtml('ping', 'my-host');

        preg_match('/PING my-host \((\d+\.\d+\.\d+\.\d+)\)/', $first, $a);
        preg_match('/PING my-host \((\d+\.\d+\.\d+\.\d+)\)/', $second, $b);

        expect($a)->toHaveCount(2)
            ->and($a[1])->toBe($b[1])
            ->and(filter_var($a[1], FILTER_VALIDATE_IP))->not->toBeFalse();
    });

    it('picks a host for itself when called without an argument', function () {
        $html = terminalCoreHtml('ping');

        expect($html)->toContain('PING ')->toContain('4 packets transmitted, 4 received');
    });

    it('reports an unknown host', function () {
        $response = terminalCoreRun('ping', 'example.com');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('ping: cannot resolve <strong>example.com</strong>: Unknown host')
            ->and($response['html'])->not->toContain('64 bytes');
    });

    it('treats a malformed ip address as an unknown host', function () {
        expect(terminalCoreHtml('ping', '999.1.1.1'))->toContain('Unknown host');
    });

    it('escapes an unknown host', function () {
        $html = terminalCoreHtml('ping', '<script>alert(1)</script>');

        expect($html)
            ->toContain('<strong>&lt;script&gt;alert(1)&lt;/script&gt;</strong>')
            ->not->toContain('<script>');
    });

    it('answers --help instead of pinging', function () {
        $html = terminalCoreHtml('ping', '--help');

        expect($html)->toContain('// ping --help')->not->toContain('64 bytes');
    });
});
