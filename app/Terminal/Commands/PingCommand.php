<?php

namespace App\Terminal\Commands;

use App\Facades\Settings;
use App\Terminal\TerminalResponse;

class PingCommand extends BaseCommand
{
    public function name(): string
    {
        return 'ping';
    }

    public function helpGroup(): ?string
    {
        return null;
    }

    protected function execute(?string $arg): TerminalResponse
    {
        $hosts = $this->getHosts();

        $host = $arg ?? \Arr::random($hosts);

        $isValidIp = filter_var($host, FILTER_VALIDATE_IP) !== false;

        if (! $isValidIp && ! in_array(strtolower($host), $hosts, true)) {
            $safe = e($host);

            return TerminalResponse::echo(
                $this->renderError("ping: cannot resolve <strong>$safe</strong>: Unknown host")
            );
        }

        $isLocal = in_array(strtolower($host), ['127.0.0.1', 'localhost'], true);
        $times = $this->generateTimes($isLocal);
        $ip = $isLocal ? '127.0.0.1' : ($isValidIp ? $host : $this->resolvedIp($host));

        $rows = '';
        foreach ($times as $seq => $time) {
            $rows .= "<p>64 bytes from $ip: icmp_seq=$seq ttl=64 time=$time ms</p>";
        }

        $min = min($times);
        $max = max($times);
        $avg = number_format(array_sum($times) / count($times), 3);
        $stddev = number_format($this->stddev($times), 3);

        $safeHost = e($host);

        return TerminalResponse::echo(<<<HTML
        <div class="t-block">
            <p class="t-dim">PING $safeHost ($ip): 56 data bytes</p>
            {$rows}
            <p class="t-dim t-mt">--- $safeHost ping statistics ---</p>
            <p>4 packets transmitted, 4 received, <span class="t-accent">0%</span> packet loss</p>
            <p class="t-dim">round-trip min/avg/max/stddev = $min/$avg/$max/$stddev ms</p>
        </div>
        HTML);
    }

    /** @return non-empty-array<int, string> */
    private function generateTimes(bool $local): array
    {
        $times = [];

        for ($i = 0; $i < 4; $i++) {
            if ($local) {
                // sub-1ms with slight jitter
                $base = mt_rand(50, 900) / 1000;
            } else {
                // realistic internet RTT: ~12-35ms base with occasional spike
                $base = mt_rand(120, 350) / 10;
                if ($i === 2 && mt_rand(1, 3) === 1) {
                    $base *= mt_rand(15, 30) / 10; // spike on 3rd packet, 1-in-3 chance
                }
            }

            $times[$i] = number_format($base, 3);
        }

        return $times;
    }

    private function resolvedIp(string $hostname): string
    {
        // Stable fake public IP derived from hostname so it looks consistent
        $seed = crc32($hostname);

        return sprintf(
            '%d.%d.%d.%d',
            ($seed & 0xFF000000) >> 24 & 0x7F | 0x01,
            ($seed & 0x00FF0000) >> 16,
            ($seed & 0x0000FF00) >> 8,
            ($seed & 0x000000FF),
        );
    }

    /** @param array<int, string> $values */
    private function stddev(array $values): float
    {
        $floats = array_map('floatval', $values);
        $mean = array_sum($floats) / count($floats);
        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $floats)) / count($floats);

        return sqrt($variance);
    }

    /** @return array<int, string> */
    private function getHosts(): array
    {
        $hosts = ['127.0.0.1', 'localhost'];

        $promptHostname = Settings::getPromptHostname();

        return ! empty($promptHostname)
            ? array_merge($hosts, [$promptHostname])
            : $hosts;
    }
}
