<?php

class IntegrationPipeline
{
    private array $processors = [];
    private array $loaders = [];

    public function addProcessor(string $name, callable $callback): self
    {
        $this->processors[$name] = $callback;
        return $this;
    }

    public function addLoader(string $name, callable $callback): self
    {
        $this->loaders[$name] = $callback;
        return $this;
    }

    public function run(): void
    {
        $results = [];

        foreach ($this->processors as $name => $processor) {
            echo "▶ Запуск процессора: {$name}\n";
            $results[$name] = $processor();
        }

        foreach ($this->loaders as $name => $loader) {
            echo "▶ Запуск загрузчика: {$name}\n";
            $loader($results);
        }

        echo "✅ Pipeline завершён\n";
    }
}
