<?php
namespace Portal;

final class Menu
{
    public array $items;
    public string $currentPath;
    public int $activeSection = 0;

    public function __construct(array $items, string $requestUri)
    {
        $this->items = array_values($items);
        $this->currentPath = parse_url($requestUri, PHP_URL_PATH) ?: '';
        foreach ($this->items as $index => $item) {
            if ($this->contains([$item])) { $this->activeSection = $index; break; }
        }
    }

    public function contains(array $items): bool
    {
        foreach ($items as $item) {
            if (isset($item['submenu']) && $this->contains($item['submenu'])) return true;
            if (($item['link'] ?? '#') !== '#' && parse_url($item['link'], PHP_URL_PATH) === $this->currentPath) return true;
        }
        return false;
    }

    public function firstLink(array $item): string
    {
        return !empty($item['submenu']) ? $this->firstLink($item['submenu'][0]) : ($item['link'] ?? '#');
    }
}
