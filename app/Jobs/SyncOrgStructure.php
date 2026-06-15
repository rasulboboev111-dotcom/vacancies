<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

/**
 * Запускает импорт оргструктуры Tojiktelecom на воркере очереди, а не в процессе
 * вызывающей стороны. Тяжёлая работа (запрос к API + полная сверка дерева в одной
 * транзакции) живёт в команде `org:import`; эта задача просто вызывает её в
 * режиме `--sync` из фона, чтобы диспетчер вернулся немедленно.
 */
class SyncOrgStructure implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Импорт может занять время (таймаут API + повторы + большая транзакция),
     * поэтому даём щедрый запас — это переопределяет --timeout воркера.
     */
    public int $timeout = 1800;

    /**
     * Никогда не повторяем частичный прогон автоматически: повторная отправка
     * идемпотентна, а повтор на середине — нет.
     */
    public int $tries = 1;

    public function __construct(
        public bool $api = false,
        public ?string $file = null,
        public bool $fresh = false,
    ) {}

    /**
     * Удерживаем уникальную блокировку весь прогон, чтобы пересекающиеся ручная и
     * ночная отправки не запустили импорт одновременно.
     */
    public function uniqueFor(): int
    {
        return $this->timeout;
    }

    public function handle(): void
    {
        $options = ['--sync' => true];

        if ($this->api) {
            $options['--api'] = true;
        }
        if ($this->file !== null) {
            $options['--file'] = $this->file;
        }
        if ($this->fresh) {
            $options['--fresh'] = true;
        }

        Artisan::call('org:import', $options);
    }
}
