<?php

namespace Vendimia\Core\ExceptionHandler;

use Throwable;
use ReflectionClass;

use Vendimia\Exception\VendimiaException;
use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Http\Request;
use Vendimia\Routing\MatchedRoute;
use Vendimia\View\View;

use const Vendimia\DEBUG;

/**
 * Shows detailed information about an exception using HTML.
 *
 * This must not be used in production
 */
class Web extends ExceptionHandlerAbstract
{
    /**
     * Retrive a few lines of a source file
     */
    public static function readSourceLines($file, $line, $count = 8)
    {
        $lines = [];

        $start = $line - intval($count / 2);
        if ($start < 0) {
            $count -= $start;
            $start = 0;
        }

        $f = fopen($file, 'r');

        for ($i = 0; $i < $start; $i++) {
            fgets($f);
        }

        $i = 0;
        while (($i < $count) && !feof($f)) {
            $lines[$start + $i + 1] = htmlentities(fgets($f));
            $i++;
        }
        fclose($f);

        return $lines;
    }

    /**
     * Renders a simple HTML with info of the throwable
     */
    public function handle(Throwable $throwable): never
    {
        $object = ObjectManager::retrieve();

        $throwable_class = get_class($throwable);

        if (!DEBUG) {
            // Si no estamos en debug, solo incluimos el fichero 500.php

            $object->new(View::class)->renderHttpStatus(500, [
            ]);

        }


        // No usamos vistas, en caso sean excepciones en Vendimia mismo
        $html = <<<EOF
        <html><head><style>
        body{margin: 0px; font-family: sans-serif}
        header{background: #008080; color: white; padding: 15px}
        main{display: grid; grid-template-columns: 1fr 1fr; grid-gap: 20px}
        section{padding: 10px;}
        h1{margin: 0px; font-weight: normal; font-size: 200%}
        h2{margin: 0px; font-weight: normal; font-size: 120%}
        a[data-code] {color: #AAF; font-size: 80%; font-variant: small-caps; border: 1px solid #AAF; border-radius: 10px; padding: 0px 10px; }
        a[data-code].hidden {display: none}

        li {font-family: monospace; padding-bottom: 20px}
        div.code-line span.class{color: #008080}
        div.code-line span.method{color: #008080; font-weight: bold}
        div.code-line span.args{color: #888}

        table {font-family: monospace; border-collapse:collapse; width: 100%}

        table.code {table-layout: fixed; border: 1px solid #eee; margin: 10px 0px; display: none; max-width: 50vw;overflow: hidden}
        table.code td:first-child{width: 25px; background: #eee;color:#aaa;text-align: right; padding: 0px 10px; user-select: none}
        table.code td:nth-child(2){width: 100%; padding-left: 20px; white-space: pre; overflow: hidden}
        table.code tr.selected{background: #FFA}
        table.code tr.selected td:first-child {color: black; font-weight: bold}
        table.information th {width: 200px; text-align: right; background: #e0eeee; padding: 2px 4px; vertical-align: top}
        table.information td {padding-left: 10px; border-bottom: 1px solid #eee}
        </style>
        <script>
        function toggle_links(id) {document.querySelectorAll(`a[data-code="\${id}"]`).forEach(el => el.classList.toggle('hidden'))}
        function expand(id) {document.querySelector(`table#code-\${id}`).style.display = 'block'; toggle_links(id)}
        function collapse(id) {document.querySelector(`table#code-\${id}`).style.display = 'none'; toggle_links(id)}
        </script>
        <title>{$throwable_class}: {$throwable->getMessage()}</title>
        </head>
        <body>
        <header>
        <h1>{$throwable->getMessage()}</h1>
        <h2><strong>{$throwable_class}</strong> on {$throwable->getFile()}:{$throwable->getLine()}</h2>
        </header>

        <main><section>

        <h2>Source file</h2>

        <table class="code" style="display: block">
        EOF;

        $lines = self::readSourceLines($throwable->getFile(), $throwable->getLine());
        foreach ($lines as $line => $source) {
            $tr_class = "";
            if ($line == $throwable->getLine()) {
                $tr_class = "class='selected'";
            }
            $html .= "<tr {$tr_class}><td>{$line}</td><td>{$source}</td></tr>";
        }

        $html .= <<<EOF
        </table>

        <h2>Traceback</h2>
        <ol>
        EOF;
        $source_idx = 0;
        foreach ($throwable->getTrace() as $trace) {
            $source =
                '<span class="class">' . ($trace['class'] ?? '') . '</span>' .
                ($trace['type'] ?? '') .
                '<span class="method">' . $trace['function'] . '</span>'
            ;
            $args = self::processTraceArgs($trace['args'] ?? '-');

            $file_line = '';
            if (isset($trace['file']) && isset($trace['line'])) {
                $file_line = "{$trace['file']}:{$trace['line']}";
                $file_line .= ' <a data-code="' . $source_idx . '" href="javascript:expand(' . $source_idx . ')">Show code</a>';
                $file_line .= ' <a data-code="' . $source_idx . '" href="javascript:collapse(' . $source_idx . ')" class="hidden">Hide code</a>';
            }

            $html .= <<<EOF
            <li>
            <div class="code-line">{$source}(<span class="args">{$args}</span>)</div>
            <div class="source-line">{$file_line}</div>

            EOF;

            if ($file_line) {
                $html .= '<table class="code" id="code-' . $source_idx . '">';
                $lines = self::readSourceLines($trace['file'], $trace['line']);
                foreach ($lines as $line => $source) {
                    $tr_class = "";
                    if ($line == $trace['line']) {
                        $tr_class = "class='selected'";
                    }
                    $html .= "<tr {$tr_class}><td>{$line}</td><td>{$source}</td></tr>";
                }
                $html .= '</table>';
            }

            $html .= "</li>";

            $source_idx += 1;
        }
        $html .= <<<EOF
        </ol></section>
        <section>
        EOF;

        $http_code = 500;
        if ($throwable instanceof VendimiaException) {
            $http_code = $throwable->getExtra()['__HTTP_CODE'] ?? 500;

            $html .= '<h2>Extra information</h2><table class="information">';

            foreach ($throwable->getExtra() as $key => $value) {
                $value = self::processTraceArgs($value, separator: '<br />');
                $html .=  "<tr><th>{$key}:</th><td>{$value}</td>";
            }

            $html .= '</table>';
        }

        $html .= '<h2>Request</h2><table class="information">';

        $request = $object->get(Request::class);
        $matched_rule = $object->get(MatchedRoute::class);
        $query_params = self::processTraceArgs($request->query_params);
        $parsed_body = self::processTraceArgs($request->parsed_body);

        $html .= "<tr><th>Method and URL:</th><td>{$request->getMethod()} {$request->getUri()?->getPath()}</td>";
        $html .= "<tr><th>Matched rule:</th><td>{$matched_rule}</td>";
        $html .= "<tr><th>Query parameters:</th><td>{$query_params}</td>";
        $html .= "<tr><th>Parsed body:</th><td>{$parsed_body}</td>";

        $html .= '</table>';

        $html .= <<<EOF
        </section></main>
        </body></html>
        EOF;

        http_response_code($http_code);
        echo $html;
        exit;
    }
}