<?php

namespace HuangYi\Swoole\Routing;

use Illuminate\Support\Arr;

class RouteGroup
{
    /**
     * Merge route groups into a new array.
     *
     * @param  array $new
     * @param  array $old
     * @return array
     */
    public static function merge($new, $old)
    {
        $new = array_merge(static::formatAs($new, $old), [
            'namespace' => static::formatNamespace($new, $old),
        ]);

        return array_merge_recursive(Arr::except(
            $old, ['namespace', 'as']
        ), $new);
    }

    /**
     * Format the namespace for the new group attributes.
     *
     * @param  array $new
     * @param  array $old
     * @return string|null
     */
    protected static function formatNamespace($new, $old)
    {
        if (isset($new['namespace'])) {
            return isset($old['namespace'])
                ? trim($old['namespace'], '\\') . '\\' . trim($new['namespace'], '\\')
                : trim($new['namespace'], '\\');
        }

        return isset($old['namespace']) ? $old['namespace'] : null;
    }

    /**
     * Format the "as" clause of the new group attributes.
     *
     * @param  array $new
     * @param  array $old
     * @return array
     */
    protected static function formatAs($new, $old)
    {
        if (isset($old['as'])) {
            $new['as'] = $old['as'] . Arr::get($new, 'as', '');
        }

        return $new;
    }
}
