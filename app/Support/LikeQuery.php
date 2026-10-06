<?php

namespace App\Support;

class LikeQuery
{
    /**
     * Escapa los comodines de LIKE para que el usuario pueda buscar
     * literalmente los caracteres %, _ y \.
     *
     * Sin este escape, buscar "%" devuelve todos los registros y buscar "_"
     * coincide con cualquier caracter, permitiendo enumerar datos.
     *
     * @param  string  $term  Texto buscado por el usuario.
     * @param  bool    $wrap  Si es true, envuelve el termino con % para busqueda parcial.
     */
    public static function escape(string $term, bool $wrap = true): string
    {
        $escaped = str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $term
        );

        return $wrap ? '%' . $escaped . '%' : $escaped;
    }

    /**
     * Aplica el escape de LIKE a una consulta ya construida.
     *
     * Uso:
     *   LikeQuery::apply($query, 'nombre', $busqueda)
     *   LikeQuery::apply($query, ['nombre', 'apellido'], $busqueda)
     */
    public static function apply($query, string|array $columns, string $term): void
    {
        $escaped = self::escape($term);

        $query->where(function ($sub) use ($columns, $escaped) {
            foreach ((array) $columns as $column) {
                $sub->orWhere($column, 'like', $escaped);
            }
        });
    }
}
