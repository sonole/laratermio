<x-filament-panels::page>
    {{ $this->form }}

    <div style="width: 100% !important; min-width: 100% !important; margin-top: 1.5rem;">
        <div
            x-data="{ html: @js($previewHtml) }"
            x-effect="html = @js($previewHtml); $refs.previewFrame.contentWindow.document.open(); $refs.previewFrame.contentWindow.document.write(html); $refs.previewFrame.contentWindow.document.close();"
            style="width: 100% !important; height: 500px !important"
        >
            <iframe
                x-ref="previewFrame"
                style="width: 100% !important; height: 100% !important; border: none !important; margin: 0 !important; padding: 0 !important;"
                sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin"
            ></iframe>
        </div>
    </div>
</x-filament-panels::page>
