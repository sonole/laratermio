<?php

namespace App\Terminal;

readonly class TerminalResponse
{
    private function __construct(
        public string $type,
        public string $html = '',
        public string $url = '',
        public string $key = '',
        public string $path = '',
    ) {}

    public static function echo(string $html): self
    {
        return new self('echo', html: $html);
    }

    public static function clear(): self
    {
        return new self('clear');
    }

    public static function open(string $url): self
    {
        return new self('open', url: $url);
    }

    public static function theme(string $key): self
    {
        return new self('theme', key: $key);
    }

    public static function paginate(string $key): self
    {
        return new self('paginate', key: $key);
    }

    public static function selector(string $key): self
    {
        return new self('selector', key: $key);
    }

    public static function overlay(string $key): self
    {
        return new self('overlay', key: $key);
    }

    public static function cd(string $prompt, string $path): self
    {
        return new self('cd', html: $prompt, path: $path);
    }

    public static function clientHistory(): self
    {
        return new self('client_history');
    }

    /** @return array{type: string, html?: string, url?: string, key?: string, path?: string} */
    public function toArray(): array
    {
        $data = ['type' => $this->type];

        if ($this->html !== '') {
            $data['html'] = $this->html;
        }

        if ($this->url !== '') {
            $data['url'] = $this->url;
        }

        if ($this->key !== '') {
            $data['key'] = $this->key;
        }

        if ($this->path !== '') {
            $data['path'] = $this->path;
        }

        return $data;
    }
}
