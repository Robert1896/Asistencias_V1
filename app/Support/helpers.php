<?php

use App\Support\LikeQuery;

if (! function_exists('escape_like')) {
    /**
     * Escapa los comodines de LIKE (% y _) de una busqueda de usuario.
     *
     * @param  string  $term  Texto buscado por el usuario.
     * @param  bool    $wrap  Si es true, envuelve el termino con % para busqueda parcial.
     */
    function escape_like(string $term, bool $wrap = true): string
    {
        return LikeQuery::escape($term, $wrap);
    }
}
