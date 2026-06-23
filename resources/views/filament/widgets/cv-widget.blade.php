<x-filament::widget>
    <x-filament::section>
        <x-slot name="heading">CV Generator</x-slot>

        <x-slot name="description">
            <div class="flex flex-col gap-1">
                @if ($cvExists && $lastGeneratedAt)
                    <span class="text-gray-700 dark:text-gray-300">
                        Last compiled PDF file: <strong class="font-medium text-gray-900 dark:text-white">{{ $lastGeneratedAt }}</strong>
                    </span>
                @else
                    <span class="text-amber-600 dark:text-amber-400 font-medium">No compiled CV PDF has been generated yet.</span>
                @endif
            </div>
        </x-slot>

        <x-slot name="afterHeader">
            <div class="flex items-center gap-3">
                @if ($cvExists)
                    <x-filament::link
                        :href="$this->cvUrl()"
                        icon="heroicon-o-eye"
                        target="_blank"
                        color="gray"
                    >
                        View Generated PDF
                    </x-filament::link>
                @endif

                <x-filament::button
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-arrow-path"
                    wire:loading.class="opacity-50"
                >
                    <span wire:loading.remove wire:target="generate">Regenerate CV</span>
                    <span wire:loading wire:target="generate">Generating…</span>
                </x-filament::button>
            </div>
        </x-slot>

        <div class="text-sm text-gray-500 dark:text-gray-400 space-y-3">
            <p>
                Generates a fresh ATS-friendly PDF from your current experience, education, skills, and projects.
            </p>

            <p class="pb-3">
                The live changes you make to your profile metrics will not reflect on your public CV link until you hit <strong>Regenerate CV</strong>.
            </p>

            @if ($cvExists)
                <div class="flex items-start gap-3 rounded-lg bg-amber-50 text-amber-800 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50 dark:border-amber-900/50 text-xs">
                    <div style="margin-top: 0.45rem;">
                        <span class="font-semibold">Data Synchronization Note:</span>
                        <p>If you updated any skills, experience, or project records recently, the current file is outdated. Please regenerate to apply updates, or use the <a href="{{ route('filament.admin.pages.template-preview') }}" class="underline font-medium hover:text-amber-700 dark:hover:text-amber-300">Template Preview</a> tool to test styling before finalizing.</p>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament::widget>
