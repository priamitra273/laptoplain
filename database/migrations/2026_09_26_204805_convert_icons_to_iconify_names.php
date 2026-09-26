<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $primeIconMap = [
        'pi pi-bolt' => 'i-lucide-zap',
        'pi pi-bookmark' => 'i-lucide-bookmark',
        'pi pi-info-circle' => 'i-lucide-circle-alert',
        'pi pi-check-square' => 'i-lucide-square-check',
        'pi pi-exclamation-triangle' => 'i-lucide-triangle-alert',
    ];

    public function up(): void
    {
        foreach (['menus', 'task_categories'] as $table) {
            $icons = DB::table($table)->distinct()->pluck('icon');

            foreach ($icons as $icon) {
                $iconifyName = $this->toIconifyName($icon);

                if ($iconifyName !== $icon) {
                    DB::table($table)->where('icon', $icon)->update(['icon' => $iconifyName]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['menus', 'task_categories'] as $table) {
            $icons = DB::table($table)->distinct()->pluck('icon');

            foreach ($icons as $icon) {
                if (! is_string($icon) || ! str_starts_with($icon, 'i-lucide-')) {
                    continue;
                }

                $pascalCaseName = str_replace(' ', '', ucwords(str_replace('-', ' ', substr($icon, strlen('i-lucide-')))));

                DB::table($table)->where('icon', $icon)->update(['icon' => $pascalCaseName]);
            }
        }
    }

    private function toIconifyName(?string $icon): ?string
    {
        if ($icon === null) {
            return null;
        }

        if (str_starts_with($icon, 'i-')) {
            return $icon;
        }

        if (isset($this->primeIconMap[$icon])) {
            return $this->primeIconMap[$icon];
        }

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $icon)) {
            return $icon;
        }

        $name = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $icon);
        $name = preg_replace('/([A-Z])([A-Z][a-z])/', '$1-$2', $name);
        $name = preg_replace('/(?<!\d)([a-zA-Z])(\d)/', '$1-$2', $name);

        return 'i-lucide-'.strtolower($name);
    }
};
