import ReportService from "../../_common/classes/services/ReportService.js";
import { createChartPanel } from "../../_common/prototypes/ChartPanel.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { getBackendUrl } from "../../_common/scripts/functions.js";
import { createAuthFailureHandler, serviceUnavailableMessage } from "../../_common/scripts/ui.js";

/**
 * Dashboard administrativo.
 *
 * Uma única chamada (`GET /reports/dashboard`) traz todos os indicadores e as
 * séries dos gráficos — antes eram quatro listagens completas só para contar
 * registros. Os gráficos são desenhados pelo `ChartPanel` (protótipo) e os
 * relatórios em PDF vêm como Blob, porque o token JWT viaja no header.
 */

const session = new SessionStorage("admin");

const reports = new ReportService();
reports.setAuthToken(session.getToken());

const toast = createToast();
const charts = createChartPanel();
const handleAuthFailure = createAuthFailureHandler({
    session,
    toast,
    loginUrl: getBackendUrl("admin/login")
});

const money = (value) =>
    Number(value ?? 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

function fillCounter(selector, value) {
    const element = document.querySelector(selector);

    if (element) {
        element.textContent = value;
    }
}

function renderCharts(data) {
    const byCategory = data.services_by_category ?? [];
    const byType = data.users_by_type ?? [];
    const byFaqType = data.faqs_by_type ?? [];

    charts.bar("chart-services", {
        label: "Serviços",
        labels: byCategory.map((row) => row.label),
        data: byCategory.map((row) => row.total)
    });

    charts.doughnut("chart-catalog", {
        label: "Valor (R$)",
        labels: byCategory.map((row) => row.label),
        data: byCategory.map((row) => row.total_value)
    });

    charts.stackedBar("chart-users", {
        labels: byType.map((row) => row.label === "ADMIN" ? "Administradores" : "Clientes"),
        datasets: [
            { label: "Ativos", data: byType.map((row) => row.active) },
            { label: "Inativos", data: byType.map((row) => row.inactive) }
        ]
    });

    charts.stackedBar("chart-faqs", {
        labels: byFaqType.map((row) => row.label),
        datasets: [
            { label: "Publicadas", data: byFaqType.map((row) => row.published) },
            { label: "Rascunhos", data: byFaqType.map((row) => row.unpublished) }
        ]
    });
}

async function loadDashboard() {
    ["#total-services", "#total-categories", "#total-users", "#total-faqs",
     "#catalog-value", "#pending-contacts"].forEach((selector) => fillCounter(selector, "..."));

    try {
        const result = await reports.dashboard();

        if (!result.ok) {
            handleAuthFailure(result);
            toast.fromResponse(result);
            ["#total-services", "#total-categories", "#total-users", "#total-faqs",
             "#catalog-value", "#pending-contacts"].forEach((selector) => fillCounter(selector, "-"));
            return;
        }

        const totals = result.data?.totals ?? {};

        fillCounter("#total-services", totals.services ?? 0);
        fillCounter("#total-categories", totals.categories ?? 0);
        fillCounter("#total-users", totals.users ?? 0);
        fillCounter("#total-faqs", totals.faqs ?? 0);
        fillCounter("#catalog-value", money(totals.catalog_value));
        fillCounter("#pending-contacts", totals.pending_contacts ?? 0);

        renderCharts(result.data ?? {});
    } catch (error) {
        console.error("Erro ao carregar o dashboard:", error);
        toast.error(serviceUnavailableMessage());
    }
}

// --- relatórios em PDF -------------------------------------------------------

/** Cada botão do painel mapeia para o método correspondente do serviço. */
const reportLoaders = {
    services: () => reports.servicesPdf(),
    users: () => reports.usersPdf(),
    faqs: () => reports.faqsPdf()
};

async function openReport(button) {
    const loader = reportLoaders[button.dataset.report];

    if (!loader) {
        return;
    }

    const originalLabel = button.textContent;
    button.disabled = true;
    button.textContent = "Gerando...";

    let objectUrl = null;

    try {
        const result = await loader();

        if (!result.ok) {
            handleAuthFailure(result);
            toast.fromResponse(result);
            return;
        }

        objectUrl = URL.createObjectURL(result.blob);
        window.open(objectUrl, "_blank", "noopener");
        toast.success("Relatório gerado com sucesso.");
    } catch (error) {
        console.error("Erro ao gerar o relatório:", error);
        toast.error(serviceUnavailableMessage());
    } finally {
        button.disabled = false;
        button.textContent = originalLabel;

        // A aba já carregou o PDF; manter a URL viva vazaria memória.
        if (objectUrl) {
            setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
        }
    }
}

document.querySelectorAll(".btn-report").forEach((button) => {
    button.addEventListener("click", () => openReport(button));
});

await loadDashboard();
