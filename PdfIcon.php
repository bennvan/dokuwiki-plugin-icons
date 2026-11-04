<?php

namespace dokuwiki\plugin\icons;

use dokuwiki\Cache\Cache;

/** Local SVG images for DW2PDF; browser rendering stays with Iconify. */
class PdfIcon
{
    private static $collections = [];

    public static function file(string $id, string $color = '#000000', bool $fixedWidth = false): ?string
    {
        if (!preg_match('/^([a-z0-9]+(?:-[a-z0-9]+)*):([a-z0-9]+(?:-[a-z0-9]+)*)$/D', $id, $match)) {
            return null;
        }
        [, $prefix, $name] = $match;
        $source = __DIR__ . '/assets/iconify/json/' . $prefix . '.json.gz';
        if (!is_file($source)) return null;
        $cache = new Cache('icons-pdf:' . $id . ':' . $color . ':' . (int) $fixedWidth, '.icons.svg');
        if ($cache->useCache(['files' => [__FILE__, $source]])) return $cache->cache;

        if (!isset(self::$collections[$prefix])) {
            self::$collections[$prefix] = json_decode(io_readFile($source), true) ?: [];
        }
        $collection = self::$collections[$prefix];
        $icon = self::resolve($collection, $name);
        if (!$icon) return null;
        $width = (float) ($icon['width'] ?? $collection['width'] ?? 16);
        $height = (float) ($icon['height'] ?? $collection['height'] ?? 16);
        $left = (float) ($icon['left'] ?? $collection['left'] ?? 0);
        $top = (float) ($icon['top'] ?? $collection['top'] ?? 0);
        if ($width <= 0 || $height <= 0) return null;
        $body = '<g transform="translate(' . -$left . ' ' . -$top . ')">' . $icon['body'] . '</g>';
        if (!empty($icon['hFlip'])) $body = '<g transform="translate(' . $width . ' 0) scale(-1 1)">' . $body . '</g>';
        if (!empty($icon['vFlip'])) $body = '<g transform="translate(0 ' . $height . ') scale(1 -1)">' . $body . '</g>';
        $rotation = (($icon['rotate'] ?? 0) % 4 + 4) % 4;
        if ($rotation) {
            $transforms = [1 => "translate($height 0) rotate(90)",
                           2 => "translate($width $height) rotate(180)",
                           3 => "translate(0 $width) rotate(270)"];
            $body = '<g transform="' . $transforms[$rotation] . '">' . $body . '</g>';
            if ($rotation % 2) [$width, $height] = [$height, $width];
        }
        // Fit into the syntax's square (or FW) box without stretching the artwork.
        $ratio = $fixedWidth ? 1.25 : 1;
        $boxWidth = max($width, $height * $ratio);
        $boxHeight = $boxWidth / $ratio;
        $body = '<g transform="translate(' . (($boxWidth - $width) / 2) . ' ' . (($boxHeight - $height) / 2) . ')">' . $body . '</g>';
        // SVG images cannot inherit the surrounding document's currentColor.
        $body = str_replace('currentColor', hsc($color), $body);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"'
            . ' width="' . $boxWidth . '" height="' . $boxHeight . '" viewBox="0 0 ' . $boxWidth . ' ' . $boxHeight . '">'
            . $body . '</svg>';
        return $cache->storeCache($svg) ? $cache->cache : null;
    }

    private static function resolve(array $collection, string $name, array $seen = []): ?array
    {
        if (isset($collection['icons'][$name])) return $collection['icons'][$name];
        if (isset($seen[$name]) || !isset($collection['aliases'][$name]['parent'])) return null;
        $seen[$name] = true;
        $alias = $collection['aliases'][$name];
        $parent = self::resolve($collection, $alias['parent'], $seen);
        if (!$parent) return null;
        foreach (['hFlip', 'vFlip'] as $key) {
            $alias[$key] = !empty($parent[$key]) !== !empty($alias[$key]);
        }
        $alias['rotate'] = ($parent['rotate'] ?? 0) + ($alias['rotate'] ?? 0);
        return array_replace($parent, $alias);
    }
}
