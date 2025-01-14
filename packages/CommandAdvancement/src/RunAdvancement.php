<?php

namespace Packages\CommandAdvancement;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

/**
 * 指令執行
 */
class RunAdvancement extends Command
{
    /** @var string 指令集位置 */
    const PATH = 'app/Console/Advancements';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'advancement:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '執行 版本演化指令';

    public function __construct(private readonly Filesystem $filesystem)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        $files = $this->getFiles();

        $files->each(function (string $file_name) {
            $advancement = $this->filesystem->requireOnce($file_name);

            $advancement->run();

            CommandAdvancement::query()->create(['file' => $file_name]);

            $this->info("Advance - {$file_name} - successful");
        });

        if ($files->isNotEmpty()) {
            $this->info('Command Advancement Finished');
        }
    }

    /**
     * 獲得需要執行的指令集
     *
     * @return Collection
     * @throws Exception
     */
    private function getFiles(): Collection
    {
        try {
            $files = collect($this->filesystem->files(self::PATH))
                ->map(fn ($file) => $file->getPathname())
                ->pipe($this->filterShouldRunFruitions(...));

            if ($files->isEmpty()) {
                throw new FileNotFoundException();
            }

            return $files;
        } catch (DirectoryNotFoundException|FileNotFoundException $e) {
            $this->warn('Nothing Advance');

            return collect();
        }
    }

    /**
     * 排除已經執行過的指令集
     *
     * @param Collection $files
     *
     * @return Collection
     */
    private function filterShouldRunFruitions(Collection $files): Collection
    {
        $history = CommandAdvancement::query()->pluck('file');

        return $files->reject(fn (string $file_name) => $history->contains($file_name));
    }
}
