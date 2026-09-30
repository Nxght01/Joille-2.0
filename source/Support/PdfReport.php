<?php

namespace Source\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;

/**
 * Geração de relatórios em PDF com Dompdf.
 *
 * A classe recebe título, subtítulo e uma tabela (cabeçalho + linhas), monta o
 * HTML com a identidade visual do sistema e devolve o PDF pronto — em string
 * (getOutput) ou direto no navegador (stream).
 */
class PdfReport
{
    private string $title;
    private string $subtitle;
    private array $columns = [];
    private array $rows = [];
    private array $summary = [];
    private ?string $errorMessage = null;

    public function __construct(string $title, string $subtitle = "")
    {
        $this->title = $title;
        $this->subtitle = $subtitle;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /** @param string[] $columns títulos das colunas da tabela */
    public function setColumns(array $columns): PdfReport
    {
        $this->columns = $columns;
        return $this;
    }

    /** @param array<int, array<int, string|int|float|null>> $rows linhas da tabela */
    public function setRows(array $rows): PdfReport
    {
        $this->rows = $rows;
        return $this;
    }

    /** @param array<string, string|int|float> $summary pares rótulo => valor exibidos no topo */
    public function setSummary(array $summary): PdfReport
    {
        $this->summary = $summary;
        return $this;
    }

    /** Devolve os bytes do PDF, ou null em caso de falha. */
    public function getOutput(): ?string
    {
        try {
            $options = new Options();
            $options->set("isRemoteEnabled", false);
            $options->set("defaultFont", "Helvetica");

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($this->html(), "UTF-8");
            $dompdf->setPaper("A4", "portrait");
            $dompdf->render();

            return $dompdf->output();
        } catch (Exception $exception) {
            $this->errorMessage = "Erro ao gerar o PDF: " . $exception->getMessage();
            return null;
        }
    }

    /**
     * Envia o PDF na resposta HTTP.
     * @return bool false quando a geração falhou (nada foi escrito na saída)
     */
    public function stream(string $fileName): bool
    {
        $output = $this->getOutput();

        if ($output === null) {
            return false;
        }

        // O roteador abre um ob_start(); o buffer precisa ser limpo para que o
        // PDF não seja contaminado por espaços ou avisos já emitidos.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header("Content-Type: application/pdf");
        header("Content-Disposition: inline; filename=\"{$fileName}\"");
        header("Content-Length: " . strlen($output));
        echo $output;

        return true;
    }

    private function html(): string
    {
        $company = htmlspecialchars(CONF_COMPANY_NAME, ENT_QUOTES, "UTF-8");
        $address = htmlspecialchars(CONF_COMPANY_ADDRESS, ENT_QUOTES, "UTF-8");
        $phone = htmlspecialchars(CONF_COMPANY_PHONE, ENT_QUOTES, "UTF-8");
        $title = htmlspecialchars($this->title, ENT_QUOTES, "UTF-8");
        $subtitle = htmlspecialchars($this->subtitle, ENT_QUOTES, "UTF-8");
        $generatedAt = date("d/m/Y H:i");

        $summary = "";
        foreach ($this->summary as $label => $value) {
            $label = htmlspecialchars((string)$label, ENT_QUOTES, "UTF-8");
            $value = htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
            $summary .= "<span class=\"chip\"><strong>{$value}</strong> {$label}</span>";
        }

        $head = "";
        foreach ($this->columns as $column) {
            $head .= "<th>" . htmlspecialchars((string)$column, ENT_QUOTES, "UTF-8") . "</th>";
        }

        $body = "";
        if (empty($this->rows)) {
            $colspan = max(1, count($this->columns));
            $body = "<tr><td colspan=\"{$colspan}\" class=\"empty\">Nenhum registro encontrado.</td></tr>";
        }

        foreach ($this->rows as $row) {
            $body .= "<tr>";
            foreach ($row as $cell) {
                $body .= "<td>" . htmlspecialchars((string)$cell, ENT_QUOTES, "UTF-8") . "</td>";
            }
            $body .= "</tr>";
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 24px 28px 40px; }
                body { font-family: Helvetica, Arial, sans-serif; color: #3d2c23; font-size: 11px; }
                header { border-bottom: 2px solid #3d2c23; padding-bottom: 8px; margin-bottom: 14px; }
                header h1 { margin: 0; font-size: 18px; }
                header p { margin: 2px 0 0; font-size: 10px; color: #7a6a60; }
                h2 { font-size: 14px; margin: 0 0 2px; }
                .subtitle { margin: 0 0 12px; font-size: 10px; color: #7a6a60; }
                .chip { display: inline-block; border: 1px solid #d9cec6; border-radius: 10px;
                        padding: 4px 9px; margin: 0 6px 10px 0; font-size: 10px; }
                table { width: 100%; border-collapse: collapse; }
                th { background: #3d2c23; color: #fff; text-align: left; padding: 6px; font-size: 10px; }
                td { border-bottom: 1px solid #e6ded8; padding: 6px; }
                tr:nth-child(even) td { background: #faf8f6; }
                td.empty { text-align: center; color: #7a6a60; padding: 18px; }
                footer { position: fixed; bottom: -24px; left: 0; right: 0;
                         font-size: 9px; color: #7a6a60; text-align: center; }
            </style>
        </head>
        <body>
            <header>
                <h1>{$company}</h1>
                <p>{$address} &middot; {$phone}</p>
            </header>

            <h2>{$title}</h2>
            <p class="subtitle">{$subtitle}</p>

            <div>{$summary}</div>

            <table>
                <thead><tr>{$head}</tr></thead>
                <tbody>{$body}</tbody>
            </table>

            <footer>Relatório gerado pelo sistema {$company} em {$generatedAt}</footer>
        </body>
        </html>
        HTML;
    }
}
