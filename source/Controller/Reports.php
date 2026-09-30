<?php

namespace Source\Controller;

use Source\Models\Contact;
use Source\Models\Faqs\Question;
use Source\Models\Service;
use Source\Models\User;
use Source\Support\PdfReport;

/**
 * Relatórios e indicadores da área administrativa.
 *
 * - `dashboard` devolve JSON com os números e as séries que o painel usa para
 *   desenhar os gráficos (Chart.js);
 * - as rotas `*Pdf` devolvem o relatório já renderizado em PDF (Dompdf), com
 *   `Content-Type: application/pdf`, e por isso NÃO seguem o envelope JSON.
 *
 * Todas exigem token de administrador (type_id = 1).
 */
class Reports extends Api
{
    public function dashboard(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        $service = new Service();
        $user = new User();
        $question = new Question();
        $contact = new Contact();

        $byCategory = $service->statsByCategory();
        $byType = $user->statsByType();
        $byFaqType = $question->statsByType();

        $totalServices = array_sum(array_map(fn($row) => (int)$row->total, $byCategory));
        $totalValue = array_sum(array_map(fn($row) => (float)$row->total_value, $byCategory));
        $totalUsers = array_sum(array_map(fn($row) => (int)$row->active, $byType));
        $totalFaqs = array_sum(array_map(fn($row) => (int)$row->published, $byFaqType));

        $pending = count(array_filter($contact->selectLatest(500), fn($row) => (int)$row->answered === 0));

        $this->call(200, "success", "Indicadores do painel", "success")->back([
            "totals" => [
                "services" => $totalServices,
                "categories" => count($byCategory),
                "users" => $totalUsers,
                "faqs" => $totalFaqs,
                "pending_contacts" => $pending,
                // Somatório dos valores dos serviços ativos: quanto o catálogo "vale"
                "catalog_value" => round($totalValue, 2)
            ],
            "services_by_category" => array_map(fn($row) => [
                "label" => $row->category_name,
                "total" => (int)$row->total,
                "total_value" => round((float)$row->total_value, 2)
            ], $byCategory),
            "users_by_type" => array_map(fn($row) => [
                "label" => $row->type_name,
                "active" => (int)$row->active,
                "inactive" => (int)$row->inactive
            ], $byType),
            "faqs_by_type" => array_map(fn($row) => [
                "label" => $row->type_description,
                "published" => (int)$row->published,
                "unpublished" => (int)$row->unpublished
            ], $byFaqType)
        ]);
    }

    /** Relatório do catálogo: serviços por categoria, com valor e nº de imagens. */
    public function servicesPdf(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        $service = new Service();
        $rows = $service->selectAllForReport();
        $stats = $service->statsByCategory(false);

        $report = new PdfReport(
            "Relatório de Serviços",
            "Catálogo completo, agrupado por categoria"
        );

        $report->setSummary([
            "serviços" => count($rows),
            "categorias" => count($stats),
            "ativos" => count(array_filter($rows, fn($row) => (int)$row->active === 1)),
            "em catálogo" => $this->money(array_sum(array_map(fn($row) => (float)$row->value, $rows)))
        ]);

        $report->setColumns(["#", "Serviço", "Categoria", "Valor", "Imagens", "Situação"]);
        $report->setRows(array_map(fn($row) => [
            $row->id,
            $row->name,
            $row->category_name,
            $this->money((float)$row->value),
            $row->images,
            (int)$row->active === 1 ? "Ativo" : "Inativo"
        ], $rows));

        $this->streamOrFail($report, "relatorio-servicos.pdf");
    }

    /** Relatório de usuários por perfil de acesso. */
    public function usersPdf(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        $user = new User();
        $rows = $user->selectAllForReport();

        $report = new PdfReport(
            "Relatório de Usuários",
            "Contas cadastradas, agrupadas por perfil de acesso"
        );

        $report->setSummary([
            "contas" => count($rows),
            "administradores" => count(array_filter($rows, fn($row) => $row->type_name === "ADMIN")),
            "clientes" => count(array_filter($rows, fn($row) => $row->type_name === "CLIENT")),
            "ativas" => count(array_filter($rows, fn($row) => (int)$row->active === 1))
        ]);

        $report->setColumns(["#", "Nome", "E-mail", "Perfil", "Situação"]);
        $report->setRows(array_map(fn($row) => [
            $row->id,
            $row->name,
            $row->email,
            $row->type_name === "ADMIN" ? "Administrador" : "Cliente",
            (int)$row->active === 1 ? "Ativa" : "Inativa"
        ], $rows));

        $this->streamOrFail($report, "relatorio-usuarios.pdf");
    }

    /** Relatório das perguntas frequentes, publicadas e não publicadas. */
    public function faqsPdf(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        $question = new Question();
        $rows = $question->selectAllForReport();

        $report = new PdfReport(
            "Relatório de Perguntas Frequentes",
            "Perguntas e respostas cadastradas, agrupadas por tipo"
        );

        $report->setSummary([
            "perguntas" => count($rows),
            "publicadas" => count(array_filter($rows, fn($row) => (int)$row->active === 1))
        ]);

        $report->setColumns(["#", "Tipo", "Pergunta", "Resposta", "Situação"]);
        $report->setRows(array_map(fn($row) => [
            $row->id,
            $row->type_description,
            $row->question,
            // A resposta é encurtada para a tabela não estourar a largura da página
            mb_strimwidth((string)$row->answer, 0, 120, "..."),
            (int)$row->active === 1 ? "Publicada" : "Rascunho"
        ], $rows));

        $this->streamOrFail($report, "relatorio-faqs.pdf");
    }

    /**
     * Envia o PDF ou, se a renderização falhar, responde o erro no envelope JSON
     * padrão — nunca um PDF corrompido.
     */
    private function streamOrFail(PdfReport $report, string $fileName): void
    {
        if (!$report->stream($fileName)) {
            $this->call(500, "internal_server_error", $report->getErrorMessage(), "error")->back();
        }
    }

    private function money(float $value): string
    {
        return "R$ " . number_format($value, 2, ",", ".");
    }
}
